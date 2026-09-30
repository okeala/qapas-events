<?php
namespace App\Models;
class Stand extends Record {
 public function eventProject() {return $this->belongsTo(EventProject::class);}
}
