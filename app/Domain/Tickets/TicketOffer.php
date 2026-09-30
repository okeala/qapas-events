<?php
namespace App\Domain\Tickets;
use App\Models\PresalePlan;
final class TicketOffer {
 public function quote(PresalePlan $p,?\Carbon\CarbonInterface $at=null): array {
  $at=$at?:now();$early=$p->early_until&&$at->lte($p->early_until);$price=$p->price_cents;$relay=$p->relay_basis_points;
  if($p->sales_strategy==='presale_discount'&&!$early)$price=$p->full_price_cents??$p->price_cents;
  if($p->sales_strategy==='relay_stepdown'&&$p->relay_changes_at&&$at->gte($p->relay_changes_at))$relay=$p->late_relay_basis_points??$relay;
  $benefits=array_values([...($p->benefits??[]),...($early?($p->early_benefits??[]):[])]);
  $cost=$p->benefit_cost_cents;if($early&&count($p->early_benefits??[]))$cost=$cost!==null&&$p->early_benefit_cost_cents!==null?$cost+$p->early_benefit_cost_cents:null;
  $q=['price_cents'=>(int)$price,'relay_basis_points'=>(int)$relay,'phase'=>$early?'early':'standard','benefits'=>$benefits,'benefit_cost_cents'=>$cost];$q['key']=hash('sha256',json_encode([$p->terms_version,$q],JSON_UNESCAPED_UNICODE));return $q;
 }
 public function blockers(PresalePlan $p): array {
  if(!$p->unified_benefits)return [];$b=[];
  if($p->kind!=='admission')$b[]='Le ticket commun doit comprendre l’entrée';
  if(!count($p->benefits??[])||$p->benefit_cost_cents===null||blank($p->benefit_evidence))$b[]='Avantages garantis, coûts et preuve de fourniture à valider';
  if(count($p->early_benefits??[])&&(!$p->early_until||$p->early_benefit_cost_cents===null))$b[]='Fin et coût des bonus de prévente à préciser';
  if($p->sales_strategy==='presale_discount'&&(!$p->early_until||$p->full_price_cents<=$p->price_cents||blank($p->pricing_evidence)))$b[]='Prix plein supérieur, échéance et justification de remise requis';
  if($p->sales_strategy==='relay_stepdown'&&(!$p->relay_changes_at||$p->late_relay_basis_points===null||$p->late_relay_basis_points>=$p->relay_basis_points||blank($p->pricing_evidence)))$b[]='Baisse de commission, date et mandat commercial à documenter';
  return $b;
 }
}
