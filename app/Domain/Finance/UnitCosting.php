<?php
namespace App\Domain\Finance;
use App\Models\Scenario;
final class UnitCosting {
 public function calculate(Scenario $scenario): array {
  $scenario->loadMissing(['budgetLines.stand','includedStands','includedActivities.materials','includedFeatures.needs','programSlots.activity']);
  $report=$scenario->report();$groups=[];$grossCost=0;$investment=0;$grossRevenue=0;$netRevenue=0;$missingPrices=[];
  foreach($scenario->budgetLines as $line){
   if($line->forecast_quantity===0)continue;
   if(!in_array($line->kind,['cost','revenue'],true))continue;
   $key=$line->stand_id?'stand-'.$line->stand_id:$line->scope;
   if(!isset($groups[$key]))$groups[$key]=['name'=>$line->stand?->name??(['common'=>'Frais communs','bar'=>'Boissons et vin chaud','cups'=>'Gobelets et circuit réutilisable','soup'=>'Soupe au chou','structural'=>'Partenariats généraux','fries'=>'Friterie historique'][$line->scope]??$line->scope),'stand'=>$line->stand,'cost'=>0,'revenue'=>0,'gross_revenue'=>0,'uncertain'=>false];
   $amount=Pricing::forecast($line,$line->forecast_quantity);$gross=($line->unit_gross_cents??0)*$line->forecast_quantity;
   if($line->unit_gross_cents===null)$missingPrices[]=$line->name;
   if($amount===null||Pricing::pending($line)||$line->vat_basis_points===null)$groups[$key]['uncertain']=true;
   if($line->kind==='cost'){$groups[$key]['cost']+=$amount??0;$grossCost+=$gross;if($line->expense_type==='investment')$investment+=$gross;}
   else{$groups[$key]['revenue']+=$amount??0;$groups[$key]['gross_revenue']+=$gross;$grossRevenue+=$gross;$netRevenue+=$amount??0;}
  }
  $activities=[];foreach($scenario->includedActivities as $activity){$cost=$activity->costReport();$activities[]=['activity'=>$activity,'cost'=>$cost];$grossCost+=$cost['gross_cents'];}
  $grossCost+=$report['site_gross_cents'];
  return ['groups'=>$groups,'activities'=>$activities,'report'=>$report,'gross_cost_cents'=>$grossCost,'investment_cents'=>$investment,'gross_revenue_cents'=>$grossRevenue,'net_revenue_cents'=>$netRevenue,'missing_prices'=>$missingPrices,'known_outlay_cents'=>$grossCost+($scenario->contingency_cents??0)+($scenario->refund_reserve_cents??0)+($scenario->organizer_full_monthly_cents??0)*$scenario->months];
 }
}
