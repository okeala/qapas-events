<?php
namespace App\Models;
class Activity extends Record {
 protected $attributes=['proposer_type'=>'organization','track'=>'public','capacity'=>0,'risk_reviewed'=>false,'status'=>'idea'];
protected function casts(): array {return ['risk_reviewed'=>'boolean'];}
 public function eventProject() {return $this->belongsTo(EventProject::class);}
}
