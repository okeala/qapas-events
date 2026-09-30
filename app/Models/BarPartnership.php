<?php
namespace App\Models;
use Illuminate\Validation\ValidationException;
class BarPartnership extends Record {
 protected $attributes=['status'=>'study','forecast_ticket_sales_cents'=>0];
 protected function casts(): array {return ['starts_at'=>'datetime','ends_at'=>'datetime'];}
 public function eventProject(){return $this->belongsTo(EventProject::class);}
 public function partner(){return $this->belongsTo(StandPartner::class,'stand_partner_id');}
 public function budgetLine(){return $this->belongsTo(BudgetLine::class);}
 public function forecastCommission(): ?int {return $this->commission_basis_points===null?null:intdiv($this->forecast_ticket_sales_cents*$this->commission_basis_points,10000);}
 protected static function booted(): void {parent::booted();static::saving(function(self $b){if($b->isDirty('budget_line_id'))$b->unsetRelation('budgetLine');if($b->isDirty('stand_partner_id'))$b->unsetRelation('partner');
  \Illuminate\Support\Facades\Validator::make($b->getAttributes(),['commission_basis_points'=>'nullable|integer|between:0,10000','forecast_ticket_sales_cents'=>'integer|between:0,100000000','forecast_other_costs_cents'=>'nullable|integer|between:0,100000000','status'=>'in:study,agreed,active,closed'])->validate();
  if($b->partner?->stand?->event_project_id!==$b->event_project_id)throw ValidationException::withMessages(['stand_partner_id'=>'Relais de cette édition requis.']);
  if($b->exists&&$b->isDirty(['event_project_id','stand_partner_id']))throw ValidationException::withMessages(['stand_partner_id'=>'Conserver le partenaire.']);
  if($b->budget_line_id&&($b->budgetLine?->scenario?->event_project_id!==$b->event_project_id||$b->budgetLine->kind!=='cost'))throw ValidationException::withMessages(['budget_line_id'=>'Coût de cette édition requis.']);
  if($b->status!=='study'&&(!$b->partner->relay_slot||$b->partner->status!=='active'||!$b->starts_at||!$b->ends_at||$b->ends_at->lte($b->starts_at)||$b->commission_basis_points===null||blank($b->terms)||blank($b->operator_evidence)||blank($b->agreement_evidence)))throw ValidationException::withMessages(['status'=>'Relais actif, créneau, mandat de vente, responsabilité QAPAS, assurance/hygiène, caisse et convention requis.']);
 });}
}
