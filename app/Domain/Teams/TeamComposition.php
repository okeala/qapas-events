<?php
namespace App\Domain\Teams;
use App\Models\Team;
final class TeamComposition {
 public function report(Team $team): array {
  $assignments=$team->roleAssignments()->get()->keyBy('role_code');$covered=[];$missing=[];
  $required=$team->eventProject->community_version?ExpertRoles::CORE:array_values(array_diff(ExpertRoles::CODES,['musician']));
  foreach($required as $code){$slot=$assignments->get($code);if($slot?->isCovered())$covered[]=$slot;else $missing[]=$code;}
  $groups=collect($covered)->groupBy(fn($role)=>$role->interest_id?'interest-'.$role->interest_id:'register-'.$role->candidate_reference);$cumulations=$groups->filter(fn($roles)=>$roles->count()>1)->map(fn($roles)=>$roles->pluck('role_code')->all())->all();
  return ['required'=>count($required),'covered'=>count($covered),'missing'=>$missing,'people'=>$groups->count(),'cumulations'=>$cumulations,'complete'=>!$missing&&(!$cumulations||filled($team->role_cumulation_evidence))];
 }
}
