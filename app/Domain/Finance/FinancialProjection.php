<?php
namespace App\Domain\Finance;
use App\Models\{FinancialPlan,CommercialPlan};
use Illuminate\Support\Facades\Validator;
final class FinancialProjection {
 public static function portion(int $amount,int $bp): int {return intdiv($amount,10000)*$bp+intdiv(($amount%10000)*$bp+5000,10000);}
 public function allocate(int $amount,array $schedule,array $keys,string $fallback): array {
  $out=array_fill_keys($keys,0);if(!$schedule)$schedule=[['phase'=>$fallback,'share_basis_points'=>10000]];$left=$amount;
  foreach($schedule as $i=>$entry){$value=$i===count($schedule)-1?$left:intdiv($amount,10000)*(int)$entry['share_basis_points']+intdiv(($amount%10000)*(int)$entry['share_basis_points'],10000);$key=in_array($entry['phase'],$keys,true)?$entry['phase']:$fallback;$out[$key]+=$value;$left-=$value;}return $out;
 }
 private function tax(array $row,array $p,FinancialPlan $plan,int $gross,int $unit,int $qty,array &$issues): array {
  $mode=$p['vat_treatment'];if(isset($p['vat_fingerprint'])&&$p['vat_fingerprint']!==FinancialSources::taxFingerprint($row['source'],$row['source_type'])){$mode='pending';$p['deduction_basis_points']=0;$issues[]=$row['name'].' : nature ou taux modifié, revoir la qualification IVA';}$rate=$row['vat_rate'];$revenue=$row['kind']==='revenue';$out=0;$input=0;$net=$gross;$expense=$gross;
  if($mode==='pending'||blank($p['vat_evidence']??null))$issues[]=$row['name'].' : qualification IVA à confirmer';
  if($mode==='foreign'){$issues[]=$row['name'].' : TVA étrangère, facture / traitement intracommunautaire à examiner';if($revenue)$net=0;return compact('out','input','net','expense');}
  if(in_array($mode,['exempt','outside'],true)||($revenue&&$plan->vat_regime==='exempt'))return compact('out','input','net','expense');
  if($rate===null){$issues[]=$row['name'].' : taux IVA inconnu';if($revenue)$net=0;return compact('out','input','net','expense');}
  if(!in_array((int)$rate,[0,600,1300,2300],true)){$issues[]=$row['name'].' : taux hors grille continentale, aucune déduction automatique';if($revenue)$net=0;return compact('out','input','net','expense');}
  if($rate===0&&$mode!=='outside'&&$mode!=='exempt')$issues[]=$row['name'].' : taux zéro à justifier par son régime';
  if($mode==='reverse'){$out=self::portion($gross,(int)$rate);$input=$plan->vat_regime==='normal'?self::portion($out,(int)$p['deduction_basis_points']):0;$expense=$gross+$out-$input;return compact('out','input','net','expense');}
  $net=Money::net($unit,(int)$rate)*$qty;$tax=$gross-$net;
  if($revenue)$out=$tax;elseif($plan->vat_regime==='normal'&&$mode==='domestic')$input=self::portion($tax,(int)$p['deduction_basis_points']);
  $expense=$gross-$input;return compact('out','input','net','expense');
 }
 public function calculate(FinancialPlan $plan,array $variation=[]): array {
  $v=array_replace(['price'=>100,'volume'=>100,'fixed'=>100],$variation);Validator::make($v,['price'=>'integer|between:25,200','volume'=>'integer|between:0,200','fixed'=>'integer|between:25,200'])->validate();
  $plan->unsetRelation('scenario')->unsetRelation('profiles');$scenario=$plan->scenario;$service=app(FinancialSources::class);$sources=$service->rows($plan);$profiles=$plan->profiles->keyBy(fn($p)=>$p->source_type.':'.$p->source_id);$keys=array_keys($plan->phaseOptions());$first=$keys[0];$last=end($keys);$phases=[];
  foreach($plan->phases as $phase)$phases[$phase['key']]=$phase+array_fill_keys(['operating_in','operating_out','investment_out','financing_in','financing_out','restricted_in','restricted_out','vat_output','vat_input','vat_paid','receivables','payables','stock','customer_advances','supplier_advances','bfr','cash','free_cash','vat_reserved','restricted_balance'],0);
  $issues=[];$groups=[];$details=[];$fixedCosts=0;$variableCosts=0;$fixedRevenue=0;$variableRevenue=0;$capex=0;$vatOutput=0;$vatInput=0;$ownerDue=0;
  if($plan->vat_regime==='unknown')$issues[]='Régime TVA réel de QAPAS et périodicité non confirmés ; simulation conditionnelle.';
  if(!$scenario->costs_complete)$issues[]='Périmètre des coûts du scénario incomplet.';
  foreach($scenario->includedActivities as $activity){if(!$activity->materials_complete||$activity->materials->isEmpty())$issues[]=$activity->name.' : inventaire des épreuves incomplet';foreach($activity->materials as $m)if(filled($m->shared_cost_key)&&!$scenario->budgetLines->contains(fn($l)=>$l->costing_key===$m->shared_cost_key&&$l->kind==='cost'&&$l->forecast_quantity>0))$issues[]=$m->name.' : coût mutualisé absent du budget';}
  foreach($scenario->includedFeatures as $feature)if(!$feature->needs_complete||$feature->needs->isEmpty())$issues[]=$feature->name.' : besoins de site incomplets';
  if($scenario->organizer_full_monthly_cents===null)$issues[]='Coût complet du porteur à confirmer ; minimum des charges conservé.';
  foreach($sources as $row){
   $profile=$profiles->get($row['key']);$p=$profile?$profile->toArray():$service->defaults($row,$plan);$p+=['vat_evidence'=>null,'schedule_evidence'=>null,'depreciation_unit_cents'=>null];
   $qty=$row['quantity'];$driver=$p['driver'];if($driver!=='source'){
    if($row['locked']||$row['source_type']!=='budget'||$row['source']->stand_id){$issues[]=$row['name'].' : multiplicateur inapplicable à un poste lié ou engagé';}
    else $qty=$qty===null?null:$qty*match($driver){'stands'=>$scenario->includedStands->count(),'villages'=>$scenario->includedStands->where('kind','village')->count(),'independents'=>$scenario->includedStands->where('kind','independent')->count(),'sponsors'=>$scenario->includedStands->where('kind','sponsor')->count(),'days'=>$scenario->event_days??0,'tickets'=>$plan->ticket_target,'visitors'=>$plan->visitor_target,default=>1};
   }
   if($driver==='days'&&$scenario->event_days===null)$qty=null;
   if($qty===0)continue;
   if($qty===null||$row['unit_cents']===null){$issues[]=$row['name'].' : quantité ou prix à chiffrer';continue;}
   $unit=(int)$row['unit_cents'];if(!$row['locked']){if($row['kind']==='revenue')$unit=self::portion($unit,$v['price']*100);elseif($row['kind']==='cost'&&$p['behavior']==='fixed')$unit=self::portion($unit,$v['fixed']*100);}
   $gross=$unit*$qty;if($gross>1000000000000)throw \Illuminate\Validation\ValidationException::withMessages(['amount'=>'Projection par poste limitée à 10 milliards d’euros.']);
   if(!$row['priced'])$issues[]=$row['name'].' : prix prévisionnel non validé';
   if(blank($p['schedule_evidence']??null))$issues[]=$row['name'].' : échéancier proposé à confirmer';
   if($row['source_type']==='budget'&&$row['source']->stand_id&&!$scenario->includedStands->contains('id',$row['source']->stand_id))$issues[]=$row['name'].' : stand absent du scénario';
   $invoice=in_array($p['invoice_phase'],$keys,true)?$p['invoice_phase']:$first;$cash=$this->allocate($gross,$p['cash_schedule']??[],$keys,$invoice);
   if(!in_array($row['kind'],['cost','revenue'],true)){foreach($cash as $key=>$value)$phases[$key]['restricted_in']+=$value;continue;}
   $tax=$this->tax($row,$p,$plan,$gross,$unit,$qty,$issues);$expense=$tax['expense'];$revenue=$row['kind']==='revenue'?$tax['net']:0;
   if($row['investment']){if($p['depreciation_unit_cents']===null){$issues[]=$row['name'].' : charge d’amortissement non fixée, coût complet retenu par prudence';}else $expense=min($expense,self::portion($p['depreciation_unit_cents']*$qty,(!$row['locked']&&$p['behavior']==='fixed')?$v['fixed']*100:10000));}
   $factor=($p['behavior']==='variable'&&!$row['locked'])?$v['volume']*100:10000;
   // A volume sensitivity is a proportional sales mix, not a sale of fractional stands.
   $gross=self::portion($gross,$factor);$revenue=self::portion($revenue,$factor);$expense=self::portion($expense,$factor);foreach(['out','input'] as $field)$tax[$field]=self::portion($tax[$field],$factor);
   $cash=$this->allocate($gross,$p['cash_schedule']??[],$keys,$invoice);
   if($row['kind']==='revenue'){if($p['behavior']==='fixed'||$row['locked'])$fixedRevenue+=$revenue;else $variableRevenue+=$revenue;}
   else{if($p['behavior']==='fixed'||$row['locked'])$fixedCosts+=$expense;else $variableCosts+=$expense;if($row['investment'])$capex+=$gross;}
   if($row['source_type']==='budget'&&$row['source']->paid_by==='organizer')$ownerDue+=max(0,($row['source']->unit_gross_cents??0)*$row['source']->paid_quantity-$row['source']->reimbursed_cents);
   $vatOutput+=$tax['out'];$vatInput+=$tax['input'];$taxOut=$this->allocate($tax['out'],$p['vat_schedule']??[],$keys,$invoice);$taxIn=$this->allocate($tax['input'],$p['vat_schedule']??[],$keys,$invoice);
   $cumulative=0;$invoiceIndex=array_search($invoice,$keys,true);$consumeIndex=array_search($p['consumption_phase']??$last,$keys,true);if($consumeIndex===false)$consumeIndex=count($keys)-1;
   foreach($keys as $i=>$key){$value=$cash[$key];$cumulative+=$value;$phases[$key][$row['kind']==='revenue'?'operating_in':($row['investment']?'investment_out':'operating_out')]+=$value;$phases[$key]['vat_output']+=$taxOut[$key];$phases[$key]['vat_input']+=$taxIn[$key];
    if(!$row['investment']){if($row['kind']==='revenue'){$phases[$key]['receivables']+=max(0,($i>=$invoiceIndex?$gross:0)-$cumulative);$phases[$key]['customer_advances']+=max(0,$cumulative-($i>=$invoiceIndex?$gross:0));}
     else{$phases[$key]['payables']+=max(0,($i>=$invoiceIndex?$gross:0)-$cumulative);$phases[$key]['supplier_advances']+=max(0,$cumulative-($i>=$invoiceIndex?$gross:0));if($p['is_stock']&&$i>=$invoiceIndex&&$i<$consumeIndex)$phases[$key]['stock']+=$expense;}}
   }
   $category=$p['category'];$groups[$category]??=['label'=>\App\Models\FinancialProfile::CATEGORIES[$category],'revenue'=>0,'fixed'=>0,'variable'=>0,'investment'=>0];
   if($row['kind']==='revenue')$groups[$category]['revenue']+=$revenue;else $groups[$category][$p['behavior']==='variable'&&!$row['locked']?'variable':'fixed']+=$expense;if($row['investment'])$groups[$category]['investment']+=$gross;
   $details[]=['key'=>$row['key'],'name'=>$row['name'],'quantity'=>$qty,'unit_cents'=>$unit,'gross_cents'=>$gross,'net_revenue_cents'=>$revenue,'expense_cents'=>$row['kind']==='cost'?$expense:0,'vat_output'=>$tax['out'],'vat_input'=>$tax['input'],'locked'=>$row['locked']];
  }
  $organizer=OrganizerCost::amount($scenario);$fixedCosts+=$organizer;foreach($this->allocate($organizer,$plan->organizer_cash_schedule??[],$keys,$last) as $key=>$amount)$phases[$key]['operating_out']+=$amount;$groups['personnel']??=['label'=>'Personnel / porteur','revenue'=>0,'fixed'=>0,'variable'=>0,'investment'=>0];$groups['personnel']['fixed']+=$organizer;
  if($organizer&&(!$plan->organizer_cash_schedule||blank($plan->organizer_schedule_evidence)))$issues[]='Coût du porteur : échéancier à confirmer ; dernière phase retenue à défaut.';
  foreach($plan->financing??[] as $flow){$column=match($flow['kind']){'capital','loan','owner_advance'=>'financing_in','repayment','owner_repayment'=>'financing_out','restricted_in'=>'restricted_in','restricted_out'=>'restricted_out'};$phases[$flow['phase']][$column]+=(int)$flow['amount_cents'];}
  $commercial=CommercialPlan::where('scenario_id',$scenario->id)->first();$prudence=($scenario->contingency_cents??0);$buffer=$prudence+($scenario->refund_reserve_cents??0)+($commercial->unknown_allowance_cents??0)+$plan->minimum_cash_cents;
  $cash=(int)$plan->opening_cash_cents;$vatAccrued=(int)$plan->opening_vat_due_cents-(int)$plan->opening_vat_credit_cents;$vatPaid=0;$credit=(int)$plan->opening_vat_credit_cents;$restricted=0;$taxBuckets=array_fill_keys($keys,0);$taxBuckets[$first]+=$plan->opening_vat_due_cents;$peakBfr=0;$minFree=$cash-max(0,$vatAccrued)-$buffer;$curve=[];
  foreach($keys as $key){$phase=&$phases[$key];$vatDelta=$phase['vat_output']-$phase['vat_input'];$vatAccrued+=$vatDelta;$taxBuckets[$phase['vat_payment_phase']]+=$vatDelta;
   $settlement=$taxBuckets[$key]-$credit;$payment=max(0,$settlement);$credit=max(0,-$settlement);$vatPaid+=$payment;$phase['vat_paid']=$payment;
   $restricted+=$phase['restricted_in']-$phase['restricted_out'];if($restricted<0)$issues[]='Restitutions de cautions / fonds affectés supérieures aux fonds reçus.';
   $cash+=$phase['operating_in']+$phase['financing_in']+$phase['restricted_in']-$phase['operating_out']-$phase['investment_out']-$phase['financing_out']-$phase['restricted_out']-$payment;
   $phase['vat_reserved']=max(0,$vatAccrued-$vatPaid);$phase['cash']=$cash;$phase['restricted_balance']=max(0,$restricted);$phase['free_cash']=$cash-$phase['vat_reserved']-max(0,$restricted)-$buffer;
   $phase['bfr']=$phase['stock']+$phase['receivables']+$phase['supplier_advances']-$phase['payables']-$phase['customer_advances'];$peakBfr=max($peakBfr,$phase['bfr']);$minFree=min($minFree,$phase['free_cash']);unset($phase);
  }
  $margin=$variableRevenue-$variableCosts;$fixedGap=$fixedCosts-$fixedRevenue;$result=$fixedRevenue+$variableRevenue-$fixedCosts-$variableCosts;$target=$scenario->target_surplus_cents*$scenario->months;
  $factor=$fixedGap<=0?0:($margin>0?$fixedGap/$margin:null);$breakEven=$factor===null?null:(int)ceil($fixedRevenue+$variableRevenue*$factor);
  foreach([0,25,50,75,100,125,150,175,200] as $pct)$curve[]=['volume'=>$pct,'revenue'=>$fixedRevenue+self::portion($variableRevenue,$pct*100),'cost'=>$fixedCosts+self::portion($variableCosts,$pct*100),'target_cost'=>$fixedCosts+self::portion($variableCosts,$pct*100)+$target+$prudence];
  return ['variation'=>$v,'phases'=>array_values($phases),'groups'=>array_values($groups),'details'=>$details,'fixed_cost_cents'=>$fixedCosts,'variable_cost_cents'=>$variableCosts,'fixed_revenue_cents'=>$fixedRevenue,'variable_revenue_cents'=>$variableRevenue,'revenue_cents'=>$fixedRevenue+$variableRevenue,'contribution_cents'=>$margin,'result_cents'=>$result,'prudent_result_cents'=>$result-$prudence,'target_cents'=>$target,'result_gap_cents'=>max(0,-$result),'target_gap_cents'=>max(0,$target+$prudence-$result),'break_even_sales_cents'=>$breakEven,'break_even_volume_pct'=>$factor===null?null:round($factor*100,2),'curve'=>$curve,'investment_cash_cents'=>$capex,'vat_output_cents'=>$vatOutput,'vat_input_cents'=>$vatInput,'vat_paid_cents'=>$vatPaid,'vat_credit_cents'=>$credit,'reserve_cents'=>$buffer,'peak_bfr_cents'=>$peakBfr,'funding_need_cents'=>max(0,-$minFree),'closing_cash_cents'=>$cash,'owner_advance_due_cents'=>$ownerDue,'issues'=>array_values(array_unique($issues)),'complete'=>empty($issues),'forecast_only'=>true];
 }
}
