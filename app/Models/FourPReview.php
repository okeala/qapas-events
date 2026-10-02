<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FourPReview extends Model
{
    protected $table = 'event_four_p_reviews';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['four_ps' => 'array', 'observed_at' => 'datetime', 'due_at' => 'datetime'];
    }
}
