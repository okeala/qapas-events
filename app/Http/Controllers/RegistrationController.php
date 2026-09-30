<?php
namespace App\Http\Controllers;
use App\Models\{EventProject,RegistrationCampaign,CandidateRegistration};
use App\Domain\Registration\{RegistrationPayments,StripeGateway};
use App\Domain\Teams\ExpertRoles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,URL};
use Illuminate\Validation\{Rule,ValidationException};
class RegistrationController {
 public function show(EventProject $project){if(\App\Models\PresalePlan::where('event_project_id',$project->id)->exists())return redirect()->route('presales.show',['project'=>$project->slug,'candidate'=>1]);abort_unless($project->is_public,404);$campaign=$project->registrationCampaign;abort_unless($campaign,404);return view('public.registration',['project'=>$project,'campaign'=>$campaign,'open'=>$campaign->is_open&&!$campaign->blockers()]);}
 public function store(Request $request,EventProject $project){
  abort_if(\App\Models\PresalePlan::where('event_project_id',$project->id)->exists(),409,'Utiliser la prévente de cette édition.');
  abort_unless($project->is_public,404);
  $data=$request->validate(['name'=>'required|string|max:120','email'=>'required|email:rfc|max:254','freguesia'=>'required|string|max:120','expert_roles'=>'required|array|min:1|max:13','expert_roles.*'=>['required','string','distinct',Rule::in(ExpertRoles::CODES)],'terms_version'=>'required|string|max:100','terms'=>'accepted','privacy'=>'accepted','website'=>'nullable|string|max:0']);
  $registration=DB::transaction(function()use($data,$project,$request){
   $c=RegistrationCampaign::where('event_project_id',$project->id)->lockForUpdate()->firstOrFail();
   abort_unless($c->is_open&&!$c->blockers(),409);if($data['terms_version']!==$c->terms_version)throw ValidationException::withMessages(['terms'=>__('registration.changed')]);
   $key=mb_strtolower(trim($data['email']));$existing=$c->registrations()->where('email_key',$key)->first();
   if($existing){if($request->session()->get('registration_owner.'.$existing->public_id)===true&&$existing->payment_status==='pending')return $existing;throw ValidationException::withMessages(['email'=>__('registration.duplicate')]);}
   $i=$project->interests()->create(['profile'=>'team','name'=>$data['name'],'email'=>$key,'freguesia'=>$data['freguesia'],'expert_roles'=>$data['expert_roles'],'privacy_acknowledged_at'=>now(),'privacy_version'=>'2026-09-30-registration','marketing_opt_in'=>false,'source'=>'direct']);
   $locale=app()->getLocale()==='pt'?'pt':'fr';
   return $c->registrations()->create(['interest_id'=>$i->id,'is_live'=>(bool)config('registration.live'),'email_key'=>$key,'amount_cents'=>1000,'credit_cents'=>1000,'vat_basis_points'=>$c->vat_basis_points,'currency'=>'eur','terms_version'=>$c->terms_version,'terms_snapshot'=>"10 EUR TTC / IVA incluído · 10 EUR crédit boissons / crédito bebidas si non retenu / se não selecionado.\n".$c->{'terms_'.$locale}."\n".$c->{'refund_policy_'.$locale},'accepted_at'=>now()]);
  },3);
  $request->session()->put('registration_owner.'.$registration->public_id,true);
  return redirect()->to(URL::temporarySignedRoute('registration.status',$registration->accepted_at->copy()->addDays(30),['registration'=>$registration->public_id]));
 }
 public function status(CandidateRegistration $registration){return response()->view('public.registration-status',['registration'=>$registration,'checkoutUrl'=>URL::temporarySignedRoute('registration.pay',now()->addMinutes(30),['registration'=>$registration->public_id])])->header('Cache-Control','private, no-store')->header('Referrer-Policy','no-referrer')->header('X-Robots-Tag','noindex, nofollow');}
 public function pay(CandidateRegistration $registration){try{$url=app(RegistrationPayments::class)->checkout($registration);}catch(\Illuminate\Http\Client\RequestException|\Illuminate\Http\Client\ConnectionException $e){return back()->withErrors(['payment'=>__('registration.unavailable')]);}return redirect()->away($url);}
 public function webhook(Request $request){$event=app(StripeGateway::class)->verifyWebhook($request->getContent(),(string)$request->header('Stripe-Signature'));app(RegistrationPayments::class)->webhook($event);app(\App\Domain\Tickets\TicketPayments::class)->webhook($event);return response()->json(['received'=>true]);}
}
