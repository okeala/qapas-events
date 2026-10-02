<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventCampaign extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['results' => 'array', 'due_at' => 'datetime'];
    }
}
