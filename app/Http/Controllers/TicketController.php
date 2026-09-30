<?php
namespace App\Http\Controllers;
use App\Models\{EventProject,PresalePlan,Distributor,EventTicket};
use App\Domain\Tickets\{Ticketing,TicketPayments};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
class TicketController {
 private function privateView(string $view,array $data){return response()->view($view,$data)->header('Cache-Control','private, no-store')->header('Referrer-Policy','no-referrer')->header('X-Robots-Tag','noindex, nofollow');}
 public function show(Request $request,EventProject $project){abort_unless($project->is_public,404);$plan=PresalePlan::where('event_project_id',$project->id)->firstOrFail();$relay=null;if($request->filled('relay')){$relay=Distributor::where('public_id',$request->string('relay'))->firstOrFail();abort_unless($relay->active()&&$relay->standPartner->stand->event_project_id===$project->id,404);}return $this->form($request,$plan,$relay,false);}
 public function form(Request $request,PresalePlan $plan,?Distributor $relay,bool $cash){
  $token=(string)\Illuminate\Support\Str::uuid();$request->session()->put('ticket_forms.'.$token,['plan'=>$plan->id,'relay'=>$relay?->id,'cash'=>$cash]);
  $stands=$relay?collect([$relay->standPartner->stand]):$plan->eventProject->stands()->where('kind','village')->where('is_public',true)->where('status','!=','withdrawn')->get();
  return $this->privateView('public.presales',['project'=>$plan->eventProject,'plan'=>$plan,'relay'=>$relay,'cash'=>$cash,'stands'=>$stands,'token'=>$token,'open'=>$plan->is_open&&!$plan->blockers()&&($cash||app(TicketPayments::class)->configured())]);
 }
 public function store(Request $request,EventProject $project){
  abort_unless($project->is_public,404);$plan=PresalePlan::where('event_project_id',$project->id)->firstOrFail();$data=$this->validateInput($request);
  $form=$request->session()->get('ticket_forms.'.$data['request_id']);abort_unless($form&&$form['plan']===$plan->id,419);
  $relay=$form['relay']?Distributor::findOrFail($form['relay']):null;$cash=$form['cash'];if($cash)abort_unless(auth('relay')->id()===$relay?->id&&$relay->active(),403);
  if(!$cash)abort_unless(app(TicketPayments::class)->configured(),503);
  $data['channel']=$cash?'cash':'card';if($cash)$request->validate(['cash_received'=>'accepted']);
  $ticket=app(Ticketing::class)->issue($plan,$data,$relay);
  return redirect()->to(URL::temporarySignedRoute('ticket.owner',now()->addDays(120),['ticket'=>$ticket->public_id]));
 }
 private function validateInput(Request $request): array {return $request->validate(['request_id'=>'required|uuid','buyer_name'=>'required|string|max:120','email'=>'nullable|email:rfc|max:254','phone'=>['required','string','regex:/^\+[1-9][0-9]{7,14}$/'],'stand_id'=>'nullable|integer','terms_version'=>'required|string|max:100','candidate_terms_version'=>'nullable|string|max:100','candidate'=>'sometimes|boolean','expert_roles'=>'nullable|required_if:candidate,1|array|max:13','expert_roles.*'=>['string','distinct',Rule::in(\App\Domain\Teams\ExpertRoles::CODES)],'terms'=>'accepted','privacy'=>'accepted','public_listing'=>'sometimes|boolean','public_name'=>'nullable|required_if:public_listing,1|string|max:80','website'=>'nullable|string|max:0']);}
 public function verify(EventTicket $ticket){return $this->privateView('public.ticket',['ticket'=>$ticket,'owner'=>false]);}
 public function owner(EventTicket $ticket){return $this->privateView('public.ticket',['ticket'=>$ticket,'owner'=>true]);}
 public function pay(EventTicket $ticket){try{return redirect()->away(app(TicketPayments::class)->checkout($ticket));}catch(\Illuminate\Http\Client\ConnectionException|\Illuminate\Http\Client\RequestException){return back()->withErrors(['ticket'=>__('tickets.payment_unavailable')]);}}
 public function withdrawListing(EventTicket $ticket){$ticket->update(['public_listing'=>false,'public_name'=>null,'listing_consented_at'=>null]);return back()->with('ticket_notice',__('tickets.listing_removed'));}
 public function listing(EventProject $project){abort_unless($project->is_public,404);$plan=PresalePlan::where('event_project_id',$project->id)->firstOrFail();$names=$plan->tickets()->where('is_live',(bool)config('registration.live'))->where('status','paid')->whereNotNull('paid_at')->where('public_listing',true)->whereNotNull('listing_consented_at')->whereNotNull('public_name')->with('candidate')->orderBy('paid_at')->get()->filter(fn($t)=>$t->valid());return $this->privateView('public.ticket-list',compact('project','names'));}
 public function control(EventTicket $ticket){abort_unless(auth('admin')->user()?->is_active,403);return $this->privateView('public.ticket-control',compact('ticket'));}
 public function redeem(Request $request,EventTicket $ticket){abort_unless(auth('admin')->user()?->is_active,403);$data=$request->validate(['evidence'=>'required|string|max:2000']);app(Ticketing::class)->redeem($ticket,$data['evidence']);return back()->with('ticket_notice',__('tickets.checked'));}
}
