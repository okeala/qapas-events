<?php
namespace App\Http\Controllers;
use App\Models\{EventProject,Offer,Stand,Activity};
class CatalogController {
 public function authorizePreview(): void {abort_unless(app()->environment('local'),404);abort_unless(auth('admin')->user()?->is_active,403);}
 private function render($view,$data,bool $preview){return response()->view($view,$data+['preview'=>$preview])->header('Cache-Control',$preview?'private, no-store':'no-cache')->header('X-Robots-Tag',$preview?'noindex, nofollow':'index, follow');}
 public function offer(EventProject $project,Offer $offer){if($offer->event_project_id===$project->id&&$offer->is_public&&$offer->kind==='village'&&$project->sponsorship_policy_version){abort_unless($project->is_public&&$project->sponsorship_visibility==='catalog',404);return redirect()->route('sponsoring.index',['project'=>$project->slug,'target'=>'stand']);}abort_unless($project->is_public&&$offer->event_project_id===$project->id&&$offer->is_public,404);return $this->render('public.offer',compact('project','offer'),false);}
 public function previewOffer(EventProject $project,Offer $offer){$this->authorizePreview();abort_unless($offer->event_project_id===$project->id&&$offer->preview_current,404);if($offer->kind==='village'&&$project->sponsorship_policy_version)return redirect()->route('sponsoring.preview',['project'=>$project->slug,'target'=>'stand']);return $this->render('public.offer',compact('project','offer'),true);}
 public function stand(EventProject $project,Stand $stand){abort_unless($project->is_public&&$stand->event_project_id===$project->id&&$stand->is_public&&$stand->status!=='withdrawn',404);return $this->render('public.stand',compact('project','stand'),false);}
 public function previewStand(EventProject $project,Stand $stand){$this->authorizePreview();abort_unless($stand->event_project_id===$project->id,404);return $this->render('public.stand',compact('project','stand'),true);}
 public function previewActivity(EventProject $project,Activity $activity){$this->authorizePreview();abort_unless($activity->event_project_id===$project->id,404);return $this->render('public.activity',['project'=>$project,'activity'=>$activity,'geoPlan'=>app(\App\Domain\Planning\GeographicPlan::class)->data($project,true),'plan'=>null],true);}
}
