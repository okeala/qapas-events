<?php
namespace Database\Seeders;
use App\Models\{EventProject,FurniturePlan};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class CostingSeeder extends Seeder {
 public function run(): void {DB::transaction(function(){
  $p=EventProject::where('slug','os-jogos-do-agricultor')->first();if(!$p)return;
  // A separate, private working scenario. Never overwrite the operating plan or repeat estimates over edits.
  $s=$p->scenarios()->firstOrCreate(['template_key'=>'costing-two-days-v1'],[
   'name'=>'Chiffrage de travail · 6 + 6 · deux jours','launch_model'=>true,'shelter_model'=>'distributed','event_days'=>2,'minimum_daily_activities'=>3,'team_target'=>6,'independent_target'=>6,'stand_target'=>12,'guests_per_stand'=>24,'contingency_cents'=>100000,
   'assumptions'=>'SIMULATION NON VALIDÉE du 30/09/2026. Deux jours, trois épreuves officielles distinctes par jour, six passages par épreuve. 6 indépendants à 500 € TTC (emplacement inclus) ; hypothèse 6 parrains à 500 € TTC et 6 relais à 49,99 €, aucun acquis. Provision prudente de fourniture des abris ; peut être retirée seulement lorsque apport exposant ou prêt est convenu. Chaque stand : 24 abritées / 12 assises à étudier, un ensemble six places inclus, complément propre/prêté/optionnel. Fabrication du mobilier payée entièrement ici sans amortissement fictif ; temps du porteur dans sa rémunération complète encore inconnue. IVA 23 % simulée sur les lignes renseignées, déduction non présumée ; taux et exclusions à confirmer. Recettes boissons et soupe jamais utilisées comme préfinancement. Prix de travail, pas offres commerciales. Autres animations d’équipes encore inconnues : chiffrer avant incorporation. Réserve imprévus 1 000 € provisoire ; remboursements/annulation et rémunération restent à chiffrer.',
  ]);
  if(!$s->wasRecentlyCreated)return;
  $base=$p->scenarios()->where('template_key','launch-hospitality-v1')->firstOrFail();$stands=$base->includedStands;$s->includedStands()->sync($stands->pluck('id'));
  $source='Hypothèse interne, non devis, 30/09/2026. Prix TTC provisoire ; IVA de simulation 23 %, aucune déduction acquise. Aucun engagement ni paiement.';
  $line=function(array $data)use($s,$source){return $s->budgetLines()->create($data+['kind'=>'cost','scope'=>'common','unit'=>'forfait','forecast_quantity'=>1,'vat_basis_points'=>2300,'pricing_status'=>'estimate','price_checked_at'=>'2026-09-30','price_source'=>$source,'evidence'=>'Estimation de travail uniquement.']);};
  foreach($stands as $stand){
   $unit=['stand_id'=>$stand->id,'scope'=>'stand'];
   foreach([
    ['Abri du stand : provision fourniture deux jours',12000,'Fourniture QAPAS provisionnée par prudence. Pas de devis de location. Si apport exposant ou prêt documenté : passer quantité à zéro. 3 × 6 m est un repère, pas une capacité validée avec nos tables.'],
    ['Installation : marquage, attaches et consommables',2000,'Hors construction de cabane ou raccordement spécial. Besoins particuliers à ajouter à la fiche.'],
    ['Ensemble six places : livraison, reprise et nettoyage',2000,'Fabrication comptée une seule fois au budget commun. Ce coût couvre seulement le service du premier ensemble.'],
   ] as [$name,$amount,$note])$line($unit+['name'=>$name,'unit_gross_cents'=>$amount,'unit'=>'stand / événement','price_source'=>$source.' '.$note]);
   $line($unit+['name'=>$stand->kind==='village'?'Parrain principal du stand — prix à tester':'Formule fondateur indépendante — emplacement inclus','kind'=>'revenue','unit_gross_cents'=>50000,'unit'=>'contrat','price_source'=>$source.($stand->kind==='village'?' 500 € est une hypothèse à tester avec le parrain, pas un accord.':' Base 500 € TTC annoncée par le porteur ; périmètre final à contractualiser.')]);
   if($stand->kind==='village')$line($unit+['name'=>'Premier relais sponsor — prix à tester','kind'=>'revenue','unit_gross_cents'=>4999,'unit'=>'relais','price_source'=>$source.' Un seul relais envisagé ici ; jusqu’à trois au total. Supports comptés en communication commune. Deux relais supplémentaires ne sont pas présumés vendus.']);
   foreach([['Ensemble six places supplémentaire',5000],['Tente plus grande : supplément sur devis',null]] as [$name,$amount])$line($unit+['name'=>$name,'kind'=>'revenue','unit_gross_cents'=>$amount,'forecast_quantity'=>0,'unit'=>'option','price_source'=>$source.' Option inactive. Avant activation ajouter ses coûts de fourniture/service et vérifier disponibilité.']);
  }
  foreach(json_decode(file_get_contents(database_path('data/costing-budget-2026.json')),true,512,JSON_THROW_ON_ERROR) as $data)$line($data);
  $f=FurniturePlan::firstOrCreate(['event_project_id'=>$p->id,'template_key'=>'douglas-costing-v1'],[
   'name'=>'Douglas · simulation économique · 12 ensembles six places','parts'=>FurniturePlan::illustrativeParts(),'quantity'=>12,'wood_price_cents_m3'=>45000,'coverage_m2_litre'=>10,'coats'=>2,'hardware_cents_unit'=>2000,'roller_cents_batch'=>1500,'other_cents_batch'=>6000,'work_minutes_unit'=>150,'hourly_cents'=>1500,'service_cents_unit'=>2000,'vat_basis_points'=>2300,
   'evidence'=>'SIMULATION, pas devis scierie : Douglas 450 €/m³ TTC, 15 % pertes, quincaillerie 20 €/ensemble, protection 40 €/5 L (hypothèse utilisateur), 10 m²/L/couche et deux couches NON vérifiés sur fiche produit, rouleau 15 €/lot, autres consommables 60 €/lot. Débit et résistance à valider. Temps valorisé 150 minutes × 15 €/h pour mesurer le coût complet ; réalisé par le porteur, donc sa rémunération complète est renseignée une seule fois dans le scénario. Service 20 €/rotation et prix 50 € TTC à tester. Finition adaptée à l’usage mobilier et temps de séchage à vérifier.',
  ]);
  $r=$f->report();$line(['name'=>'Fabrication Douglas : matières pour douze ensembles six places','costing_key'=>'furniture-manufacture','expense_type'=>'investment','unit_gross_cents'=>$r['cash_batch_cents'],'price_source'=>$f->evidence.' Instantané initial du calcul mobilier ; réviser cette ligne après toute modification du plan de débit ou des prix. Aucun amortissement appliqué au préfinancement.']);
  // Only fill untouched inventories, never overwrite quantities, prices or a user's quotation.
  $materials=json_decode(file_get_contents(database_path('data/costing-materials-2026.json')),true,512,JSON_THROW_ON_ERROR);
  $activities=$base->includedActivities;$s->includedActivities()->sync($activities->pluck('id'));
  foreach($activities as $i=>$a){
   $untouched=$a->status==='idea'&&!$a->materials_complete&&$a->materials()->whereNotNull('unit_gross_cents')->doesntExist()&&$a->materials()->whereNotNull('evidence')->doesntExist();
   if($untouched&&$a->planned_runs===1){$a->update(['planned_runs'=>6]);foreach($materials as $data)if($data['activity_key']===$a->template_key){unset($data['activity_key']);$m=$a->materials()->where('name',$data['name'])->first();if($m)$m->update($data);}}
   if($i<6)$s->programSlots()->create(['activity_id'=>$a->id,'day_number'=>intdiv($i,3)+1,'time_label'=>'À planifier — hypothèse trois épreuves / jour']);
  }
  foreach([
   ['Mini-rétro : transport aller et reprise',2,15990,'trajet','Repère Rentapa : livraison à partir de 130 € HT / trajet. Deux trajets extrapolés à 159,90 € TTC chacun. Pas un devis pour Belmonte ; remplacement par prestataire local requis. https://rentapa.pt/maquinas/mini-escavadoras'],
   ['Engins : carburant et consommables',1,7000,'forfait','Provision interne, consommation à mesurer après essais.'],
   ['Tracteur / remorque : livraison et reprise',1,12000,'forfait','Provision interne, mise à disposition locale non acquise.'],
   ['Engins : adaptation, assurance et encadrement technique',1,null,'prestation','Le conducteur d’équipe ne remplace pas la supervision professionnelle ni les essais. Périmètre et coût à chiffrer.'],
   ['Répétitions, rechanges et essais hors compétition',1,15000,'enveloppe','Provision générale ; détailler les passages supplémentaires, consommables et usure avant confirmation.'],
   ['Animations inventées par les équipes et indépendants',1,null,'programme','Scénarios inconnus. Recenser besoins et répartition QAPAS/stand ; aucun coût ni bénévolat supposé nul.'],
  ] as [$name,$q,$price,$unit,$note])$line(['name'=>$name,'forecast_quantity'=>$q,'unit_gross_cents'=>$price,'unit'=>$unit,'price_source'=>$source.' '.$note]);
 });}
}
