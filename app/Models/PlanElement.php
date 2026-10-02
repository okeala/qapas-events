<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PlanElement extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['geometry' => 'array', 'dimensions' => 'array', 'four_ps' => 'array', 'success' => 'array', 'public_content' => 'array', 'operations' => 'array', 'is_public' => 'boolean', 'is_essential' => 'boolean', 'archived_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $item) => $item->uuid ??= (string) Str::uuid());
    }

    public function scenario()
    {
        return $this->belongsTo(EventScenario::class, 'scenario_id');
    }

    public function offers()
    {
        return $this->hasMany(EventOffer::class, 'element_id');
    }

    public function budgetLines()
    {
        return $this->hasMany(EventBudgetLine::class, 'element_id');
    }
}
