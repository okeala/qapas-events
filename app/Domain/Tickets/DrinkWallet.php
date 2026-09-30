<?php
namespace App\Domain\Tickets;
use App\Models\EventTicket;
use Illuminate\Support\Facades\{DB,Validator};
use Illuminate\Validation\ValidationException;
final class DrinkWallet {
 public function redeem(EventTicket $ticket,int $amount,string $evidence,string $operation): void {
  abort_unless(auth('admin')->user()?->is_active,403);
  Validator::make(compact('amount','evidence','operation'),['amount'=>'integer|between:1,1000','evidence'=>'required|string|max:2000','operation'=>'required|uuid'])->validate();
  DB::transaction(function()use($ticket,$amount,$evidence,$operation){
   $t=EventTicket::lockForUpdate()->findOrFail($ticket->id);
   $previous=DB::table('ticket_drink_redemptions')->where('operation_id',$operation)->first();
   if($previous){if($previous->event_ticket_id!==$t->id||$previous->amount_cents!==$amount)throw ValidationException::withMessages(['amount'=>'Référence déjà utilisée.']);return;}
   $event=$t->plan->eventProject;$local=now()->timezone('Europe/Lisbon');
   if(!$event->starts_at||!$event->ends_at||now()->lt($event->starts_at)||now()->gt($event->ends_at)||($event->community_version&&($local->hour<10||$local->hour>=22)))throw ValidationException::withMessages(['amount'=>'Les tickets-boissons sont utilisables pendant les Jeux, aux heures du bar.']);
   if(!$t->valid()||$amount>$t->drinkRemaining())throw ValidationException::withMessages(['amount'=>'Paiement non confirmé ou solde boissons insuffisant.']);
   DB::table('ticket_drink_redemptions')->insert(['event_ticket_id'=>$t->id,'operation_id'=>$operation,'amount_cents'=>$amount,'evidence'=>$evidence,'admin_id'=>auth('admin')->id(),'created_at'=>now()]);
  },3);
 }
}
