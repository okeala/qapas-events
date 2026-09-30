<?php
namespace App\Models;
class SupplierQuote extends Record {
 protected $attributes=['source_type'=>'supplier'];
 protected function casts(): array {return ['quantity'=>'integer','unit_gross_cents'=>'integer','vat_basis_points'=>'integer','delivery_cents'=>'integer','other_cents'=>'integer','deposit_cents'=>'integer','received_at'=>'date','valid_until'=>'date','applied_at'=>'datetime'];}
 public function consultation(){return $this->belongsTo(CostConsultation::class,'cost_consultation_id');}
 public function total(): ?int {if($this->quantity===null||$this->unit_gross_cents===null||$this->delivery_cents===null||$this->other_cents===null)return null;return $this->quantity*$this->unit_gross_cents+$this->delivery_cents+$this->other_cents;}
 protected static function booted(): void {parent::booted();static::saving(function(self $q){
  \Illuminate\Support\Facades\Validator::make($q->getAttributes(),['supplier'=>'required|string|max:255','email'=>'nullable|email|max:254','source_type'=>'in:supplier,local_loan,sponsor,word_of_mouth','quantity'=>'nullable|integer|between:1,1000000','unit_gross_cents'=>'nullable|integer|between:0,1000000000','vat_basis_points'=>'nullable|integer|between:0,10000','delivery_cents'=>'nullable|integer|between:0,1000000000','other_cents'=>'nullable|integer|between:0,1000000000','deposit_cents'=>'nullable|integer|between:0,1000000000'])->validate();
  if($q->exists&&$q->isDirty('cost_consultation_id'))throw \Illuminate\Validation\ValidationException::withMessages(['supplier'=>'Une offre reste dans sa consultation.']);
  if($q->exists&&$q->getOriginal('applied_at')&&$q->isDirty(['supplier','reference','received_at','quantity','unit','unit_gross_cents','vat_basis_points','delivery_cents','other_cents','deposit_cents','conditions','document_reference']))throw \Illuminate\Validation\ValidationException::withMessages(['supplier'=>'Offre déjà reprise dans une hypothèse budgétaire : saisir une nouvelle version pour conserver la trace.']);
 });}
}
