<?php
namespace App\Models;
class Offer extends Record {
protected function casts(): array {return ['is_public'=>'boolean','is_founder'=>'boolean','founder_deadline'=>'date'];}
 public function eventProject() {return $this->belongsTo(EventProject::class);}
}
