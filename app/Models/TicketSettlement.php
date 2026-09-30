<?php
namespace App\Models;
class TicketSettlement extends Record {protected function casts(): array{return ['is_live'=>'boolean','received_at'=>'datetime'];}public function distributor(){return $this->belongsTo(Distributor::class);}public function plan(){return $this->belongsTo(PresalePlan::class,'presale_plan_id');}public function tickets(){return $this->hasMany(EventTicket::class);}}
