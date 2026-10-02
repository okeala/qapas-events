<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PlannerEvent extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['description' => 'array', 'settings' => 'array', 'is_demo' => 'boolean', 'first_signed_at' => 'immutable_datetime',
            'decision_deadline' => 'immutable_datetime', 'decided_at' => 'immutable_datetime', 'starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $event) => $event->uuid ??= (string) Str::uuid());
    }

    public function scenarios(): HasMany
    {
        return $this->hasMany(EventScenario::class, 'event_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(EventBooking::class, 'event_id');
    }

    public function publications(): HasMany
    {
        return $this->hasMany(EventPublication::class, 'event_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(FourPReview::class, 'event_id');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(EventCampaign::class, 'event_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(EventLead::class, 'event_id');
    }

    public function overdue(): bool
    {
        return $this->decision === 'pending' && $this->decision_deadline && now()->greaterThanOrEqualTo($this->decision_deadline);
    }

    public function publicDecision(): string
    {
        return $this->overdue() ? 'not_confirmed' : $this->decision;
    }
}
