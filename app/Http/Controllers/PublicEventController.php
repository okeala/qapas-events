<?php
namespace App\Http\Controllers;
use App\Models\EventProject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class PublicEventController {
 public function home() {return view('public.home',['projects'=>EventProject::where('is_public',true)->get()]);}
 public function show(EventProject $project) {abort_unless($project->is_public,404);return $this->render($project);}
 public function preview(EventProject $project) {app(CatalogController::class)->authorizePreview();return $this->render($project,true);}
 private function render(EventProject $project,bool $preview=false) {
  $milestones=$project->ideas()->when(!$preview,fn($q)=>$q->where('is_public',true)->whereNotNull('public_label'))->orderBy('sort_order')->get();
  $view=view('public.event',['project'=>$project,'mobilizationCards'=>app(\App\Domain\Promotion\PublicMobilization::class)->cards($project,$milestones),'preview'=>$preview,'offers'=>$project->offers()->where($preview?'preview_current':'is_public',true)->when($project->sponsorship_policy_version,fn($q)=>$q->where('kind','!=','village'))->get(),'stands'=>$project->stands()->when(!$preview,fn($q)=>$q->where('is_public',true))->where('status','!=','withdrawn')->get(),'activities'=>$project->activities()->when(!$preview,fn($q)=>$q->where('is_public',true))->where('status','!=','archived')->orderBy('sort_order')->get()->filter(fn($a)=>$preview||$a->publicVisible()),'relayProgress'=>app(\App\Domain\Planning\RelayMobilization::class)->report($project),'press'=>$project->pressReleases()->orderByDesc('published_at')->get()->filter(fn($r)=>$r->publiclyAvailable()),'launchScenario'=>$project->launchScenario(),'geoPlan'=>app(\App\Domain\Planning\GeographicPlan::class)->data($project,$preview),'milestones'=>$project->ideas()->when(!$preview,fn($q)=>$q->where('is_public',true)->whereNotNull('public_label'))->orderBy('sort_order')->get(),'plan'=>app(\App\Domain\Planning\SitePlan::class)->data($project)]);
  return $preview?response($view)->header('Cache-Control','private, no-store')->header('X-Robots-Tag','noindex, nofollow'):$view;
 }
 public function interest(Request $request,EventProject $project) {
  abort_unless($project->is_public,404);
  abort_unless(config('events.privacy_ready') && filled(config('events.organizer_name')) && filter_var(config('events.contact_email'),FILTER_VALIDATE_EMAIL),503);
  $data=$request->validate([
   'profile'=>['required',Rule::in(['resident','team','junta','exhibitor','relay','sponsor','volunteer'])],
   'name'=>['required','string','max:120'],'email'=>['required','email:rfc','max:254'],
   'freguesia'=>['nullable','string','max:120'],'message'=>['nullable','string','max:3000'],
   'privacy'=>['accepted'],'marketing_opt_in'=>['sometimes','boolean'],
   'larger_tent_requested'=>['sometimes','boolean'],'extra_furniture_requested'=>['sometimes','boolean'],'expected_guests'=>['nullable','integer','between:1,10000'],
   'expert_roles'=>['nullable','array','max:14'],'expert_roles.*'=>['string','distinct',Rule::in(\App\Domain\Teams\ExpertRoles::CODES)],
   'website'=>['nullable','string','max:0'],
   'source'=>['nullable',Rule::in(['direct','junta','relay','field','social'])],
  ]);
  $project->interests()->create([
   'profile'=>$data['profile'],'name'=>$data['name'],'email'=>$data['email'],
   'expert_roles'=>$data['expert_roles']??[],'freguesia'=>$data['freguesia']??null,'message'=>$data['message']??null,
   'larger_tent_requested'=>$request->boolean('larger_tent_requested'),'extra_furniture_requested'=>$request->boolean('extra_furniture_requested'),'expected_guests'=>$data['expected_guests']??null,
   'marketing_opt_in'=>$request->boolean('marketing_opt_in'),
   'privacy_acknowledged_at'=>now(),'privacy_version'=>'2026-09-30-roles',
   'source'=>$data['source']??'direct',
  ]);
  return redirect()->route('event.show',['project'=>$project->slug])->with('received',true);
 }
}
