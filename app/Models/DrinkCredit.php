<?php
namespace App\Models;
class DrinkCredit extends Record {
 public function registration(){return $this->belongsTo(CandidateRegistration::class,'candidate_registration_id');}
 public function availableCents(): int {return $this->registration?->isPaid()&&$this->registration->decision==='not_selected'?max(0,$this->face_cents-$this->redeemed_cents):0;}
}
