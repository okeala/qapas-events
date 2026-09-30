<?php
namespace App\Models;
use Illuminate\Validation\ValidationException;
class FeedbackInvitation extends Record {
 public const AUDIENCES=['participant'=>'Participants','visitor'=>'Visiteurs et soutiens','junta'=>'Juntas','relay'=>'Points-relais','sponsor'=>'Sponsors','press'=>'Presse'];
 protected $attributes=['status'=>'draft','locale'=>'pt'];
 protected $hidden=['email','email_hash','contact_basis','body_snapshot'];
 protected function casts(): array {return ['email'=>'encrypted','sent_at'=>'datetime','started_at'=>'datetime','responded_at'=>'datetime'];}
 public function campaign(){return $this->belongsTo(FeedbackCampaign::class,'feedback_campaign_id');}
 public function url(): string {return route('feedback.show',['invitation'=>$this->public_id]);}
 protected static function booted(): void {parent::booted();static::saving(function(self $i){
  if($i->exists&&in_array($i->getOriginal('status'),['sending','accepted','unknown'])&&$i->isDirty('status')&&in_array($i->status,['draft','ready']))throw ValidationException::withMessages(['status'=>'Ne pas renvoyer automatiquement une invitation dont le résultat peut déjà être acquis.']);
  if($i->exists&&$i->isDirty(['feedback_campaign_id','source_key','email']))throw ValidationException::withMessages(['email'=>'Conserver le destinataire de cette invitation ; annuler et préparer une nouvelle invitation si nécessaire.']);
  if($i->email&&!filter_var($i->email,FILTER_VALIDATE_EMAIL))throw ValidationException::withMessages(['email'=>'Adresse valide requise.']);
  if(!isset(self::AUDIENCES[$i->audience])||!in_array($i->locale,['fr','pt'])||!in_array($i->status,['draft','ready','sending','accepted','unknown','cancelled']))throw ValidationException::withMessages(['status'=>'État ou public invalide.']);
  $i->email_hash=$i->email?hash('sha256',mb_strtolower(trim($i->email))):null;
  if($i->status==='ready'&&(blank($i->contact_basis)||blank($i->email)))throw ValidationException::withMessages(['contact_basis'=>'Vérifier le contact et la base de cette unique invitation de bilan ; aucun abonnement marketing.']);
 });}
}
