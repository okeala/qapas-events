<?php
namespace Database\Seeders;
use App\Models\{EventProject,Team};
use App\Domain\Teams\ExpertRoles;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class ParticipantFurnitureAndRolesSeeder extends Seeder {
 public function run(): void {DB::transaction(function(){
  Team::each(fn($team)=>ExpertRoles::seedSlots($team));
  $p=EventProject::where('slug','os-jogos-do-agricultor')->first();if(!$p||$p->scenarios()->where('template_key','costing-rental-experts-v1')->exists())return;
  $old=$p->scenarios()->where('template_key','costing-two-days-v1')->first();if(!$old)return;
  $s=$old->replicate(['public_id','template_key','parent_id']);$s->fill(['template_key'=>'costing-rental-experts-v1','name'=>'Socle actuel · mobilier participant · équipes expertes','is_archived'=>false,'furniture_paid_by_participant'=>true,'costs_complete'=>false,'assumptions'=>'RÉVISION : mobilier à la charge des participants, location privilégiée auprès du loueur ; aucun ensemble table-bancs inclus ni fabrication Douglas QAPAS. Objectif maintenu : 24 personnes abritées / 12 assises, moitié de l’espace dédiée aux personnes debout et à la circulation. Location, livraison/reprise et caution à documenter par stand ; frais participant séparés du budget QAPAS. La provision de tente QAPAS est conservée : cette révision porte uniquement sur le mobilier. Les prix de stand restent des hypothèses TTC à contractualiser avec cette exclusion explicite. Treize rôles experts à couvrir par chaque équipe ; candidatures puis choix local. Durée, consommations et coûts restants repris comme hypothèses du scénario précédent. Aucun engagement ni paiement historique transposé ; revoir les obligations déjà prises dans les scénarios archivés avant toute nouvelle offre.']);$s->save();
  $s->includedStands()->sync($old->includedStands->pluck('id'));$s->includedActivities()->sync($old->includedActivities->pluck('id'));$s->includedFeatures()->sync($old->includedFeatures->pluck('id'));
  foreach($old->budgetLines as $line){
   if($line->costing_key==='furniture-manufacture'||in_array($line->name,['Ensemble six places : livraison, reprise et nettoyage','Ensemble six places supplémentaire'],true))continue;
   $copy=$line->replicate(['public_id','scenario_id']);$copy->fill(['committed_quantity'=>0,'paid_quantity'=>0,'reimbursed_cents'=>0,'receipt_reference'=>null,'reconciled_at'=>null,'reconciled_by'=>null,'paid_by'=>'qapas','pricing_status'=>$line->pricing_status==='reference'?'reference':'estimate','evidence'=>'Reprise de prévision uniquement ; accords, paiements et justificatifs restent dans le scénario précédent.']);$s->budgetLines()->save($copy);
  }
  foreach($old->programSlots as $slot){$copy=$slot->replicate(['public_id','scenario_id']);$s->programSlots()->save($copy);}
  foreach($s->includedStands as $stand)$stand->update(['included_furniture_sets'=>0,'extra_furniture_sets'=>0,'furniture_source'=>'rental','furniture_confirmed'=>false,'hospitality_validated'=>false]);
  foreach($s->includedActivities as $activity)if($activity->status==='idea'&&in_array($activity->fundingScenario?->template_key,['launch-six-six','launch-hospitality-v1','costing-two-days-v1'],true))$activity->update(['funding_scenario_id'=>$s->id]);
  $p->scenarios()->whereIn('template_key',['launch-six-six','launch-hospitality-v1','costing-two-days-v1'])->update(['is_archived'=>true]);
  $p->offers()->whereIn('template_key',['hospitality-independent','hospitality-patron'])->update(['is_public'=>false]);
  foreach([
   ['rental-independent','Stand indépendant fondateur · mobilier à votre charge','stand',6,true,'Emplacement et services QAPAS expressément décrits au devis. Objectif d’accueil à étudier : 24 personnes abritées, dont 12 assises.','Tout le mobilier, sa location privilégiée, sa livraison/reprise et sa caution restent à la charge de l’exposant. Aucun ensemble offert ou inclus. Abri et options suivant devis. Marchandises et personnel propres, sans bar payant, sandwich préparé ni transformation alimentaire.'],
   ['rental-patron','Parrainer une équipe de freguesia et son stand','village',6,false,'Stand de la freguesia soutenu et contreparties publicitaires définies avec QAPAS ; une équipe couvrant treize rôles experts, choisie localement parmi les volontaires.','Mobilier à organiser et financer par le participant, location privilégiée. Un soutien du parrain ou de la junta à la location doit être convenu : aucun apport gratuit n’est présumé. Pas de versement informel à l’équipe.'],
  ] as [$key,$name,$kind,$capacity,$founder,$includes,$excludes])$p->offers()->firstOrCreate(['template_key'=>$key],['name'=>$name,'kind'=>$kind,'capacity'=>$capacity,'is_founder'=>$founder,'includes'=>$includes,'excludes'=>$excludes,'delivery'=>'Demande de proposition sans commande ni réservation. Contrat de location mobilier auprès du loueur, frais et responsabilités présentés avant engagement.','is_public'=>true]);
  foreach($p->ideas()->where('template_key','hospitality-quotes')->where('status','idea')->whereNull('owner')->whereNull('evidence')->get() as $idea)$idea->update(['name'=>'Chiffrer abris, locations de mobilier, soupe et fournisseurs','experiment'=>'Devis tentes, location tables/assises avec frais participant, chef, brasseur, terrain et logistique. Fabrication Douglas écartée.']);
  foreach([
   ['rental-quote','Organiser la location du mobilier par les participants','price','Le mobilier n’immobilise plus la trésorerie QAPAS.','Comparer devis de location avec transport, montage, reprise, caution et conditions météo. Chaque participant confirme la fourniture et son financement.'],
   ['expert-recruitment','Recruter autour des treize rôles experts','product','Les savoir-faire, qualités de jeu, vente, récit et coordination fédèrent des habitants différents.','Recueillir candidatures consenties par rôle, vérifier les compétences, organiser le bulletin à la junta ou au lieu accepté, consigner le choix et étudier les cumuls éventuels.'],
  ] as [$key,$name,$pillar,$hypothesis,$experiment])$p->ideas()->firstOrCreate(['template_key'=>$key],['name'=>$name,'pillar'=>$pillar,'hypothesis'=>$hypothesis,'experiment'=>$experiment,'sort_order'=>$key==='rental-quote'?35:45,'depends_on'=>$p->ideas()->where('template_key',$key==='rental-quote'?'frame':'team')->pluck('id')->all(),'status'=>'idea','is_public'=>false]);
  $p->pressReleases()->whereIn('scenario_id',$p->scenarios()->where('is_archived',true)->pluck('id'))->where('status','draft')->update(['scenario_id'=>$s->id]);
 });}
}
