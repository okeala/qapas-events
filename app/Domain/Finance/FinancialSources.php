<?php
namespace App\Domain\Finance;
use App\Models\{FinancialPlan,FinancialProfile,BudgetLine,ActivityMaterial,SiteNeed};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
final class FinancialSources {
 public static function taxFingerprint($source,string $type): string {return hash('sha256',json_encode([$type==='budget'?$source->kind:'cost',$source->vat_basis_points===null?null:(int)$source->vat_basis_points]));}
 public function resolve(FinancialProfile $profile){
  $scenario=$profile->financialPlan?->scenario;if(!$scenario)return null;
  return match($profile->source_type){
   'budget'=>BudgetLine::where('scenario_id',$scenario->id)->whereNull('superseded_by_id')->find($profile->source_id),
   'material'=>ActivityMaterial::whereIn('activity_id',$scenario->includedActivities()->select('activities.id'))->where(fn($q)=>$q->whereNull('shared_cost_key')->orWhere('shared_cost_key',''))->find($profile->source_id),
   'site'=>SiteNeed::whereIn('site_feature_id',$scenario->includedFeatures()->select('site_features.id'))->find($profile->source_id),default=>null};
 }
 public function rows(FinancialPlan $plan): array {
  $s=$plan->scenario;$s->loadMissing(['budgetLines.stand','includedStands','includedActivities.materials','includedFeatures.needs']);$rows=[];
  foreach($s->budgetLines as $line)$rows[]=$this->row('budget',$line,$plan);
  foreach($s->includedActivities as $activity)foreach($activity->materials as $m)if(blank($m->shared_cost_key))$rows[]=$this->row('material',$m,$plan,$activity->name,$m->basis==='per_run'?$activity->planned_runs:1);
  foreach($s->includedFeatures as $feature)foreach($feature->needs as $n)$rows[]=$this->row('site',$n,$plan,$feature->name,$n->basis==='per_day'?$s->event_days:1);
  return $rows;
 }
 private function row(string $type,$source,FinancialPlan $plan,?string $parent=null,?int $multiplier=1): array {
  $qty=$type==='budget'?$source->forecast_quantity:$source->quantity;
  return ['key'=>$type.':'.$source->id,'source_type'=>$type,'source_id'=>$source->id,'source'=>$source,'name'=>($parent?$parent.' · ':'').$source->name,'kind'=>$type==='budget'?$source->kind:'cost','quantity'=>$qty===null||$multiplier===null?null:$qty*$multiplier,'unit_cents'=>$source->unit_gross_cents,'vat_rate'=>$source->vat_basis_points,'investment'=>$type==='budget'&&$source->expense_type==='investment','locked'=>$type==='budget'&&($source->committed_quantity>0||$source->paid_quantity>0||$source->pricing_status==='confirmed'),'priced'=>!Pricing::pending($source)];
 }
 public function defaults(array $row,FinancialPlan $plan): array {
  $src=$row['source'];$scope=$src->scope??'common';$keys=array_keys($plan->phaseOptions());$phase=in_array($scope,['bar','soup','cups','fries'],true)?'live':($row['kind']==='revenue'?'mobilize':'prepare');if(!in_array($phase,$keys,true))$phase=$keys[0];
  $category=$row['investment']?'capex':match($scope){'structural'=>'sponsorship','bar','soup','cups','fries'=>$row['kind']==='revenue'?'sales':'supplies','stand'=>$row['kind']==='revenue'?'sales':'rentals',default=>$row['source_type']==='material'?'supplies':($row['source_type']==='site'?'utilities':'other')};
  $variable=$row['source_type']==='budget'?in_array($scope,['stand','bar','soup','cups','fries'],true):($src->basis!=='fixed');
  return ['name'=>$row['name'],'source_kind'=>$row['kind'],'category'=>$category,'behavior'=>$variable?'variable':'fixed','driver'=>'source','invoice_phase'=>$phase,'consumption_phase'=>in_array('live',$keys,true)?'live':$phase,'cash_schedule'=>[['phase'=>$phase,'share_basis_points'=>10000]],'vat_schedule'=>[['phase'=>$phase,'share_basis_points'=>10000]],'vat_treatment'=>'pending','deduction_basis_points'=>0,'is_stock'=>false];
 }
 public function synchronize(FinancialPlan $plan): void {
  abort_unless(auth('admin')->user()?->is_active||app()->runningInConsole(),403);
  foreach($this->rows($plan) as $row)$plan->profiles()->firstOrCreate(['source_type'=>$row['source_type'],'source_id'=>$row['source_id']],$this->defaults($row,$plan));
 }
 public function edit(FinancialProfile $profile,array $data): void {
  abort_unless(auth('admin')->user()?->is_active,403);
  DB::transaction(function()use($profile,$data){
   $profile=FinancialProfile::lockForUpdate()->findOrFail($profile->id);$source=$this->resolve($profile);if($source)$source=$source->newQuery()->lockForUpdate()->find($source->id);if(!$source)throw ValidationException::withMessages(['source'=>'Poste absent du scénario.']);
   $qty=$profile->source_type==='budget'?'forecast_quantity':'quantity';$values=['unit_gross_cents'=>$data['source_unit_cents']??null,'vat_basis_points'=>$data['source_vat_rate']??null,$qty=>$data['source_quantity']??null];
   $changed=collect($values)->contains(fn($value,$key)=>($source->$key===null?null:(int)$source->$key)!==($value===null?null:(int)$value));
   if($changed&&$profile->source_type==='budget'&&($source->committed_quantity||$source->paid_quantity||$source->pricing_status==='confirmed'))throw ValidationException::withMessages(['source_unit_cents'=>'Prix ou quantité engagés : conserver cet accord et créer une variante budgétaire.']);
   if($changed){$values['price_source']=$data['source_price_evidence']??null;$values['pricing_status']='estimate';$source->update($values);}
   $profile->update(collect($data)->except(['source_unit_cents','source_vat_rate','source_quantity','source_price_evidence'])->all());
  },3);
 }
}
