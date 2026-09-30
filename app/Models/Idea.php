<?php
namespace App\Models;
class Idea extends Record {
public function eventProject() {return $this->belongsTo(EventProject::class);}
}
