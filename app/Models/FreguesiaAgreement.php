<?php
namespace App\Models;
use Illuminate\Validation\ValidationException;
class FreguesiaAgreement extends Record {
 protected $attributes=['status'=>'draft'];
 protected function casts(): array {return ['signed_at'=>'datetime'];}
 public function eventProject(){return $this->belongsTo(EventProject::class);}
 public function stand(){return $this->belongsTo(Stand::class);}
 public function signed(): bool {return $this->status==='signed'&&$this->signed_at&&!$this->signed_at->isFuture()&&filled($this->authority_evidence)&&filled($this->legal_review)&&filled($this->agreement_evidence)&&filled($this->legal_entity)&&filled($this->signatory);}
 protected static function booted(): void {parent::booted();static::saving(function(self $a){
  if(!$a->stand||$a->stand->event_project_id!==$a->event_project_id||$a->stand->kind!=='village')throw ValidationException::withMessages(['stand_id'=>'Stand de freguesia de cette édition requis.']);
  if($a->exists&&$a->isDirty(['stand_id','event_project_id']))throw ValidationException::withMessages(['stand_id'=>'Conserver le périmètre signé.']);
  if($a->exists&&$a->getOriginal('status')==='signed'&&$a->isDirty(['terms_fr','terms_pt','legal_entity','signatory','authority_evidence','legal_review'])){if(!$a->isDirty('terms_version'))throw ValidationException::withMessages(['terms_version'=>'Nouvelle version et nouvelle signature requises.']);$a->status='draft';$a->signed_at=null;}
  if(!in_array($a->status,['draft','review','signed','withdrawn'])||blank($a->terms_version)||blank($a->terms_fr)||blank($a->terms_pt))throw ValidationException::withMessages(['status'=>'Convention versionnée FR/PT requise.']);
  if($a->status==='signed'&&!$a->signed())throw ValidationException::withMessages(['status'=>'Identité, pouvoir de signature, délibération, revue juridique et convention effectivement signée requis.']);
 });}
}
