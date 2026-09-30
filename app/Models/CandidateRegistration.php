<?php
namespace App\Models;
class CandidateRegistration extends Record {
 protected function casts(): array {return ['is_live'=>'boolean','amount_cents'=>'integer','credit_cents'=>'integer','vat_basis_points'=>'integer','refunded_cents'=>'integer','fee_cents'=>'integer','accepted_at'=>'datetime','paid_at'=>'datetime','decision_at'=>'datetime'];}
 public function campaign(){return $this->belongsTo(RegistrationCampaign::class,'registration_campaign_id');}
 public function interest(){return $this->belongsTo(Interest::class);}
 public function drinkCredit(){return $this->hasOne(DrinkCredit::class);}
 public function isPaid(): bool {return $this->is_live===(bool)config('registration.live')&&$this->payment_status==='paid'&&$this->paid_at!==null&&$this->amount_cents===1000&&$this->currency==='eur'&&$this->refunded_cents===0;}
}
