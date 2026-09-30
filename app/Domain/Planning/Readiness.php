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
   if (!$project->scenarios->contains(fn($scenario)=>$scenario->report()['target_secured'])) $blockers[]='Aucun scénario ne couvre les coûts et l’objectif QAPAS par des engagements';
   if (!$project->runItems()->exists()) $blockers[]='Conducteur et responsables absents';
   if (!$project->activities()->where('track','official')->where('status','approved')->exists()) $blockers[]='Programme officiel non validé';
   if (!$project->activities()->where('track','public')->where('status','approved')->exists()) $blockers[]='Créneaux grand public non validés';
   if ($project->activities()->where('status','approved')->where(function($q){$q->where('risk_reviewed',false)->orWhereNull('risk_evidence')->orWhereNull('referee')->orWhere('capacity',0);})->exists()) $blockers[]='Activité validée sans sécurité, capacité ou responsable';
  }
  return $blockers;
 }
}
