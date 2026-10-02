<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventBooking extends Model
{
    protected $guarded = [];

    protected $hidden = ['buyer_name', 'buyer_email', 'buyer_subject', 'agreement_reference', 'payment_reference'];

    protected function casts(): array
    {
        return ['buyer_name' => 'encrypted', 'buyer_email' => 'encrypted', 'offer_snapshot' => 'array', 'signed_at' => 'immutable_datetime', 'deadline' => 'immutable_datetime', 'paid_at' => 'immutable_datetime'];
    }

    public function event()
    {
        return $this->belongsTo(PlannerEvent::class, 'event_id');
    }

    public function offer()
    {
        return $this->belongsTo(EventOffer::class, 'offer_id');
    }

    public function refund()
    {
        return $this->hasOne(EventRefund::class, 'booking_id');
    }
}
