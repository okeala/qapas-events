<?php
namespace App\Models;
class Interest extends Record {
 protected $attributes=['status'=>'new','marketing_opt_in'=>false,'source'=>'direct'];
protected function casts(): array {return ['marketing_opt_in'=>'boolean','privacy_acknowledged_at'=>'datetime'];}
 public function eventProject() {return $this->belongsTo(EventProject::class);}
}
