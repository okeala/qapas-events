<?php
namespace App\Models;
class Debrief extends Record {
 public function eventProject() {return $this->belongsTo(EventProject::class);}
}
