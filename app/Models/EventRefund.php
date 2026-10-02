<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventRefund extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function booking()
    {
        return $this->belongsTo(EventBooking::class, 'booking_id');
    }
}
