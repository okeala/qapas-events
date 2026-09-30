<?php
namespace App\Domain\Community;
use App\Models\{FeedbackCampaign,FeedbackInvitation,EventTicket,OutreachVisit,PressRelease,Distributor,Sponsorship};
use Illuminate\Support\Facades\{DB,Mail};
final class Feedback {
 public function prepare(FeedbackCampaign $c): void {
  abort_unless(auth('admin')->user()?->is_active,403);
  $add=function($key,$name,$email,$audience,$basis)use($c){if(blank($email))return;$hash=hash('sha256',mb_strtolower(trim($email)));$c->invitations()->firstOrCreate(['email_hash'=>$hash],['source_key'=>$key,'name'=>$name,'email'=>$email,'audience'=>$audience,'contact_basis'=>$basis,'status'=>'draft']);};
  foreach(EventTicket::whereHas('plan',fn($q)=>$q->where('event_project_id',$c->event_project_id))->where('status','paid')->get() as $t)if($t->valid())$add('ticket-'.$t->id,$t->buyer_name,$t->email,$t->candidate_registration_id?'participant':'visitor','Billet confirmé de cette édition. Invitation unique de bilan, contact et notice à revoir avant envoi.');
  foreach(OutreachVisit::where('event_project_id',$c->event_project_id)->where('junta_status','agreed')->get() as $v)foreach(['president_email','treasurer_email'] as $field)$add('junta-'.$v->id.'-'.$field,$v->freguesia,$v->$field,'junta','Interlocuteur professionnel de la junta participante ; bilan du projet.');
  foreach(Distributor::whereHas('standPartner.stand',fn($q)=>$q->where('event_project_id',$c->event_project_id))->get() as $d)if($d->active())$add('relay-'.$d->id,$d->name,$d->email,'relay','Point-relais mandaté de cette édition ; bilan de la collaboration.');
  foreach(Sponsorship::where('event_project_id',$c->event_project_id)->with('prospect')->get() as $s)if($s->agreed())$add('sponsor-'.$s->id,$s->sponsor_name,$s->prospect?->email,'sponsor','Interlocuteur du sponsor conventionné ; bilan des prestations et de l’événement.');
  foreach(PressRelease::where('event_project_id',$c->event_project_id)->get() as $p)foreach($p->press_emails??[] as $email)$add('press-'.hash('sha256',$email),'Rédaction',$email,'press','Contact presse déjà choisi ; vérifier qu’il a effectivement couvert ou suivi cette édition.');
 }
 public function tick(): int {
  FeedbackInvitation::where('status','sending')->where('started_at','<',now()->subMinutes(5))->update(['status'=>'unknown','delivery_evidence'=>'Traitement interrompu ; vérifier avant nouvelle action.']);
  if(!config('promotion.external_enabled')||blank(config('promotion.account_evidence'))||!str_starts_with(config('app.url'),'https://')||in_array(config('mail.default'),['log','array',null]))return 0;
  $sent=0;foreach(FeedbackInvitation::where('status','ready')->orderBy('id')->limit(100)->get() as $row){
   $i=DB::transaction(function()use($row){$i=FeedbackInvitation::lockForUpdate()->findOrFail($row->id);$c=$i->campaign;if($i->status!=='ready'||!$c->accepting()||!$c->send_after||$c->send_after->isFuture()||blank($c->authorization)||blank($i->contact_basis)||blank($i->email)||$i->responded_at)return null;$body=$c->{'body_'.$i->locale}."\n\n".$i->url();$i->update(['status'=>'sending','body_snapshot'=>$body,'started_at'=>now()]);return $i;},3);
   if(!$i)continue;
   try {Mail::raw($i->body_snapshot,fn($m)=>$m->to($i->email)->subject($i->locale==='pt'?'Obrigado · Os Jogos do Agricultor':'Merci · Os Jogos do Agricultor'));$i->update(['status'=>'accepted','sent_at'=>now(),'delivery_evidence'=>'Accepté par le transport ; livraison non garantie.']);$sent++;}
   catch(\Throwable){$i->update(['status'=>'unknown','delivery_evidence'=>'Résultat incertain. Pas de nouvel envoi automatique.']);}
  }return $sent;
 }
}
