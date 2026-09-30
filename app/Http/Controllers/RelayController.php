<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\{PresalePlan,EventTicket};
class RelayController {
 private function relay(){ $d=auth('relay')->user();abort_unless($d&&$d->active(),403);return $d;}
 public function login(){return view('public.relay-login');}
 public function authenticate(Request $request){$data=$request->validate(['email'=>'required|email','password'=>'required|string']);if(!auth('relay')->attempt($data)){return back()->withErrors(['email'=>__('tickets.login_failed')])->onlyInput('email');}$request->session()->regenerate();if(!auth('relay')->user()->active()){auth('relay')->logout();abort(403);}return redirect()->route('relay.dashboard');}
 public function logout(Request $request){auth('relay')->logout();$request->session()->regenerateToken();return redirect()->route('relay.login');}
 public function dashboard(){ $relay=$this->relay();$plan=PresalePlan::where('event_project_id',$relay->standPartner->stand->event_project_id)->firstOrFail();$tickets=$relay->tickets()->where('is_live',(bool)config('registration.live'))->latest()->paginate(30);$pending=$relay->tickets()->where('is_live',(bool)config('registration.live'))->where('channel','cash')->where('status','pending')->get();return response()->view('public.relay-dashboard',compact('relay','plan','tickets','pending'))->header('Cache-Control','private, no-store')->header('X-Robots-Tag','noindex');}
 public function cash(Request $request){$relay=$this->relay();$plan=PresalePlan::where('event_project_id',$relay->standPartner->stand->event_project_id)->firstOrFail();return app(TicketController::class)->form($request,$plan,$relay,true);}
 public function receipt(EventTicket $ticket){$relay=$this->relay();abort_unless($ticket->distributor_id===$relay->id,404);return redirect()->to(\Illuminate\Support\Facades\URL::temporarySignedRoute('ticket.owner',now()->addHours(2),['ticket'=>$ticket->public_id]));}
}
