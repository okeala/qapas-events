<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class EventPublication extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['snapshot' => 'array'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Une publication est immuable ; publier une nouvelle révision.'));
    }

    public function event()
    {
        return $this->belongsTo(PlannerEvent::class, 'event_id');
    }
}
