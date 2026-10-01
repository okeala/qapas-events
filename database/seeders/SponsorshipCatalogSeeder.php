<?php
namespace Database\Seeders;
use App\Models\{EventProject,CommercialPlan,Sponsorship,Stand,CabinProject};
use App\Domain\Finance\CommercialPricing;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class SponsorshipCatalogSeeder extends Seeder {
 public function run(): void {DB::transaction(function(){
  $p=EventProject::where('slug','os-jogos-do-agricultor')->first();$s=$p?->launchScenario();if(!$s||$p->sponsorship_policy_version)return;
  $p->update(['sponsorship_policy_version'=>'stand-shares-v1']);
  $plan=CommercialPlan::where('scenario_id',$s->id)->first();
  if($plan){
   // Replace only the previous untouched, unsigned automatic coverage proposal.
   foreach(app(CommercialPricing::class)->lines($plan) as $line)if($line->stand->kind==='village'&&!$line->committed_quantity&&!$line->paid_quantity&&$line->pricing_status==='estimate'&&str_starts_with($line->price_source??'','Proposition calculée 6 + 6'))$line->update(['unit_gross_cents'=>$plan->village_price_cents,'price_source'=>'Hypothèse commerciale de travail choisie dans le plan pour 100 % du parrainage du stand. Cinq parts égales. À tester et à confirmer ; ne prouve pas la couverture des coûts. Le solde de financement global reste visible.']);
   foreach($s->includedStands()->where('kind','village')->get() as $stand){if($stand->sponsorship_total_cents!==null)continue;$line=app(CommercialPricing::class)->lines($plan)->firstWhere('stand_id',$stand->id);$amount=($line&&($line->committed_quantity||$line->paid_quantity||$line->pricing_status==='confirmed'))?$line->unit_gross_cents:$plan->village_price_cents;if($amount&&$amount%5===0)$stand->update(['sponsorship_total_cents'=>$amount,'sponsorship_price_evidence'=>'Tarif de parrainage du stand, pas une dette de chaque équipe. Hypothèse commerciale distincte du besoin de couverture ; cinq parts égales, IVA incluse à qualifier. Accords existants conservés.']);}
   $offer=$p->offers()->where('template_key','rental-patron')->first();if($offer&&!$offer->is_public&&str_starts_with($offer->delivery??'','Tarif proposé selon le scénario 6 + 6'))$offer->update(['name'=>'Parrainer un stand de freguesia · exclusif ou partagé','price_gross_cents'=>$plan->village_price_cents,'summary'=>'Un stand commun aux équipes de la freguesia. Un parrain exclusif ou des parts de 20 %, avec visibilité proportionnelle.','includes'=>'Prix pour 100 % du parrainage du stand. Partage possible : 20 %, 40 %, 60 % ou 80 % ; le solde peut être pris par d’autres parrains. La part achetée détermine la surface dans la zone des parrains sur la signalétique du stand.','delivery'=>'Hypothèse à tester, devis et conditions de visibilité à confirmer. Ce montant n’est pas un prix par équipe ni par joueur. Le sponsoring n’inclut pas un stand d’exposition personnel pour le parrain local.']);
  }
  $structural=[];
  foreach(['event-main-v1','event-secondary-1','event-secondary-2','event-secondary-3'] as $key){
   $sp=Sponsorship::where('event_project_id',$p->id)->where('template_key',$key)->first();if(!$sp)continue;
   $stand=$sp->stand;if(!$stand){$stand=Stand::create(['event_project_id'=>$p->id,'name'=>'Stand · '.$sp->name,'kind'=>'sponsor','structure'=>'provided','status'=>'proposed','is_public'=>false,'needs'=>'Stand inclus dans le partenariat structurel. Dimensions, cabane, accueil, eau/électricité, accès, montage et démontage à préciser et chiffrer. Emplacement exact non attribué.','public_description'=>'Espace d’exposition du partenaire structurel, distinct des stands de freguesia et indépendants.']);$sp->update(['stand_id'=>$stand->id]);}
   $structural[]=$stand->id;
   $s->budgetLines()->firstOrCreate(['costing_key'=>'sponsor-stand-services-'.$stand->public_id],['name'=>'Stand sponsor : accueil, branchements et services · '.$stand->name,'kind'=>'cost','scope'=>'stand','stand_id'=>$stand->id,'unit'=>'lot','forecast_quantity'=>1,'pricing_status'=>'estimate','price_source'=>'À deviser : mobilier/accueil convenu, branchements, consommations et services propres à ce sponsor. Fabrication de la cabane et logistique mutualisée dans leurs postes existants ; ne pas les recompter ici. Le prix du partenariat inclut son stand ; aucune seconde recette de location.']);
  }
  $s->includedStands()->syncWithoutDetaching($structural);$s->update(['sponsor_stand_target'=>count($structural),'costs_complete'=>false]);
  $this->call(ReclaimedCabinsSeeder::class);
  foreach(CabinProject::whereIn('stand_id',$structural)->get() as $cabin)if($cabin->rental_pricing==='unpriced')$cabin->update(['rental_pricing'=>'included','rental_terms'=>'Cabane et emplacement inclus dans le partenariat structurel, périmètre/dimensions/services à convenir ; aucun supplément de location ajouté automatiquement.','summary_fr'=>'Stand du partenaire structurel, avec cabane prévue dans sa formule. Besoins et implantation à confirmer.','summary_pt'=>'Stand do parceiro estruturante, com cabana prevista na proposta. Necessidades e implantação por confirmar.']);
  foreach(Sponsorship::where('event_project_id',$p->id)->get() as $sp){
   $text=$sp->structural()?'Visibilité web selon le rang et stand d’exposition propre inclus. Dimensions, cabane, mobilier et raccordements à convenir ; coût réel d’installation à financer dans cette formule.':match($sp->scope){'activity'=>'Visibilité liée à l’épreuve et à son matériel, avec démonstration et images selon accord.','award'=>'Contribution identifiée au prix de 500 € ou au livrable du trophée. Affectation et contreparties distinctes.','welcome_pack'=>'Financement des t-shirts et marquage commun des acteurs des Jeux, quantités et BAT à convenir.',default=>'Visibilité sur les plaques et emplacements précisément convenus.'};
   $pt=$sp->structural()?'Visibilidade web conforme o nível e stand próprio incluído. Dimensões, cabana, mobiliário e ligações por acordar; o custo real da instalação integra esta proposta.':match($sp->scope){'activity'=>'Visibilidade associada à prova e ao seu equipamento, com demonstrações e imagens mediante acordo.','award'=>'Contribuição identificada para o prémio de 500 € ou para uma componente do troféu. Afetação e contrapartidas separadas.','welcome_pack'=>'Financiamento das t-shirts e imagem comum das pessoas em destaque, quantidades e prova gráfica por acordar.',default=>'Visibilidade nas placas e locais especificamente acordados.'};
   $sp->update(['catalog_visible'=>true,'catalog_price_cents'=>$sp->budgetLine?->unit_gross_cents,'catalog_fr'=>$text,'catalog_pt'=>$pt]);
  }
  foreach([
   ['stand-financing','Expliquer qui finance le stand de chaque freguesia','price','Séparer cabane construite par les habitants, parrainage QAPAS en cinq parts, aides de junta, participation des relais, affectations de billets et ventes propres. Prix commercial choisi, budget complet et financement manquant visibles ; aucune dette personnelle de l’équipe présumée.'],
   ['sponsor-stands','Prévoir les quatre stands des sponsors structurels','place','Un principal et trois secondaires, dont les gobelets : un stand chacun. 6 freguesias + 6 indépendants + 4 sponsors = 16 implantations si tous sont retenus. Chiffrer chaque installation dans sa formule, sans seconde vente du même emplacement ni double recette de location.'],
  ] as [$key,$name,$pillar,$experiment])$p->ideas()->firstOrCreate(['template_key'=>$key],compact('name','pillar','experiment')+['hypothesis'=>$name,'is_public'=>false,'depends_on'=>[]]);
  app(\App\Domain\Procurement\Consultations::class)->synchronize();
 });}
}
