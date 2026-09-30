<?php
namespace App\Models;
class Incident extends Record {
public function eventProject() {return $this->belongsTo(EventProject::class);}
}
