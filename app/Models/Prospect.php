<?php
namespace App\Models;
class Prospect extends Record {
 protected $attributes=['status'=>'new','priority'=>2,'visit_order'=>100,'address_confirmed'=>false];
 protected function casts(): array {return ['appointment_at'=>'datetime','checked_at'=>'date','address_confirmed'=>'boolean'];}
 public function eventProject(){return $this->belongsTo(EventProject::class);}
 protected static function booted(): void {parent::booted();static::saving(function(self $p){\Illuminate\Support\Facades\Validator::make($p->getAttributes(),['status'=>'in:new,to_call,appointment,contacted,qualified,declined','priority'=>'integer|between:1,3','visit_order'=>'integer|between:0,100000'])->validate();});}
}
