<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventBudgetLine extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'essential' => 'boolean', 'earmarked' => 'boolean', 'cash_due_at' => 'datetime'];
    }

    public function scenario()
    {
        return $this->belongsTo(EventScenario::class, 'scenario_id');
    }

    public function element()
    {
        return $this->belongsTo(PlanElement::class, 'element_id');
    }
}
