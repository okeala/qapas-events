<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventLead extends Model
{
    protected $guarded = [];

    protected $hidden = ['name', 'email', 'notes', 'subject_id'];

    protected function casts(): array
    {
        return ['name' => 'encrypted', 'email' => 'encrypted', 'notes' => 'encrypted', 'due_at' => 'datetime'];
    }
}
