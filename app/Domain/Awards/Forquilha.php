<?php
namespace App\Domain\Awards;
use App\Domain\Finance\{Money,Pricing};
use App\Models\{CommunityAward,EventProject,Sponsorship,Scenario,BudgetLine};
use Illuminate\Support\Facades\{DB,Validator};
use Illuminate\Validation\ValidationException;
final class Forquilha {
 public const PRIZE=50000;
 public function report(EventProject $project,?Scenario $scenario=null): array {
  $a=CommunityAward::where('event_project_id',$project->id)->first();$s=$a?->prizeSponsor;
  $net=$a?$this->receivedNet($a):0;
  $secured=$a&&$net>=self::PRIZE&&$a->reserved_at&&$a->reserved_at->lte(now())&&$a->reserved_by&&filled($a->reserve_evidence)&&hash_equals((string)$a->reserve_fingerprint,$this->fingerprint($a));
  $parts=[];$productionReady=true;
  foreach(['trophy_design','trophy_supply','trophy_paint'] as $purpose){$partner=Sponsorship::where('event_project_id',$project->id)->where('scope','award')->where('purpose',$purpose)->where('status','agreed')->first();$ready=$partner?->deliveryComplete()??false;$parts[$purpose]=['partner'=>$partner,'ready'=>$ready];$productionReady=$productionReady&&$ready;}
  $missing=[];if(!$secured)$missing[]='Forquilha de Ouro : 500 € encaissés nets d’IVA et réservés au prix avant ouverture des participations';
  if($scenario){$cost=$scenario->budgetLines->firstWhere('costing_key','community-christmas-prize');if(!$cost||$cost->kind!=='cost'||$cost->unit_gross_cents!==self::PRIZE||$cost->forecast_quantity!==1)$missing[]='Forquilha de Ouro : coût unique de 500 € absent de ce scénario';if($s?->budgetLine?->scenario_id!==$scenario->id)$missing[]='Forquilha de Ouro : recette du parrain à rapprocher dans ce scénario';foreach($parts as $purpose=>$part)if($part['partner']?->deliveryCostLine?->scenario_id!==$scenario->id)$missing[]='Forquilha de Ouro : coût et apport de « '.Sponsorship::AWARD_ROLES[$purpose].' » à documenter dans ce scénario';}
  return ['award'=>$a,'sponsor'=>$s,'required_cents'=>self::PRIZE,'received_net_cents'=>$net,'reserved_cents'=>$secured?self::PRIZE:0,'shortfall_cents'=>max(0,self::PRIZE-$net),'secured'=>(bool)$secured,'production_ready'=>$productionReady,'parts'=>$parts,'missing'=>$missing];
 }
 public function receivedNet(CommunityAward $a): int {
  $s=$a->prizeSponsor;$line=$s?->budgetLine;$cost=$a->budgetLine;
  if(!$s||$s->event_project_id!==$a->event_project_id||$s->scope!=='award'||$s->purpose!=='prize'||!$s->agreed()||!$line||$line->kind!=='revenue'||$line->scope!=='structural'||$line->superseded_by_id||!$line->verified()||$line->vat_basis_points===null||!$cost||$cost->superseded_by_id||$cost->kind!=='cost'||$cost->unit_gross_cents!==self::PRIZE||$cost->forecast_quantity!==1||$line->scenario_id!==$cost->scenario_id||$cost->scenario->event_project_id!==$a->event_project_id||$cost->scenario->is_archived)return 0;
  return min(Money::net($s->cash_pledged_cents??0,$line->vat_basis_points),Money::net($line->unit_gross_cents,$line->vat_basis_points)*$line->paid_quantity);
 }
 public function fingerprint(CommunityAward $a): string {
  $s=$a->prizeSponsor;$line=$s?->budgetLine;
  return hash('sha256',json_encode([$a->budget_line_id,$a->prize_sponsorship_id,$s?->only(['scope','purpose','status','sponsor_name','agreed_at','agreement_evidence','cash_pledged_cents','budget_line_id']),$line?->only(['scenario_id','kind','scope','unit_gross_cents','vat_basis_points','paid_quantity','receipt_reference','reconciled_at','reconciled_by','pricing_status','superseded_by_id'])]));
 }
 public function reserve(CommunityAward $award,string $evidence): void {
  abort_unless(auth('admin')->user()?->is_active,403);Validator::make(['evidence'=>$evidence],['evidence'=>'required|string|max:10000'])->validate();
  DB::transaction(function()use($award,$evidence){EventProject::whereKey($award->event_project_id)->lockForUpdate()->firstOrFail();$a=CommunityAward::lockForUpdate()->findOrFail($award->id);if($this->receivedNet($a)<self::PRIZE)throw ValidationException::withMessages(['evidence'=>'Accord seul insuffisant : rapprocher assez de fonds nets d’IVA sur la recette liée pour réserver 500 € complets.']);$a->update(['reserved_at'=>now(),'reserved_by'=>auth('admin')->id(),'reserve_evidence'=>$evidence,'reserve_fingerprint'=>$this->fingerprint($a)]);},3);
 }
}
