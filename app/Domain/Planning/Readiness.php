<?php
namespace App\Domain\Planning;
use App\Models\EventProject;
final class Readiness {
 public function blockers(EventProject $project,string $action): array {
  $required=config('events.gates.'.$action);
  if (!is_array($required)) return ['Action inconnue'];
  $blockers=[];
  $records=$project->requirements()->get()->keyBy('code');
  foreach ($required as $code) {
   $item=$records->get($code);
   if (!$item || !($item->status==='approved' || ($item->status==='not_applicable' && in_array($code,['food','music'],true)))
     || blank($item->evidence) || blank($item->reviewed_by) || !$item->reviewed_at || $item->reviewed_at->isFuture()
     || ($item->expires_at && $item->expires_at->lt($project->ends_at?->copy()->startOfDay() ?? today()))) $blockers[]=config('events.requirements.'.$code,$code);
  }
  if (!$project->starts_at || !$project->ends_at || $project->ends_at<=$project->starts_at || blank($project->venue) || $project->capacity<1) $blockers[]='Date, lieu et capacité à confirmer';
  if ($action==='sales') $blockers[]='Contrats versionnés, facturation et paiement à intégrer (v0.2)';
  if ($action==='live') {
   $activeScenarios=$project->scenarios->where('is_archived',false);$fundingScenarios=$activeScenarios->contains('launch_model',true)?$activeScenarios->where('launch_model',true):$activeScenarios;
   foreach($project->teams as $team)if($team->status!=='elected'||!$team->composition()['complete'])$blockers[]=$team->name.' : treize rôles experts et choix local à compléter';
   if (!$fundingScenarios->contains(fn($scenario)=>$scenario->launch_model?$scenario->report()['expansion_ready']:$scenario->report()['target_secured'])) $blockers[]='Aucun scénario ne couvre les coûts et l’objectif QAPAS par des engagements';
   if($project->require_activity_trials)foreach($fundingScenarios->flatMap(fn($scenario)=>$scenario->programSlots)->map(fn($slot)=>$slot->activity)->filter()->unique('id') as $scheduled){if($scheduled->proposer_type==='organization'&&($scheduled->status!=='approved'||!$scheduled->preparation()->ready($scheduled)))$blockers[]=$scheduled->name.' : épreuve officielle programmée sans validation technique et répétition valides';}
   $operational=$project->activities()->where('status','approved')->get()->filter(fn($a)=>$a->participation_decision!=='stand_only'&&($a->proposer_type==='organization'||$a->preparation()->ready($a)));
   foreach($operational as $activity) {
    if(!$activity->preparation()->ready($activity)&&$activity->proposer_type==='organization')$blockers[]=$activity->name.' : répétition officielle à valider';
    if($activity->proposer_type!=='organization'&&!$activity->preparation()->ready($activity))continue;
    $locations=$activity->locations()->with('siteFeature')->get();$performances=$locations->where('role','performance');$spectators=$locations->where('role','spectator');
    $geoZones=$performances->isNotEmpty()&&$spectators->isNotEmpty()&&$performances->every(fn($l)=>$l->siteFeature->category==='quartel'&&$l->siteFeature->access!=='public')&&$spectators->every(fn($l)=>$l->siteFeature->access==='public'&&!$performances->contains('site_feature_id',$l->site_feature_id));
    if($performances->contains(fn($l)=>$l->geometry!==null)||$spectators->contains(fn($l)=>$l->geometry!==null)){
     $geoZones=$performances->isNotEmpty()&&$spectators->isNotEmpty()&&$performances->every(fn($l)=>$l->geometry!==null&&$l->access!=='public')&&$spectators->every(fn($l)=>$l->geometry!==null&&$l->access==='public');
     if($geoZones)foreach($performances as $performance)foreach($spectators as $spectator)if(GeoArea::overlaps($performance->geometry,$spectator->geometry))$geoZones=false;
    }
    if($activity->risk_category==='machinery' && ($activity->access!=='qualified'||blank($activity->operator_requirements)||blank($activity->technical_review)||(!$geoZones&&($locations->contains(fn($l)=>$l->geometry!==null)||!$activity->terrace_id||$activity->map_x===null||!$activity->spectator_terrace_id||$activity->terrace_id===$activity->spectator_terrace_id||$activity->terrace?->access==='public'||$activity->spectatorTerrace?->access!=='public')))) $blockers[]=$activity->name.' : qualification, zones séparées et validation technique à compléter';
    if(in_array($activity->risk_category,['water','grafting'],true)&&blank($activity->technical_review)) $blockers[]=$activity->name.' : validation technique à compléter';
    if($activity->broadcast_planned&&blank($activity->media_plan)) $blockers[]=$activity->name.' : dispositif de captation à préparer';
   }
   if (!$project->runItems()->exists()) $blockers[]='Conducteur et responsables absents';
   if (!$operational->contains('track','official')) $blockers[]='Programme officiel non validé';
   if (!$operational->contains('track','public')) $blockers[]='Créneaux grand public non validés';
   if ($operational->contains(fn($a)=>!$a->risk_reviewed||blank($a->risk_evidence)||blank($a->referee)||$a->capacity<1)) $blockers[]='Activité validée sans sécurité, capacité ou responsable';
  }
  return $blockers;
 }
}
