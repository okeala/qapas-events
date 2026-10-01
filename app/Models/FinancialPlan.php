<?php
namespace App\Models;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
class FinancialPlan extends Record {
 protected $attributes=['vat_regime'=>'unknown','opening_cash_cents'=>0,'opening_vat_credit_cents'=>0,'opening_vat_due_cents'=>0,'minimum_cash_cents'=>0,'ticket_target'=>0,'visitor_target'=>0];
 protected function casts(): array {return ['phases'=>'array','financing'=>'array','organizer_cash_schedule'=>'array'];}
 public function scenario(){return $this->belongsTo(Scenario::class);}
 public function profiles(){return $this->hasMany(FinancialProfile::class);}
 public function phaseOptions(): array {return collect($this->phases)->pluck('label','key')->all();}
 public function report(array $variation=[]): array {return app(\App\Domain\Finance\FinancialProjection::class)->calculate($this,$variation);}
 public static function phasesFor(Scenario $scenario): array {
  $date=$scenario->eventProject->starts_at?->copy()->timezone('Europe/Lisbon')->startOfDay()??today()->addMonths(2);
  $starts=[$date->copy()->subMonths(3),$date->copy()->subMonths(2),$date->copy()->subWeeks(3),$date,$date->copy()->addDays(max(1,$scenario->event_days??2)),$date->copy()->addMonths(2)];
  $rows=[];foreach(['design'=>'Concevoir / consulter','mobilize'=>'Mobiliser / préventes','prepare'=>'Préparer / installer','live'=>'Événement','close'=>'Démonter / clôturer','tax'=>'Régularisation IVA'] as $key=>$label){$i=count($rows);$rows[]=['key'=>$key,'label'=>$label,'date'=>$starts[$i]->toDateString(),'vat_payment_phase'=>'tax'];}return $rows;
 }
 protected static function booted(): void {parent::booted();static::saving(function(self $p){
  Validator::make(['vat_regime'=>$p->vat_regime,'opening_cash_cents'=>$p->opening_cash_cents,'opening_vat_credit_cents'=>$p->opening_vat_credit_cents,'opening_vat_due_cents'=>$p->opening_vat_due_cents,'minimum_cash_cents'=>$p->minimum_cash_cents,'ticket_target'=>$p->ticket_target,'visitor_target'=>$p->visitor_target,'phases'=>$p->phases,'financing'=>$p->financing??[]],[
   'vat_regime'=>'in:unknown,normal,exempt','opening_cash_cents'=>'integer|between:-100000000000,100000000000','opening_vat_credit_cents'=>'integer|between:0,100000000000','opening_vat_due_cents'=>'integer|between:0,100000000000','minimum_cash_cents'=>'integer|between:0,100000000000','ticket_target'=>'integer|between:0,1000000','visitor_target'=>'integer|between:0,1000000',
   'phases'=>'required|array|min:2|max:24','phases.*.key'=>'required|regex:/^[a-z][a-z0-9_-]{0,39}$/|distinct','phases.*.label'=>'required|string|max:100','phases.*.date'=>'required|date_format:Y-m-d','phases.*.vat_payment_phase'=>'required|string',
   'financing'=>'array|max:100','financing.*.name'=>'required|string|max:120','financing.*.kind'=>'required|in:capital,loan,repayment,owner_advance,owner_repayment,restricted_in,restricted_out','financing.*.amount_cents'=>'required|integer|between:0,100000000000','financing.*.phase'=>'required|string',
  ])->validate();
  if($p->exists&&$p->isDirty('scenario_id'))throw ValidationException::withMessages(['scenario_id'=>'Conserver le scénario.']);
  $keys=array_column($p->phases,'key');$dates=array_column($p->phases,'date');
  foreach($p->phases as $i=>$phase)if(($i&&$dates[$i]<=$dates[$i-1])||!in_array($phase['vat_payment_phase'],$keys,true)||array_search($phase['vat_payment_phase'],$keys,true)<$i)throw ValidationException::withMessages(['phases'=>'Dates strictement croissantes ; versement IVA dans cette phase ou une phase ultérieure.']);
  foreach($p->financing??[] as $flow)if(!in_array($flow['phase'],$keys,true))throw ValidationException::withMessages(['financing'=>'Phase de financement absente.']);
  $schedule=$p->organizer_cash_schedule??[];Validator::make(['schedule'=>$schedule],['schedule'=>'array|max:24','schedule.*.phase'=>'required|string|distinct','schedule.*.share_basis_points'=>'required|integer|between:1,10000'])->validate();if($schedule&&(array_sum(array_column($schedule,'share_basis_points'))!==10000||array_diff(array_column($schedule,'phase'),$keys)))throw ValidationException::withMessages(['organizer_cash_schedule'=>'Répartir 100 % du coût du porteur dans les phases définies.']);
  if($p->vat_regime!=='unknown'&&blank($p->vat_evidence))throw ValidationException::withMessages(['vat_evidence'=>'Documenter régime et périodicité réels ; l’exonération ne se présume pas.']);
  if($p->exists&&$p->isDirty('phases'))foreach($p->profiles as $profile)foreach(array_merge([$profile->invoice_phase,$profile->consumption_phase],array_column($profile->cash_schedule??[],'phase'),array_column($profile->vat_schedule??[],'phase')) as $key)if($key&&!in_array($key,$keys,true))throw ValidationException::withMessages(['phases'=>'Une phase est encore utilisée par un poste : réaffecter ce poste avant suppression.']);
 });}
}
