<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventCapacityPool extends Model
{
    protected $guarded = [];

    public function scenario()
    {
        return $this->belongsTo(EventScenario::class, 'scenario_id');
    }
}
