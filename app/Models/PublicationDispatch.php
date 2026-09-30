<?php
namespace App\Models;
class PublicationDispatch extends Record {
 protected $hidden=['destination','payload'];
 protected function casts(): array {return ['payload'=>'array','started_at'=>'datetime','sent_at'=>'datetime'];}
 public function pressRelease(){return $this->belongsTo(PressRelease::class);}
}
