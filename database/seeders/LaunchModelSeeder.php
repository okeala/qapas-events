<?php
namespace Database\Seeders;
use App\Models\{EventProject,Scenario};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class LaunchModelSeeder extends Seeder {
 public function run(): void {DB::transaction(function(){
  $p=EventProject::where('slug','os-jogos-do-agricultor')->first();if(!$p)return;
  $s=$p->scenarios()->firstOrCreate(['template_key'=>'launch-six-six'],['name'=>'Socle fondateur · 6 freguesias + 6 indépendants','launch_model'=>true,'team_target'=>6,'independent_target'=>6,'stand_target'=>12,'minimum_daily_activities'=>3,'assumptions'=>'Format de référence : six stands de freguesia parrainés et six indépendants. Trois relais maximum par freguesia. Terrain apporté par le porteur ; frais personnels à tracer ; rémunération et charges incluses, règlement différé. Au moins trois épreuves distinctes par jour. Durée, chapiteau, prix fondateurs, réserves et devis à renseigner. Bar et friterie QAPAS : recettes futures exclues du préfinancement.']);
  if(!$s->wasRecentlyCreated)return;
  if(blank($p->land_contribution))$p->update(['land_contribution'=>'Terrain annoncé mis à disposition par le porteur. Périmètre, durée, conditions et accord du site à documenter.']);
  $ids=[];
  foreach(['village','independent'] as $kind)for($i=1;$i<=6;$i++){
   $stand=$p->stands()->firstOrCreate(['template_key'=>$kind.'-'.$i],['name'=>($kind==='village'?'Freguesia ':'Indépendant ').$i.' · à mobiliser','kind'=>$kind,'structure'=>'provided','status'=>'proposed','needs'=>$kind==='village'?'Spécialité locale à définir ; équipe de vente distincte ou organisée pendant les épreuves. Aucun bar payant au stand.':'Offre QAPAS à chiffrer. Les marchandises, salariés et ventes de l’exposant restent sous sa responsabilité. Aucune commission QAPAS sur ses ventes.']);$ids[]=$stand->id;
   foreach([['Coût direct de l’offre QAPAS','cost',1],[$kind==='village'?'Parrain principal · contribution à définir':'Tarif fondateur indépendant · à calculer','revenue',1]] as [$name,$nature,$quantity])$s->budgetLines()->create(['stand_id'=>$stand->id,'scope'=>'stand','name'=>$stand->name.' / '.$name,'kind'=>$nature,'forecast_quantity'=>$quantity]);
   if($kind==='village')$s->budgetLines()->create(['stand_id'=>$stand->id,'scope'=>'stand','name'=>$stand->name.' / Relais sponsors facultatifs (0 à 3)','kind'=>'revenue','forecast_quantity'=>0,'evidence'=>'Aucune place de relais considérée vendue. Détailler par partenaire lors des accords.']);
  }
  $s->includedStands()->sync($ids);
  foreach(['Chapiteau : capacité, montage et démontage','Sanitaires, eau potable et déchets','Énergie, raccordements et éclairage','Organisation, accueil, secours et assurances','Communication et supports des relais','Régie, captation et écran commun','Installation et personnel du bar QAPAS','Installation et personnel de la friterie QAPAS'] as $name)$s->budgetLines()->create(['name'=>$name,'kind'=>'cost','scope'=>'common','forecast_quantity'=>1]);
  $s->budgetLines()->create(['name'=>'Terrain mis à disposition par le porteur','kind'=>'cost','scope'=>'common','unit_gross_cents'=>0,'vat_basis_points'=>0,'forecast_quantity'=>1,'evidence'=>'Mise à disposition annoncée ; validation juridique du site distincte.']);
  foreach(['bar'=>'Bar QAPAS','fries'=>'Friterie QAPAS'] as $scope=>$name)$s->budgetLines()->create(['name'=>$name.' · ventes estimées à renseigner','kind'=>'revenue','scope'=>$scope,'forecast_quantity'=>0]);
  $official=$p->activities()->whereNotNull('template_key')->get();$s->includedActivities()->sync($official->pluck('id')->all());
  foreach($official as $a)if($a->status==='idea')$a->update(['publication_level'=>$a->template_key==='omelete-retro'?'teaser':'hidden','funding_scenario_id'=>$s->id]);
  $steps=[
   ['frame','Fixer et chiffrer le format 6 + 6','price','Les douze unités stand peuvent couvrir le socle avant les ventes au public.','Obtenir les devis, le coût complet de rémunération et les conditions d’annulation.',[],'Le format prend forme'],
   ['juntas','Informer les juntas avant le lancement local','place','Les juntas peuvent désigner un interlocuteur et accueillir la démarche.','Préparer puis envoyer les courriers, consigner chaque réponse et demander un rendez-vous.',['frame'],'Les freguesias sont invitées'],
   ['team','Constituer le premier noyau local','product','Des volontaires peuvent porter l’équipe, sa spécialité et le stand.','Identifier joueurs, vente, référent, lieu et protocole du scrutin.',['juntas'],'Les équipes se préparent'],
   ['patron','Convaincre le premier parrain de stand','price','Un partenaire veut rendre possible la participation locale.','Présenter le stand, les contreparties, le prix et les conditions précises.',['frame'],'Les premiers parrains rejoignent le projet'],
   ['relays','Activer jusqu’à trois relais par freguesia','promotion','Des commerces volontaires peuvent financer et relayer la participation.','Documenter contribution, affichage, supports et orientation du public.',['team','patron'],'Les relais locaux entrent en action'],
   ['communication','Déployer la communication locale','promotion','La présence locale suscite des demandes et des soutiens.','Diffuser les supports et mesurer les demandes par origine.',['relays'],'La fête se prépare dans les villages'],
   ['funding','Sécuriser le socle et décider du lancement','price','Le format est réalisable avec les encaissements rapprochés.','Contrôler coûts, trésorerie, avances, rémunération, réserves et programme quotidien.',['team','patron','communication'],'Le premier format se finance'],
   ['growth','Ouvrir le palier suivant','product','Un agrandissement peut financer tous ses coûts supplémentaires.','Comparer un scénario complet au socle ; préserver l’objectif QAPAS et les capacités.',['funding'],'La fête pourra grandir'],
  ];$stepIds=[];
  foreach($steps as $order=>[$key,$name,$pillar,$hypothesis,$experiment,$deps,$label]){$idea=$p->ideas()->firstOrCreate(['template_key'=>$key],['name'=>$name,'pillar'=>$pillar,'hypothesis'=>$hypothesis,'experiment'=>$experiment,'depends_on'=>array_map(fn($d)=>$stepIds[$d],$deps),'sort_order'=>$order+1,'public_label'=>$label,'is_public'=>true,'status'=>'idea']);$stepIds[$key]=$idea->id;}
  // Preserve historical prices/edits but stop presenting the old starter offers as the current launch catalogue.
  $p->offers()->whereNull('template_key')->whereIn('name',['Stand indépendant · emplacement nu','Stand indépendant · structure comprise','Point-relais · première édition','Partenaire structurant','Soutenir un stand de freguesia'])->update(['is_public'=>false]);
  foreach([
   ['founder-stand','Stand indépendant fondateur','stand',6,true,'Structure et emplacement selon offre finalisée. Contribution aux frais communs. Tarif fondateur à calculer après devis.','Marchandises, personnel et recettes propres de l’exposant. Aucun bar payant au stand ; aucune commission QAPAS sur ses ventes.'],
   ['village-patron','Parrainer un stand de freguesia','village',6,false,'Un parrain principal par stand. Soutien au dispositif convenu et contreparties publicitaires documentées.','Aucune audience garantie ni transfert informel à une équipe.'],
   ['relay-sponsor','Devenir relais sponsor de sa freguesia','relay',18,false,'Trois places maximum par freguesia. Contribution au stand, affichage, supports et orientation du public selon mission définie.','Les trois relais ne sont pas présumés acquis. Un même paiement est affecté une seule fois.'],
   ['structural-partner','Partenaire de l’organisation générale','sponsor',0,false,'Prestations de visibilité et équipements communs sur proposition chiffrée.','Contreparties et contingent à définir ; aucun volume de visiteurs garanti.']
  ] as [$key,$name,$kind,$cap,$founder,$includes,$excludes])$p->offers()->firstOrCreate(['template_key'=>$key],['name'=>$name,'kind'=>$kind,'capacity'=>$cap,'is_founder'=>$founder,'includes'=>$includes,'excludes'=>$excludes,'delivery'=>'Proposition en préparation. Prix total TTC, calendrier et conditions communiqués avant tout engagement.','is_public'=>true]);
 });}
}
