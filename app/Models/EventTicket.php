<?php
namespace App\Models;
class EventTicket extends Record {
 protected $hidden=['phone','email','terms_snapshot'];
 protected function casts(): array {return ['phone'=>'encrypted','public_listing'=>'boolean','is_live'=>'boolean','listing_consented_at'=>'datetime','accepted_at'=>'datetime','cash_collected_at'=>'datetime','paid_at'=>'datetime','redeemed_at'=>'datetime','amount_cents'=>'integer','fee_cents'=>'integer'];}
 public function plan(){return $this->belongsTo(PresalePlan::class,'presale_plan_id');}
 public function distributor(){return $this->belongsTo(Distributor::class);}
 public function stand(){return $this->belongsTo(Stand::class);}
 public function candidate(){return $this->belongsTo(CandidateRegistration::class,'candidate_registration_id');}
 public function settlement(){return $this->belongsTo(TicketSettlement::class,'ticket_settlement_id');}
 public function messages(){return $this->hasMany(TicketMessage::class);}
 public function valid(): bool {return $this->status==='paid'&&$this->paid_at!==null&&$this->is_live===(bool)config('registration.live')&&(!$this->candidate_registration_id||($this->candidate?->isPaid()??false));}
 public function assignedCents(): int {return $this->parking_cents+$this->relay_cents+$this->junta_cents+$this->village_cents;}
 public function expectedRemittance(): int {return $this->amount_cents-$this->commission_retained_cents;}
 public function verifyUrl(): string {return route('ticket.verify',['ticket'=>$this->public_id]);}
 public function qrSvg(): string {return (new \chillerlan\QRCode\QRCode(new \chillerlan\QRCode\QROptions(['outputType'=>\chillerlan\QRCode\Output\QROutputInterface::MARKUP_SVG,'outputBase64'=>false])))->render($this->verifyUrl());}
}
