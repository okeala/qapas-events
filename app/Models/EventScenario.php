<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventScenario extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(fn (self $scenario) => $scenario->uuid ??= (string) \Illuminate\Support\Str::uuid());
    }

    public function getRouteKeyName(): string { return 'uuid'; }

    protected function casts(): array
    {
        return ['prerequisites' => 'array', 'is_base' => 'boolean', 'profit_required' => 'boolean'];
    }

    public function event()
    {
        return $this->belongsTo(PlannerEvent::class, 'event_id');
    }

    public function elements()
    {
        return $this->hasMany(PlanElement::class, 'scenario_id');
    }

    public function offers()
    {
        return $this->hasMany(EventOffer::class, 'scenario_id');
    }

    public function budgetLines()
    {
        return $this->hasMany(EventBudgetLine::class, 'scenario_id');
    }

    public function products()
    {
        return $this->hasMany(EventProduct::class, 'scenario_id');
    }

    public function pools()
    {
        return $this->hasMany(EventCapacityPool::class, 'scenario_id');
    }
}
