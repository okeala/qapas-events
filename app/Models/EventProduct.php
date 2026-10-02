<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventProduct extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['content' => 'array', 'is_public' => 'boolean'];
    }

    public function scenario()
    {
        return $this->belongsTo(EventScenario::class, 'scenario_id');
    }

    public function movements()
    {
        return $this->hasMany(EventStockMovement::class, 'product_id');
    }

    public function locations()
    {
        return $this->hasMany(EventStockLocation::class, 'product_id');
    }
}
