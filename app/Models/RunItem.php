<?php
namespace App\Models;
class RunItem extends Record {
protected function casts(): array {return ['starts_at'=>'datetime','ends_at'=>'datetime'];}
 public function eventProject() {return $this->belongsTo(EventProject::class);}
}
