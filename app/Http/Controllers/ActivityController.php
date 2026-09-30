<?php
namespace App\Http\Controllers;
use App\Models\{EventProject,Activity};
use App\Domain\Planning\SitePlan;
use Illuminate\Support\Facades\Storage;
class ActivityController {
 public function show(EventProject $project,Activity $activity){
  abort_unless($project->is_public&&$activity->event_project_id===$project->id&&$activity->publicVisible(),404);
  return view('public.activity',['project'=>$project,'activity'=>$activity,'plan'=>app(SitePlan::class)->data($project),'geoPlan'=>app(\App\Domain\Planning\GeographicPlan::class)->data($project)]);
 }
 public function image(EventProject $project){abort_unless($project->is_public&&$project->plan_is_public,404);return $this->imageResponse($project);}
 public function privateImage(EventProject $project){abort_unless(auth('admin')->user()?->is_active,403);return $this->imageResponse($project);}
 private function imageResponse(EventProject $project){
  abort_unless(app(SitePlan::class)->dimensions($project),404);
  return Storage::disk('local')->response($project->plan_image,null,['Content-Type'=>'image/png','X-Content-Type-Options'=>'nosniff','Cache-Control'=>'private, no-store']);
 }
}
