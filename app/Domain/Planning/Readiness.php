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
   $fundingScenarios=$project->scenarios->contains('launch_model',true)?$project->scenarios->where('launch_model',true):$project->scenarios;
   if (!$fundingScenarios->contains(fn($scenario)=>$scenario->launch_model?$scenario->report()['expansion_ready']:$scenario->report()['target_secured'])) $blockers[]='Aucun scénario ne couvre les coûts et l’objectif QAPAS par des engagements';
   foreach($project->activities()->where('status','approved')->get() as $activity) {
    $locations=$activity->locations()->with('siteFeature')->get();$performances=$locations->where('role','performance');$spectators=$locations->where('role','spectator');
    $geoZones=$performances->isNotEmpty()&&$spectators->isNotEmpty()&&$performances->every(fn($l)=>$l->siteFeature->category==='quartel'&&$l->siteFeature->access!=='public')&&$spectators->every(fn($l)=>$l->siteFeature->access==='public'&&!$performances->contains('site_feature_id',$l->site_feature_id));
    if($activity->risk_category==='machinery' && ($activity->access!=='qualified'||blank($activity->operator_requirements)||blank($activity->technical_review)||(!$geoZones&&(!$activity->terrace_id||$activity->map_x===null||!$activity->spectator_terrace_id||$activity->terrace_id===$activity->spectator_terrace_id||$activity->terrace?->access==='public'||$activity->spectatorTerrace?->access!=='public')))) $blockers[]=$activity->name.' : qualification, zones séparées et validation technique à compléter';
    if(in_array($activity->risk_category,['water','grafting'],true)&&blank($activity->technical_review)) $blockers[]=$activity->name.' : validation technique à compléter';
    if($activity->broadcast_planned&&blank($activity->media_plan)) $blockers[]=$activity->name.' : dispositif de captation à préparer';
   }
   if (!$project->runItems()->exists()) $blockers[]='Conducteur et responsables absents';
   if (!$project->activities()->where('track','official')->where('status','approved')->exists()) $blockers[]='Programme officiel non validé';
   if (!$project->activities()->where('track','public')->where('status','approved')->exists()) $blockers[]='Créneaux grand public non validés';
   if ($project->activities()->where('status','approved')->where(function($q){$q->where('risk_reviewed',false)->orWhereNull('risk_evidence')->orWhereNull('referee')->orWhere('capacity',0);})->exists()) $blockers[]='Activité validée sans sécurité, capacité ou responsable';
  }
  return $blockers;
 }
}
