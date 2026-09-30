<?php
namespace App\Http\Controllers;
use App\Models\{EventProject,EventTicket,PlantingSession,PlantingSlot};
use Illuminate\Http\Request;
class PlantingController {
 public function show(EventProject $project){abort_unless($project->is_public,404);$sessions=PlantingSession::where('event_project_id',$project->id)->where('is_public',true)->with('slots')->get();return view('public.planting',compact('project','sessions'));}
 public function reserve(Request $request,EventTicket $ticket){$d=$request->validate(['session'=>'required|uuid','public_name'=>'nullable|string|max:80','thanks'=>'nullable|accepted']);$session=PlantingSession::where('public_id',$d['session'])->firstOrFail();app(\App\Domain\Planting\Planting::class)->reserve($session,$ticket,$request->boolean('thanks')?($d['public_name']??null):null);return redirect()->to(\Illuminate\Support\Facades\URL::temporarySignedRoute('ticket.owner',now()->addDays(120),['ticket'=>$ticket->public_id]));}
 public function unlist(EventTicket $ticket,PlantingSlot $slot){abort_unless($slot->event_ticket_id===$ticket->id,404);$slot->update(['public_name'=>null,'thanks_consented_at'=>null]);return back();}
}
