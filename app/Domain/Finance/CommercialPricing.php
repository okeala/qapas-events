<?php
namespace App\Domain\Finance;
use App\Models\{CommercialPlan,BudgetLine};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
final class CommercialPricing {
 public function lines(CommercialPlan $p){return $p->scenario->budgetLines()->where('kind','revenue')->where('scope','stand')->where('unit','contrat')->where('forecast_quantity',1)->whereHas('stand',fn($q)=>$q->whereIn('kind',['village','independent']))->with('stand')->get();}
 public function report(CommercialPlan $p): array {
  $s=$p->scenario;$unit=app(UnitCosting::class)->calculate($s);$lines=$this->lines($p);$villages=$lines->filter(fn($l)=>$l->stand->kind==='village');$independents=$lines->filter(fn($l)=>$l->stand->kind==='independent');$issues=[];
  if($s->is_archived||$villages->count()!==6||$independents->count()!==6||$lines->pluck('stand_id')->unique()->count()!==12||$s->includedStands->whereIn('kind',['village','independent'])->count()!==12||$lines->contains(fn($l)=>!$s->includedStands->contains('id',$l->stand_id)))$issues[]='Ce calcul exige six stands de village et six indépendants distincts, inclus dans ce scénario actif.';
  $fixed=0;$fixedWithoutSponsors=0;$fixedRows=[];
  foreach($s->budgetLines as $line){if($line->kind!=='revenue'||$lines->contains('id',$line->id)||in_array($line->scope,['bar','soup','fries','cups'],true)||!$line->forecast_quantity)continue;if($line->unit_gross_cents===null||$line->vat_basis_points===null){$issues[]='Recette non chiffrée exclue : '.$line->name;continue;}$net=Money::net($line->unit_gross_cents,$line->vat_basis_points)*$line->forecast_quantity;$fixed+=$net;if($line->scope!=='structural')$fixedWithoutSponsors+=$net;$fixedRows[]=['name'=>$line->name,'quantity'=>$line->forecast_quantity,'unit_gross_cents'=>$line->unit_gross_cents,'net_cents'=>$net,'verified'=>$line->verified()];}
  $independentNet=Money::net($p->independent_price_cents,$p->vat_basis_points)*$independents->count();
  // Full cash outlay, not depreciation; VAT credit and speculative bar/ticket takings do not pay suppliers.
  $outlay=$unit['known_outlay_cents'];$target=$s->target_surplus_cents*$s->months;$required=$outlay+$p->unknown_allowance_cents+$target;
  $price=function(int $amount)use($p,$villages){if(!$villages->count())return null;$net=(int)ceil(max(0,$amount)/$villages->count());$gross=(int)ceil($net*(10000+$p->vat_basis_points)/10000);return (int)(ceil($gross/$p->rounding_cents)*$p->rounding_cents);};
  $village=$price($required-$fixed-$independentNet);$zero=$price($outlay+$p->unknown_allowance_cents-$fixed-$independentNet);$noSponsors=$price($required-$fixedWithoutSponsors-$independentNet);$forecast=$fixed+$independentNet+Money::net($p->village_price_cents,$p->vat_basis_points)*$villages->count();
  return ['known_outlay_cents'=>$outlay,'organizer_floor_cents'=>OrganizerCost::amount($s),'unknown_allowance_cents'=>$p->unknown_allowance_cents,'target_cents'=>$target,'required_cents'=>$required,'fixed_net_cents'=>$fixed,'fixed_rows'=>$fixedRows,'independent_unit_cents'=>$p->independent_price_cents,'commercial_village_unit_cents'=>$p->village_price_cents,'village_unit_cents'=>$village,'village_break_even_cents'=>$zero,'village_without_sponsors_cents'=>$noSponsors,'projected_net_cents'=>$forecast,'headroom_cents'=>$forecast-$required,'issues'=>array_values(array_unique(array_merge($issues,$unit['report']['missing']))),'structural_issues'=>$issues,'forecast_only'=>true];
 }
 public function apply(CommercialPlan $plan,bool $seed=false): void {
  abort_unless(auth('admin')->user()?->is_active||($seed&&app()->runningInConsole()),403);
  DB::transaction(function()use($plan){$p=CommercialPlan::lockForUpdate()->findOrFail($plan->id);$p->scenario()->lockForUpdate()->firstOrFail();$report=$this->report($p);$lines=$this->lines($p);
   if($report['structural_issues'])throw ValidationException::withMessages(['price'=>implode(' ',$report['structural_issues'])]);
   if(\App\Models\StandPartner::whereIn('stand_id',$lines->pluck('stand_id'))->whereIn('status',['agreed','active'])->where(fn($q)=>$q->whereNotNull('main_slot')->orWhere('share_units','>',0))->exists())throw ValidationException::withMessages(['price'=>'Parrainage local déjà convenu : conserver le budget et les prix, préparer une proposition distincte.']);
   foreach($lines as $line){$l=BudgetLine::lockForUpdate()->findOrFail($line->id);if($l->committed_quantity||$l->paid_quantity||$l->pricing_status==='confirmed')throw ValidationException::withMessages(['price'=>'Contrat ou prix validé existant : établir une nouvelle offre, sans écraser les engagements.']);}
   foreach($lines as $line)$line->update(['unit_gross_cents'=>$line->stand->kind==='village'?$report['commercial_village_unit_cents']:$report['independent_unit_cents'],'vat_basis_points'=>$p->vat_basis_points,'pricing_status'=>'estimate','price_source'=>'Proposition calculée 6 + 6, prix TTC. IVA de simulation, coûts encore incomplets ; ni vente ni encaissement. Prix commercial choisi distinct du besoin de couverture calculé ; le solde non financé reste visible.','price_checked_at'=>today()]);
   foreach(['rental-independent'=>$report['independent_unit_cents'],'rental-patron'=>$report['commercial_village_unit_cents']] as $key=>$price){$offer=$p->scenario->eventProject->offers()->where('template_key',$key)->first();if($offer&&!$offer->is_public)$offer->update(['price_gross_cents'=>$price,'regular_price_gross_cents'=>null,'is_founder'=>false,'delivery'=>'Tarif proposé selon le scénario 6 + 6, à confirmer par devis et contrat versionné. Quantités, IVA, besoins techniques et frais à charge participant présentés avant engagement. Aucun paiement ouvert par ce calcul.']);}
   foreach($lines->filter(fn($l)=>$l->stand->kind==='village') as $line)$line->stand->update(['sponsorship_total_cents'=>$p->village_price_cents,'sponsorship_price_evidence'=>'Prix commercial choisi dans le scénario, distinct du besoin de couverture. Cinq parts de 20 %. Accords antérieurs à leur prix conservé ; aucun paiement présumé.']);
   $p->update(['applied_at'=>now()]);
  },3);
 }
}
