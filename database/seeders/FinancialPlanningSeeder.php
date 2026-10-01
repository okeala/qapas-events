<?php
namespace Database\Seeders;
use App\Models\{EventProject,FinancialPlan,CommercialPlan};
use App\Domain\Finance\CommercialPricing;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class FinancialPlanningSeeder extends Seeder {
 public function run(): void {DB::transaction(function(){
  $p=EventProject::where('slug','os-jogos-do-agricultor')->lockForUpdate()->first();$s=$p?->launchScenario();if(!$s||$p->financial_policy_version)return;
  FinancialPlan::firstOrCreate(['scenario_id'=>$s->id],['phases'=>FinancialPlan::phasesFor($s),'assumptions'=>'Prévision sur toute l’édition, solde initial avant la première phase. Calendrier indicatif à remplacer par les devis, acomptes et échéances fiscales réels. Aucun prix, régime IVA ni paiement n’est confirmé par cette initialisation.']);
  $commercial=CommercialPlan::where('scenario_id',$s->id)->first();
  if($commercial&&$commercial->village_price_cents===50000)$commercial->update(['village_price_cents'=>100000]);
  if($commercial&&$commercial->village_price_cents===100000){
   foreach(app(CommercialPricing::class)->lines($commercial) as $line){
    $stand=$line->stand;if($stand->kind!=='village'||$line->committed_quantity||$line->paid_quantity||$line->pricing_status!=='estimate'||$line->unit_gross_cents!==50000||!str_starts_with($line->price_source??'','Hypothèse commerciale de travail choisie'))continue;
    if($p->sponsorship_visibility==='catalog'||$stand->partners()->whereIn('status',['agreed','active'])->exists())continue;
    $line->update(['unit_gross_cents'=>100000,'price_source'=>'Hypothèse commerciale de travail choisie : 1 000 € TTC par stand de freguesia, financement collectif et sponsorisable en cinq parts de 200 €. Tarif de départ à tester ; aucun contrat ni encaissement créé.']);
    if($stand->sponsorship_total_cents===50000&&str_starts_with($stand->sponsorship_price_evidence??'','Tarif de parrainage du stand'))$stand->update(['sponsorship_total_cents'=>100000]);
   }
   $offer=$p->offers()->where('template_key','rental-patron')->first();if($offer&&!$offer->is_public&&$offer->price_gross_cents===50000&&str_starts_with($offer->delivery??'','Hypothèse à tester'))$offer->update(['price_gross_cents'=>100000]);
  }
  $p->update(['financial_policy_version'=>'scenario-cash-v1']);
 });}
}
