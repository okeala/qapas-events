<?php
namespace App\Models;
class LegalRequirement extends Record {
protected function casts(): array {return ['reviewed_at'=>'datetime','expires_at'=>'date'];}
 public function eventProject() {return $this->belongsTo(EventProject::class);}
}
