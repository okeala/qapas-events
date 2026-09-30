<?php
namespace App\Domain\Promotion;
use App\Models\{PressRelease,PublicationDispatch,Sponsorship,StandPartner};
use Illuminate\Support\Facades\{DB,Http,Mail};
final class PromotionPipeline {
 public function payload(PressRelease $p): array {
  $names=Sponsorship::whereIn('id',$p->sponsor_ids??[])->get()->filter(fn($s)=>$s->visible())->pluck('sponsor_name')->merge(StandPartner::whereIn('id',$p->relay_ids??[])->where('status','active')->pluck('name'))->unique()->values();
  $ack=$names->isNotEmpty()?"\n\nParceiros / parceiros locais: ".$names->implode(', '):'';$url=route('press.show',['project'=>$p->eventProject->slug,'press'=>$p->public_id]);
  return ['subject'=>$p->title_pt?:$p->name,'body'=>($p->body_pt?:$p->body).$ack,"social"=>trim(($p->social_text_pt?:($p->title_pt?:$p->name)).$ack."\n".$url),'url'=>$url,'fingerprint'=>hash('sha256',json_encode([$p->only(['name','title_pt','body','body_pt','social_text_pt','sponsor_ids','relay_ids','channels','press_emails','depends_on','dispatch_authorization','publication_version']),$names]))];
 }
 public function tick(): int {
  foreach(PressRelease::where('auto_dispatch',true)->whereIn('status',['approved','published'])->get() as $p){if(!$p->eventProject?->is_public||$p->publicationProblems())continue;if($p->target_at&&$p->target_at->isFuture())continue;$payload=$this->payload($p);foreach(array_unique($p->channels??[]) as $channel){$destinations=$channel==='email'?array_unique($p->press_emails??[]):['official'];foreach($destinations as $to)PublicationDispatch::firstOrCreate(['dedupe_key'=>hash('sha256',$p->id.'|'.$p->publication_version.'|'.$channel.'|'.mb_strtolower($to))],['press_release_id'=>$p->id,'version'=>$p->publication_version,'channel'=>$channel,'destination'=>$to,'payload'=>$payload]);}}
  PublicationDispatch::where('status','sending')->where('started_at','<',now()->subMinutes(5))->update(['status'=>'unknown','evidence'=>'Exécution interrompue : contrôler le prestataire avant toute action.']);$sent=0;
  foreach(PublicationDispatch::whereIn('status',['queued','blocked'])->orderByRaw("CASE WHEN channel = 'website' THEN 0 ELSE 1 END")->orderBy('id')->limit(100)->get() as $row)if($this->dispatch($row))$sent++;return $sent;
 }
 private function allowed(PublicationDispatch $d): bool {$p=$d->pressRelease;return $p&&$p->auto_dispatch&&in_array($p->status,['approved','published'])&&$p->eventProject?->is_public&&$d->version===$p->publication_version&&!$p->publicationProblems()&&(!$p->target_at||$p->target_at->lte(now()))&&($d->payload['fingerprint']??null)===$this->payload($p)['fingerprint'];}
 private function dispatch(PublicationDispatch $row): bool {
  $d=DB::transaction(function()use($row){$d=PublicationDispatch::lockForUpdate()->findOrFail($row->id);if(!in_array($d->status,['queued','blocked']))return null;if(!$this->allowed($d)){$d->update(['status'=>'blocked','evidence'=>'Jalon, texte, droits ou autorisation modifiés : revalidation requise.']);return null;}
   if($d->channel!=='website'){$reason=$this->configurationProblem($d);if($reason){$d->update(['status'=>'blocked','evidence'=>$reason]);return null;}}
   $d->update(['status'=>'sending','started_at'=>now()]);return $d;},3);if(!$d)return false;
  try {
   if(!$this->allowed($d)){$d->update(['status'=>'blocked','evidence'=>'Conditions révoquées avant diffusion.']);return false;}
   $ref=null;$state='accepted';
   if($d->channel==='website'){$p=$d->pressRelease;$p->update(['status'=>'published','is_public'=>true,'published_at'=>$p->published_at?:now()]);$ref=$d->payload['url'];$state='published';}
   elseif($d->channel==='email'){Mail::raw($d->payload['body']."\n\n".$d->payload['url'],fn($m)=>$m->to($d->destination)->subject($d->payload['subject']));$ref='mail-transport';}
   elseif($d->channel==='facebook'){$version=config('promotion.facebook_graph_version');$page=config('promotion.facebook_page_id');$identity=Http::withToken(config('promotion.facebook_page_token'))->timeout(15)->get('https://graph.facebook.com/'.$version.'/me',['fields'=>'id'])->throw()->json();if((string)($identity['id']??'')!==(string)$page)throw new \RuntimeException('account-mismatch');$response=Http::withToken(config('promotion.facebook_page_token'))->timeout(20)->post('https://graph.facebook.com/'.$version.'/'.$page.'/feed',['message'=>$d->payload['social']]);if(!$response->successful()){$d->update(['status'=>$response->serverError()?'unknown':'failed','evidence'=>'Facebook HTTP '.$response->status().' ; vérifier avant relance.']);return false;}$ref=$response->json('id');$state='published';}
   else {$identity=Http::withToken(config('promotion.x_user_token'))->timeout(15)->get('https://api.x.com/2/users/me')->throw()->json('data.id');if((string)$identity!==(string)config('promotion.x_user_id'))throw new \RuntimeException('account-mismatch');$response=Http::withToken(config('promotion.x_user_token'))->timeout(20)->post('https://api.x.com/2/tweets',['text'=>$d->payload['social']]);if(!$response->successful()){$d->update(['status'=>$response->serverError()?'unknown':'failed','evidence'=>'X HTTP '.$response->status().' ; vérifier avant relance.']);return false;}$ref=$response->json('data.id');$state='published';}
   if(blank($ref)){$d->update(['status'=>'unknown','evidence'=>'Réponse sans référence : vérifier le compte officiel, ne pas republier automatiquement.']);return false;}
   $d->update(['status'=>$state,'provider_reference'=>$ref,'sent_at'=>now(),'evidence'=>$state==='accepted'?'Accepté par le transport email ; livraison non garantie.':'Publication confirmée par le canal.']);return true;
  }catch(\Throwable){$d->update(['status'=>'unknown','evidence'=>'Résultat incertain ou identité non vérifiée. Contrôler le canal officiel avant toute nouvelle tentative. Aucun secret ni réponse brute conservé.']);return false;}
 }
 private function configurationProblem(PublicationDispatch $d): ?string {
  if(!config('promotion.external_enabled')||blank(config('promotion.account_evidence')))return 'Envois externes désactivés ou mandat des comptes officiels manquant.';
  if(!str_starts_with(config('app.url'),'https://')||!$d->pressRelease->publiclyAvailable())return 'Site HTTPS et communiqué public requis avant partage externe.';
  if($d->channel==='email'&&in_array(config('mail.default'),['log','array',null]))return 'Transport email réel non configuré.';
  if($d->channel==='facebook'&&(!preg_match('/^v[0-9]+\.[0-9]+$/',(string)config('promotion.facebook_graph_version'))||!preg_match('/^[0-9]+$/',(string)config('promotion.facebook_page_id'))||blank(config('promotion.facebook_page_token'))))return 'Page officielle Facebook, version Graph et jeton de Page requis.';
  if($d->channel==='x'&&(blank(config('promotion.x_user_token'))||blank(config('promotion.x_user_id'))||mb_strlen($d->payload['social'])>280))return 'Compte X, jeton utilisateur autorisé à écrire et message de 280 caractères maximum requis.';
  return null;
 }
 public function manual(PublicationDispatch $row,string $reference,string $evidence): void {abort_unless(auth('admin')->user()?->is_active,403);DB::transaction(function()use($row,$reference,$evidence){$d=PublicationDispatch::lockForUpdate()->findOrFail($row->id);if($d->channel==='website'||!$this->allowed($d)||in_array($d->status,['published','accepted','sending'])||blank($reference)||blank($evidence))throw \Illuminate\Validation\ValidationException::withMessages(['evidence'=>'Conditions valides, référence du canal et preuve de diffusion unique requises.']);$d->update(['status'=>'published','provider_reference'=>$reference,'evidence'=>$evidence,'sent_at'=>now()]);},3);}
}
