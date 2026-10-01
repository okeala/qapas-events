<?php
namespace App\Domain\Stands;
use App\Domain\Finance\Pricing;
use App\Models\{Stand,Scenario};
use Illuminate\Validation\ValidationException;
final class StandEconomics {
 public function report(Stand $stand,Scenario $scenario): array {
  if($scenario->event_project_id!==$stand->event_project_id||!$scenario->includedStands()->where('stands.id',$stand->id)->exists())throw ValidationException::withMessages(['scenario'=>'Ce stand n’appartient pas à ce scénario.']);
  $qapas=['cost'=>[],'revenue'=>[]];$reserved=[];
  foreach($stand->budgetLines()->where('scenario_id',$scenario->id)->get() as $line){
   if(!in_array($line->kind,['cost','revenue'],true)){$reserved[]=$line;continue;}
   $qapas[$line->kind][]=$line->forecast_quantity===0?0:Pricing::forecast($line,$line->forecast_quantity);
  }
  $parties=[];
  foreach($stand->externalLines()->where('scenario_id',$scenario->id)->get() as $line){$key=$line->party.'|'.$line->holder;if(!isset($parties[$key]))$parties[$key]=['holder'=>$line->holder,'party'=>$line->party,'cost'=>[],'revenue'=>[]];$parties[$key][$line->kind][]=$line->total();}
  $result=['scenario'=>$scenario->name,'qapas'=>$this->summarize($qapas),'external'=>[],'restricted_count'=>count($reserved)];
  foreach($parties as $party)$result['external'][]=['holder'=>$party['holder'],'party'=>$party['party']]+$this->summarize($party);
  return $result;
 }
 private function summarize(array $rows): array {
  $out=[];
  foreach(['cost','revenue'] as $kind){$values=$rows[$kind];$out[$kind.'_known_cents']=array_sum(array_filter($values,fn($v)=>$v!==null));$out[$kind.'_missing']=count(array_filter($values,fn($v)=>$v===null));$out[$kind.'_cents']=$out[$kind.'_missing']?null:$out[$kind.'_known_cents'];}
  $out['balance_cents']=$out['cost_cents']===null||$out['revenue_cents']===null?null:$out['revenue_cents']-$out['cost_cents'];return $out;
 }
}
