<?php
namespace App\Domain\Tickets;
use App\Models\TicketMessage;
use Illuminate\Support\Facades\{DB,Http};
final class TicketSms {
 public function dispatch(): int {
  if(!config('presales.sms_enabled')||!config('registration.live')||blank(config('presales.twilio_sid'))||blank(config('presales.twilio_token'))||blank(config('presales.twilio_from')))return 0;$count=0;
  foreach(TicketMessage::where('status','queued')->orderBy('id')->limit(100)->pluck('id') as $id){
   $m=DB::transaction(function()use($id){$m=TicketMessage::lockForUpdate()->find($id);if(!$m||$m->status!=='queued')return null;if(!$m->ticket->valid()||!$m->ticket->is_live){$m->update(['status'=>'cancelled']);return null;}$m->update(['status'=>'sending','attempted_at'=>now()]);return $m;});if(!$m)continue;
   try{$response=$this->client()->post($this->base().'/Messages.json',['From'=>config('presales.twilio_from'),'To'=>$m->ticket->phone,'Body'=>'QAPAS · pagamento recebido. Bilhete confirmado / paiement reçu. Verifique o QR / vérifiez : '.$m->ticket->verifyUrl()]);if($response->successful()&&preg_match('/^SM[a-fA-F0-9]{32}$/',(string)$response->json('sid'))){$m->update(['status'=>'accepted','provider_id'=>$response->json('sid')]);$count++;}else $m->update(['status'=>$response->serverError()?'unknown':'failed','error_code'=>(string)($response->json('code')?:$response->status())]);}
   catch(\Illuminate\Http\Client\ConnectionException){$m->update(['status'=>'unknown','error_code'=>'network_result_unknown']);}
  }
  // A transport timeout may have sent the SMS. Never retry automatically without reconciliation.
  TicketMessage::where('status','sending')->where('attempted_at','<',now()->subMinutes(10))->update(['status'=>'unknown','error_code'=>'worker_interrupted']);
  foreach(TicketMessage::where('status','accepted')->whereNotNull('provider_id')->limit(100)->get() as $m){try{$r=$this->client()->get($this->base().'/Messages/'.$m->provider_id.'.json');if($r->successful()&&$r->json('sid')===$m->provider_id){$s=$r->json('status');if($s==='delivered')$m->update(['status'=>'delivered','delivered_at'=>now()]);elseif(in_array($s,['failed','undelivered','canceled'],true))$m->update(['status'=>'failed','error_code'=>(string)$r->json('error_code')]);}}catch(\Illuminate\Http\Client\ConnectionException){}}
  return $count;
 }
 private function base(): string {return 'https://api.twilio.com/2010-04-01/Accounts/'.config('presales.twilio_sid');}
 private function client(){return Http::withBasicAuth(config('presales.twilio_sid'),config('presales.twilio_token'))->asForm()->connectTimeout(5)->timeout(15);}
}
