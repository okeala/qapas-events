<?php
namespace App\Http\Controllers;
use App\Models\{EventProject,Stand,Sponsorship};
use App\Domain\Promotion\StandSponsoring;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class SponsoringController {
 private function access(EventProject $p,bool $preview): void {if($preview)app(CatalogController::class)->authorizePreview();else abort_unless($p->is_public&&$p->sponsorship_visibility==='catalog',404);}
 private function stands(EventProject $p,bool $preview){return $p->stands()->where('kind','village')->where('status','!=','withdrawn')->when(!$preview,fn($q)=>$q->where('is_public',true))->orderBy('name')->get();}
 private function slots(EventProject $p,bool $preview){return Sponsorship::where('event_project_id',$p->id)->where('status','!=','cancelled')->when(!$preview,fn($q)=>$q->where('catalog_visible',true))->with(['activity','stand'])->get()->filter(fn($s)=>$preview||!$s->activity_id||$s->activity?->publicVisible());}
 public function index(Request $request,EventProject $project){return $this->render($request,$project,false);}
 public function preview(Request $request,EventProject $project){return $this->render($request,$project,true);}
 private function render(Request $request,EventProject $project,bool $preview){
  $this->access($project,$preview);$stands=$this->stands($project,$preview);$slots=$this->slots($project,$preview);
  $target=$request->query('target')==='event'?'event':'stand';$stand=$stands->firstWhere('public_id',$request->query('stand'));$slot=$slots->firstWhere('public_id',$request->query('slot'));
  if($request->filled('stand'))abort_unless($stand,404);if($request->filled('slot'))abort_unless($slot,404);
  $allocation=$stand?app(StandSponsoring::class)->report($stand):null;
  return response()->view('public.sponsoring',compact('project','preview','stands','slots','stand','slot','target','allocation'))->header('Cache-Control','private, no-store')->header('X-Robots-Tag',$preview?'noindex, nofollow':'index, follow');
 }
 public function store(Request $request,EventProject $project){
  $this->access($project,false);
  abort_unless(config('events.privacy_ready')&&filled(config('events.organizer_name'))&&filter_var(config('events.contact_email'),FILTER_VALIDATE_EMAIL),503);
  $data=$request->validate(['target'=>['required',Rule::in(['stand','event'])],'stand'=>['nullable','uuid'],'slot'=>['nullable','uuid'],'package'=>['nullable','required_if:target,stand','integer','between:1,5'],'name'=>['required','string','max:120'],'email'=>['required','email:rfc','max:254'],'message'=>['nullable','string','max:3000'],'privacy'=>['accepted'],'marketing_opt_in'=>['sometimes','boolean'],'website'=>['nullable','string','max:0']]);
  $selection=['target'=>$data['target']];
  if($data['target']==='stand'){
   $stand=$this->stands($project,false)->firstWhere('public_id',$data['stand']??'');abort_unless($stand,404);
   $units=(int)($data['package']??0);$exclusive=$units===5;$price=app(StandSponsoring::class)->quote($stand,$units,$exclusive);
   $selection+=['stand_public_id'=>$stand->public_id,'stand_name'=>$stand->name,'share_units'=>$units,'percentage'=>$units*20,'exclusive'=>$exclusive,'quoted_gross_cents'=>$price];
  }else{
   $slot=$this->slots($project,false)->firstWhere('public_id',$data['slot']??'');abort_unless($slot,404);
   if(!$slot->catalogAvailable())throw \Illuminate\Validation\ValidationException::withMessages(['slot'=>__('sponsoring.unavailable')]);
   $selection+=['slot_public_id'=>$slot->public_id,'scope'=>$slot->scope,'purpose'=>$slot->purpose,'quoted_gross_cents'=>$slot->catalog_price_cents,'stand_included'=>in_array($slot->scope,['main','secondary'],true)];
  }
  $project->interests()->create(['profile'=>'sponsor','name'=>$data['name'],'email'=>$data['email'],'message'=>$data['message']??null,'sponsorship_request'=>$selection,'marketing_opt_in'=>$request->boolean('marketing_opt_in'),'privacy_acknowledged_at'=>now(),'privacy_version'=>'2026-10-01-sponsorship','source'=>'direct']);
  return redirect()->route('sponsoring.index',['project'=>$project->slug,'target'=>$data['target']])->with('sponsorship_received',true);
 }
}
