<?php
namespace App\Domain\Planning;
use App\Models\EventProject;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
final class PhaseTransition {
 public const PHASES=['concept','validation','preparation','ready','live','closed'];
 public function advance(EventProject $project): void {
  DB::transaction(function() use($project) {
   $project=EventProject::query()->lockForUpdate()->findOrFail($project->id);
   $index=array_search($project->phase,self::PHASES,true);
   if ($index===false || $index>=count(self::PHASES)-1) throw ValidationException::withMessages(['phase'=>'Aucune phase suivante.']);
   $next=self::PHASES[$index+1];
   $blockers=in_array($next,['ready','live'],true)?app(Readiness::class)->blockers($project,'live'):[];
   if ($blockers) throw ValidationException::withMessages(['phase'=>implode(' · ',$blockers)]);
   $project->update(['phase'=>$next]);
  });
 }
}
