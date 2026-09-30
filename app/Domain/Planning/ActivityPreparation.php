<?php
namespace App\Domain\Planning;
use App\Models\{Activity,ActivityTrial};
final class ActivityPreparation {
 public function required(Activity $a): bool {return (bool)$a->eventProject->require_activity_trials;}
 public function fingerprint(Activity $a,string $stage): string {
  $data=$a->only(['rules','scoring','access','risk_category','operator_requirements','proposer_type']);
  if(in_array($stage,['site_installation','official_rehearsal'],true)){$data['position']=$a->only(['terrace_id','spectator_terrace_id','map_x','map_y']);$data['zones']=$a->locations()->with('siteFeature')->orderBy('id')->get()->map(fn($z)=>['zone'=>$z->only(['site_feature_id','role','geometry','access']),'quartel'=>$z->siteFeature?->only(['geometry','access'])])->all();}
  return hash('sha256',json_encode($data,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
 }
 public function validTrial(Activity $a,string $stage): ?ActivityTrial {return $a->trials()->where('stage',$stage)->where('result','passed')->where('protocol_hash',$this->fingerprint($a,$stage))->whereNotNull('reviewed_by')->where('performed_at','<=',now())->orderByDesc('performed_at')->get()->first(function($trial)use($a,$stage){if($a->eventProject->starts_at&&$trial->performed_at->gte($a->eventProject->starts_at))return false;if($stage!=='site_installation')return true;$start=$a->eventProject->starts_at;return $start&&$trial->performed_at->gte($start->copy()->subDays(7))&&$trial->performed_at->lt($start);});}
 public function ready(Activity $a): bool {
  if($a->participation_decision==='stand_only')return false;if(!$this->required($a))return true;
  if($a->proposer_type==='organization')return $this->validTrial($a,'official_rehearsal')!==null;
  $local=$this->validTrial($a,'local_test');$site=$this->validTrial($a,'site_installation');return $local&&$site&&$local->performed_at->lte($site->performed_at);
 }
 public function label(Activity $a): string {
  if($a->participation_decision==='stand_only')return 'Stand maintenu · défi retiré';if(!$this->required($a))return 'Protocole non activé pour cette édition';
  if($this->ready($a))return 'Essais requis validés';return $a->proposer_type==='organization'?'Répétition officielle à valider':($this->validTrial($a,'local_test')?'Installation et contrôle sur site à faire à J−7 / J−1':'Essai dans la freguesia à valider d’abord');
 }
 public function withdraw(Activity $a,string $reason): void {abort_unless(auth('admin')->user()?->is_active,403);if($a->proposer_type==='organization')throw \Illuminate\Validation\ValidationException::withMessages(['reason'=>'Le socle officiel exige une révision explicite du programme.']);\Illuminate\Support\Facades\Validator::make(['reason'=>$reason],['reason'=>'required|string|min:10|max:5000'])->validate();$a->update(['participation_decision'=>'stand_only','withdrawal_reason'=>$reason,'status'=>'suspended']);}
}
