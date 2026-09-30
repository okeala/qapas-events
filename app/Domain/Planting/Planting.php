<?php
namespace App\Domain\Planting;
use App\Models\{PlantingSession,PlantingSlot,EventTicket};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
final class Planting {
 public function generate(PlantingSession $session): void {abort_unless(auth('admin')->user()?->is_active,403);DB::transaction(function()use($session){$s=PlantingSession::lockForUpdate()->findOrFail($session->id);for($i=1;$i<=$s->capacity;$i++)$s->slots()->firstOrCreate(['tree_code'=>sprintf('J%d-A%03d',$s->day_number,$i)]);},3);}
 public function reserve(PlantingSession $session,EventTicket $ticket,?string $publicName=null): PlantingSlot {
  return DB::transaction(function()use($session,$ticket,$publicName){$t=EventTicket::lockForUpdate()->findOrFail($ticket->id);$s=PlantingSession::lockForUpdate()->findOrFail($session->id);if(!$t->valid()||!$t->includes_admission||$t->plan->event_project_id!==$s->event_project_id||!$s->ready()||$s->starts_at->isPast()||!$s->is_public)throw ValidationException::withMessages(['planting'=>'Billet et créneau de plantation indisponibles.']);$existing=$s->slots()->where('event_ticket_id',$t->id)->first();if($existing)return $existing;$slot=$s->slots()->where('status','available')->orderBy('id')->lockForUpdate()->first();if(!$slot||$s->slots()->whereIn('status',['reserved','planted'])->count()>=min($s->capacity,$s->trees_received))throw ValidationException::withMessages(['planting'=>'Tous les arbres de cette session sont déjà réservés.']);$slot->update(['event_ticket_id'=>$t->id,'participant_name'=>$t->buyer_name,'public_name'=>$publicName,'thanks_consented_at'=>$publicName?now():null,'status'=>'reserved']);return $slot;},3);
 }
}
