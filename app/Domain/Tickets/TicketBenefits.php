<?php
namespace App\Domain\Tickets;
use App\Models\EventTicket;
use Illuminate\Support\Facades\{DB,Validator};
use Illuminate\Validation\ValidationException;
final class TicketBenefits {
 public function redeem(EventTicket $ticket,int $index,int $quantity,string $evidence,string $operation): void {
  abort_unless(auth('admin')->user()?->is_active,403);Validator::make(compact('index','quantity','evidence','operation'),['index'=>'integer|min:0','quantity'=>'integer|between:1,100','evidence'=>'required|string|max:3000','operation'=>'required|uuid'])->validate();
  DB::transaction(function()use($ticket,$index,$quantity,$evidence,$operation){$t=EventTicket::lockForUpdate()->findOrFail($ticket->id);$prior=DB::table('ticket_benefit_redemptions')->where('operation_id',$operation)->first();if($prior){if($prior->event_ticket_id!==$t->id||$prior->benefit_index!==$index||$prior->quantity!==$quantity)throw ValidationException::withMessages(['benefit'=>'Opération déjà utilisée.']);return;}if(!$t->valid()||!isset($t->benefits_snapshot[$index])||$quantity>$t->benefitRemaining($index))throw ValidationException::withMessages(['benefit'=>'Billet ou solde d’avantage indisponible.']);DB::table('ticket_benefit_redemptions')->insert(['event_ticket_id'=>$t->id,'benefit_index'=>$index,'quantity'=>$quantity,'operation_id'=>$operation,'evidence'=>$evidence,'admin_id'=>auth('admin')->id(),'created_at'=>now()]);},3);
 }
}
