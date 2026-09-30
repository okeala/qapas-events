<?php
namespace App\Domain\Planning;
use App\Models\Activity;
final class Revelation {
 public function confirmable(Activity $a): bool {
  if($a->status!=='approved'||!$a->risk_reviewed||blank($a->risk_evidence)||!$a->fundingScenario||!$a->fundingScenario->launch_model)return false;
  if(!$a->fundingScenario->includedActivities()->where('activities.id',$a->id)->exists())return false;
  return $a->fundingScenario->report()['launch_ready']&&app(Readiness::class)->blockers($a->eventProject,'live')===[];
 }
 public function label(Activity $a): string {return $a->publication_level==='confirmed'&&!$this->confirmable($a)?'review':$a->publication_level;}
}
