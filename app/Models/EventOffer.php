<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EventOffer extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['content' => 'array', 'four_ps' => 'array', 'active' => 'boolean', 'is_public' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $offer) => $offer->uuid ??= (string) Str::uuid());
    }

    public function scenario()
    {
        return $this->belongsTo(EventScenario::class, 'scenario_id');
    }

    public function element()
    {
        return $this->belongsTo(PlanElement::class, 'element_id');
    }

    public function pool()
    {
        return $this->belongsTo(EventCapacityPool::class, 'pool_id');
    }

    public function bookings()
    {
        return $this->hasMany(EventBooking::class, 'offer_id');
    }

    public function available(): ?int
    {
        return $this->pool ? max(0, $this->pool->capacity - $this->pool->reserved) : null;
    }
}
