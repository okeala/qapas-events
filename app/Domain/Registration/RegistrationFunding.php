<?php
namespace App\Domain\Registration;
use App\Models\RegistrationCampaign;
use App\Domain\Finance\Money;
final class RegistrationFunding {
 public function report(RegistrationCampaign $campaign): array {
  $gross=0;$knownNet=0;$fees=0;$unpricedFees=0;$liability=0;$issued=0;$redeemed=0;
  foreach($campaign->registrations()->with(['drinkCredit','ticket'])->get() as $r){
   if(!$r->isPaid()||($r->ticket&&$r->ticket->benefits_snapshot!==null))continue;$gross+=$r->amount_cents;
   if($r->fee_cents===null)$unpricedFees++;else{$fees+=$r->fee_cents;$knownNet+=max(0,Money::net($r->amount_cents,$r->vat_basis_points)-$r->fee_cents-($r->ticket?->assignedCents()??0));}
   if($r->decision!=='selected')$liability+=$r->credit_cents;
   if($r->drinkCredit){$issued+=$r->drinkCredit->face_cents;$redeemed+=$r->drinkCredit->redeemed_cents;}
  }
  $available=$campaign->refund_reserve_cents===null||$campaign->bank_available_cents===null||blank($campaign->bank_evidence)?0:max(0,min($knownNet,$campaign->bank_available_cents)-$campaign->refund_reserve_cents);
  return ['gross_cents'=>$gross,'fees_cents'=>$fees,'fees_pending'=>$unpricedFees,'available_cents'=>$available,'potential_drink_credit_cents'=>$liability,'issued_cents'=>$issued,'redeemed_cents'=>$redeemed,'allocated_cents'=>$campaign->drinks_allocated_cents,'allocation_shortfall_cents'=>max(0,$campaign->drinks_allocated_cents-$available)];
 }
}
