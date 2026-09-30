<?php
namespace App\Models;
class Offer extends Record {
protected function casts(): array {return ['is_public'=>'boolean'];}
 public function eventProject() {return $this->belongsTo(EventProject::class);}
}
