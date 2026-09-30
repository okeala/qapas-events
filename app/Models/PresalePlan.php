<?php
namespace App\Models;
use Illuminate\Validation\ValidationException;
class PresalePlan extends Record {
 protected $attributes=['kind'=>'support','price_cents'=>1000,'parking_basis_points'=>1000,'relay_basis_points'=>1000,'junta_basis_points'=>1000,'village_basis_points'=>1000,'capacity'=>0,'is_open'=>false];
 protected function casts(): array {return ['is_open'=>'boolean','closes_at'=>'datetime'];}
 public function eventProject(){return $this->belongsTo(EventProject::class);}
 public function tickets(){return $this->hasMany(EventTicket::class);}
 public function blockers(): array {return app(\App\Domain\Tickets\Ticketing::class)->blockers($this);}
 public function report(): array {return app(\App\Domain\Tickets\Ticketing::class)->report($this);}
 protected static function booted(): void {parent::booted();static::saving(function(self $p){
  $rules=['kind'=>'in:support,admission','price_cents'=>'integer|between:1000,1000000','vat_basis_points'=>'nullable|integer|between:0,10000','capacity'=>'integer|between:0,100000','terms_version'=>'required|string|max:100','bank_available_cents'=>'nullable|integer|between:0,1000000000','refund_reserve_cents'=>'nullable|integer|between:0,1000000000'];foreach(['parking','relay','junta','village'] as $k)$rules[$k.'_basis_points']='integer|between:0,10000';\Illuminate\Support\Facades\Validator::make($p->getAttributes(),$rules)->validate();
  if($p->parking_basis_points+$p->relay_basis_points+$p->junta_basis_points+$p->village_basis_points>10000)throw ValidationException::withMessages(['split_evidence'=>'Répartition supérieure à 100 %.']);
  if($p->exists&&$p->isDirty('event_project_id'))throw ValidationException::withMessages(['event_project_id'=>'Conserver l’édition.']);
  if($p->exists&&$p->isDirty(['kind','price_cents','terms_version','terms_fr','terms_pt','refund_fr','refund_pt','vat_basis_points','stripe_tax_rate_id','parking_basis_points','relay_basis_points','junta_basis_points','village_basis_points','split_evidence','billing_procedure','approval_evidence','closes_at'])){if($p->tickets()->exists()&&!$p->isDirty('terms_version'))throw ValidationException::withMessages(['terms_version'=>'Nouvelle version requise ; chaque billet conserve les conditions et la répartition acceptées.']);$p->is_open=false;}
  if($p->is_open&&(!$p->exists||$p->isDirty('is_open'))){if(!auth('admin')->user()?->is_active)throw ValidationException::withMessages(['is_open'=>'Administrateur actif requis.']);if($errors=$p->blockers())throw ValidationException::withMessages(['is_open'=>implode(' · ',$errors)]);}
 });}
}
