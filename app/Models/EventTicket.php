<?php
namespace App\Models;
class EventTicket extends Record {
 protected $hidden=['phone','email','terms_snapshot'];
 protected function casts(): array {return ['includes_admission'=>'boolean','benefits_snapshot'=>'array','phone'=>'encrypted','public_listing'=>'boolean','is_live'=>'boolean','listing_consented_at'=>'datetime','accepted_at'=>'datetime','cash_collected_at'=>'datetime','paid_at'=>'datetime','redeemed_at'=>'datetime','commission_paid_at'=>'datetime','amount_cents'=>'integer','fee_cents'=>'integer'];}
 protected static function booted(): void {parent::booted();static::updated(function(self $t){if($t->wasChanged('status')&&in_array($t->status,['refunded','cancelled']))PlantingSlot::where('event_ticket_id',$t->id)->where('status','reserved')->update(['event_ticket_id'=>null,'participant_name'=>null,'public_name'=>null,'thanks_consented_at'=>null,'status'=>'available']);});}
 public function plan(){return $this->belongsTo(PresalePlan::class,'presale_plan_id');}
 public function distributor(){return $this->belongsTo(Distributor::class);}
 public function stand(){return $this->belongsTo(Stand::class);}
 public function candidate(){return $this->belongsTo(CandidateRegistration::class,'candidate_registration_id');}
 public function settlement(){return $this->belongsTo(TicketSettlement::class,'ticket_settlement_id');}
 public function messages(){return $this->hasMany(TicketMessage::class);}
 public function benefitsUsed(): bool {return \Illuminate\Support\Facades\DB::table('ticket_benefit_redemptions')->where('event_ticket_id',$this->id)->exists()||PlantingSlot::where('event_ticket_id',$this->id)->where('status','planted')->exists();}
 public function benefitRemaining(int $index): int {return max(0,($this->benefits_snapshot[$index]['quantity']??0)-\Illuminate\Support\Facades\DB::table('ticket_benefit_redemptions')->where('event_ticket_id',$this->id)->where('benefit_index',$index)->sum('quantity'));}
 public function valid(): bool {return $this->status==='paid'&&$this->paid_at!==null&&$this->is_live===(bool)config('registration.live')&&(!$this->candidate_registration_id||($this->candidate?->isPaid()??false));}
 public function paidCommissionCents(): int {return $this->commission_retained_cents?:($this->commission_paid_at?$this->relay_cents:0);}
 public function assignedCents(): int {return $this->parking_cents+$this->relay_cents+$this->junta_cents+$this->village_cents;}
 public function expectedRemittance(): int {return $this->amount_cents-$this->commission_retained_cents;}
 public function verifyUrl(): string {return route('ticket.verify',['ticket'=>$this->public_id]);}
 public function qrSvg(): string {return (new \chillerlan\QRCode\QRCode(new \chillerlan\QRCode\QROptions(['outputType'=>\chillerlan\QRCode\Output\QROutputInterface::MARKUP_SVG,'outputBase64'=>false])))->render($this->verifyUrl());}
}
