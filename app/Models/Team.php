<?php
namespace App\Models;
class Team extends Record {
public function eventProject() {return $this->belongsTo(EventProject::class);}
}
