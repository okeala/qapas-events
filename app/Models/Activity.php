<?php
namespace App\Models;
class Activity extends Record {
protected function casts(): array {return ['risk_reviewed'=>'boolean'];}
 public function eventProject() {return $this->belongsTo(EventProject::class);}
}
