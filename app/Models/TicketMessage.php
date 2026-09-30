<?php
namespace App\Models;
class TicketMessage extends Record {protected function casts(): array{return ['attempted_at'=>'datetime','delivered_at'=>'datetime'];}public function ticket(){return $this->belongsTo(EventTicket::class,'event_ticket_id');}}
