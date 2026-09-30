<?php
namespace App\Http\Controllers;
use App\Models\{EventProject,ReportPoint,Incident,FeedbackInvitation,RallyStop,DrawingContest,EditorialPost};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class CommunityController {
 private function privateView($view,$data){return response()->view($view,$data)->header('Cache-Control','private, no-store')->header('X-Robots-Tag','noindex, nofollow')->header('Referrer-Policy','no-referrer');}
 public function previewRally(EventProject $project){app(CatalogController::class)->authorizePreview();return $this->privateView('public.rally',['project'=>$project,'stops'=>RallyStop::where('event_project_id',$project->id)->orderBy('sort_order')->get(),'contest'=>DrawingContest::where('event_project_id',$project->id)->first(),'preview'=>true]);}
 public function previewCommunity(EventProject $project){app(CatalogController::class)->authorizePreview();return $this->privateView('public.community',['project'=>$project,'award'=>\App\Models\CommunityAward::where('event_project_id',$project->id)->first(),'preview'=>true]);}
 public function previewBlog(EventProject $project){app(CatalogController::class)->authorizePreview();return $this->privateView('public.blog',['project'=>$project,'posts'=>EditorialPost::where('event_project_id',$project->id)->get(),'preview'=>true]);}
 public function previewArticle(EventProject $project,EditorialPost $post){app(CatalogController::class)->authorizePreview();abort_unless($post->event_project_id===$project->id,404);return $this->privateView('public.article',compact('project','post')+['preview'=>true]);}
 public function report(ReportPoint $point){abort_unless($point->is_active&&$point->eventProject?->is_public&&config('events.privacy_ready'),404);return $this->privateView('public.incident',compact('point'));}
 public function submitReport(Request $request,ReportPoint $point){
  abort_unless($point->is_active&&$point->eventProject?->is_public&&config('events.privacy_ready'),404);
  $d=$request->validate(['submission_id'=>'required|uuid','category'=>'required|in:water,power,waste,access,safety,other','report'=>'required|string|max:2000','urgent'=>'sometimes|boolean','privacy'=>'accepted','website'=>'nullable|string|max:0']);
  DB::transaction(function()use($point,$d){$p=ReportPoint::lockForUpdate()->findOrFail($point->id);abort_unless($p->is_active,404);$old=Incident::where('submission_id',$d['submission_id'])->first();if($old){abort_unless($old->report_point_id===$p->id,409);return;}Incident::create(['event_project_id'=>$p->event_project_id,'report_point_id'=>$p->id,'submission_id'=>$d['submission_id'],'source'=>'qr','name'=>$p->name.' · '.$d['category'],'report'=>$d['report'],'severity'=>!empty($d['urgent'])?'stop':'attention','status'=>'open','owner'=>'Permanence QAPAS · à affecter']);},3);
  return back()->with('received',true);
 }
 public function pointSheet(ReportPoint $point){abort_unless(auth('admin')->user()?->is_active,403);return $this->privateView('workspace.report-point',compact('point'));}
 public function sponsorSheet(\App\Models\Sponsorship $sponsorship){abort_unless(auth('admin')->user()?->is_active,403);abort_unless($sponsorship->scope==='signage'&&$sponsorship->agreed(),404);$features=\App\Models\SiteFeature::where('event_project_id',$sponsorship->event_project_id)->whereIn('id',$sponsorship->signage_feature_ids??[])->get();return $this->privateView('workspace.sponsor-plaque',compact('sponsorship','features'));}
 public function feedback(FeedbackInvitation $invitation){abort_unless($invitation->campaign->accepting()&&$invitation->status!=='cancelled',404);return $this->privateView('public.feedback',compact('invitation'));}
 public function saveFeedback(Request $request,FeedbackInvitation $invitation){
  abort_unless($invitation->campaign->accepting()&&$invitation->status!=='cancelled',404);
  $d=$request->validate(['rating'=>'required|integer|between:1,5','positive'=>'nullable|string|max:3000','improvements'=>'nullable|string|max:3000','privacy'=>'accepted','website'=>'nullable|string|max:0']);
  DB::transaction(function()use($invitation,$d){$i=FeedbackInvitation::lockForUpdate()->findOrFail($invitation->id);abort_unless($i->campaign->accepting()&&$i->status!=='cancelled',404);if(!$i->responded_at)$i->update(['rating'=>$d['rating'],'positive'=>$d['positive']??null,'improvements'=>$d['improvements']??null,'responded_at'=>now()]);},3);return back()->with('received',true);
 }
 public function rally(EventProject $project){abort_unless($project->is_public,404);$stops=RallyStop::where('event_project_id',$project->id)->orderBy('sort_order')->get()->filter(fn($s)=>$s->publishable());$contest=DrawingContest::where('event_project_id',$project->id)->first();return view('public.rally',compact('project','stops','contest'));}
 public function blog(EventProject $project){abort_unless($project->is_public,404);$posts=EditorialPost::where('event_project_id',$project->id)->orderByDesc('published_at')->get()->filter(fn($p)=>$p->visible());return view('public.blog',compact('project','posts'));}
 public function article(EventProject $project,EditorialPost $post){abort_unless($post->event_project_id===$project->id&&$post->visible(),404);return view('public.article',compact('project','post'));}
 public function commitments(EventProject $project){abort_unless($project->is_public,404);$award=\App\Models\CommunityAward::where('event_project_id',$project->id)->first();return view('public.community',compact('project','award'));}
}
