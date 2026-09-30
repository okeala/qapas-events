<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
class Distributor extends Authenticatable {
 protected $guarded=['id','public_id'];protected $hidden=['password','remember_token'];
 protected function casts(): array {return ['password'=>'hashed','is_active'=>'boolean'];}
 public function getRouteKeyName(): string {return 'public_id';}
 public function standPartner(){return $this->belongsTo(StandPartner::class);}
 public function tickets(){return $this->hasMany(EventTicket::class);}
 public function active(): bool {return $this->is_active&&filled($this->contract_evidence)&&$this->standPartner?->status==='active'&&$this->standPartner?->relay_slot!==null;}
 protected static function booted(): void {static::creating(fn(self $d)=>$d->public_id=(string)\Illuminate\Support\Str::uuid());static::saving(function(self $d){if($d->exists&&$d->isDirty('stand_partner_id'))throw \Illuminate\Validation\ValidationException::withMessages(['stand_partner_id'=>'Conserver le relais lié.']);if($d->is_active&&!$d->active())throw \Illuminate\Validation\ValidationException::withMessages(['is_active'=>'Relais actif et mandat de distribution écrit requis : caisse, commission, reversement, remboursements et données personnelles.']);});}
}
