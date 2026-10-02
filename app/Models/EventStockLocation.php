<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventStockLocation extends Model
{
    protected $guarded = [];

    public function element()
    {
        return $this->belongsTo(PlanElement::class, 'element_id');
    }
}
