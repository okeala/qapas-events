<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventAuditEntry extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['details' => 'array'];
    }
}
