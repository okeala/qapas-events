<?php
namespace App\Models;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
class BudgetLine extends Record {
 protected $attributes=['deductible'=>false,'forecast_quantity'=>0,'committed_quantity'=>0,'paid_quantity'=>0,'scope'=>'common','paid_by'=>'qapas','reimbursed_cents'=>0];
 protected function casts(): array {return ['deductible'=>'boolean','reconciled_at'=>'datetime'];}
 public function scenario(){return $this->belongsTo(Scenario::class);}
 public function stand(){return $this->belongsTo(Stand::class);}
 public function standPartner(){return $this->belongsTo(StandPartner::class);}
 public function verified(): bool {return !\App\Domain\Finance\Pricing::pending($this)&&$this->paid_quantity>0&&filled($this->receipt_reference)&&$this->reconciled_at&&!$this->reconciled_at->isFuture()&&$this->reconciled_by!==null;}
 protected static function booted(): void {parent::booted();static::saving(function(self $l){\App\Domain\Finance\Pricing::validate($l);
  Validator::make($l->getAttributes(),['unit_gross_cents'=>'nullable|integer|between:0,1000000000','vat_basis_points'=>'nullable|integer|between:0,10000','forecast_quantity'=>'integer|between:0,1000000','committed_quantity'=>'integer|min:0|lte:forecast_quantity','paid_quantity'=>'integer|min:0|lte:committed_quantity','costing_key'=>'nullable|string|max:120','expense_type'=>'sometimes|in:operating,investment','unit'=>'sometimes|string|max:80','kind'=>'required|in:revenue,cost,deposit,earmarked,third_party','scope'=>'in:common,stand,bar,fries,soup,structural,cups','paid_by'=>'in:qapas,organizer'])->validate();
  $l->identity_key=$l->superseded_by_id?null:\App\Domain\Finance\BudgetIdentity::key($l->getAttributes());
  if($l->identity_key&&self::where('scenario_id',$l->scenario_id)->whereNull('superseded_by_id')->when($l->exists,fn($q)=>$q->where('id','!=',$l->id))->get()->contains(fn($other)=>\App\Domain\Finance\BudgetIdentity::key($other->getAttributes())===$l->identity_key))throw ValidationException::withMessages(['name'=>'Ce poste existe déjà dans cette unité et ce scénario. Modifier sa quantité ou enregistrer une offre fournisseur alternative.']);
  if($l->exists&&$l->isDirty('scenario_id'))throw ValidationException::withMessages(['scenario_id'=>'Une ligne existante ne change pas de scénario.']);
  if(!$l->superseded_by_id&&filled($l->costing_key)&&self::whereNull('superseded_by_id')->where('scenario_id',$l->scenario_id)->where('costing_key',$l->costing_key)->when($l->exists,fn($q)=>$q->where('id','!=',$l->id))->exists())throw ValidationException::withMessages(['costing_key'=>'Cette clé de mutualisation existe déjà dans le scénario.']);
  if($l->stand_id&&(!Stand::whereKey($l->stand_id)->where('event_project_id',$l->scenario?->event_project_id)->exists()||$l->scope!=='stand'))throw ValidationException::withMessages(['stand_id'=>'Choisir un stand de cette édition, avec le périmètre Stand.']);
  if($l->scope==='stand'&&!$l->stand_id)throw ValidationException::withMessages(['stand_id'=>'Le périmètre Stand exige un stand.']);
  if($l->stand_partner_id&&StandPartner::find($l->stand_partner_id)?->stand_id!==$l->stand_id)throw ValidationException::withMessages(['stand_partner_id'=>'Partenaire d’un autre stand.']);
  if($l->reimbursed_cents<0||$l->reimbursed_cents>($l->unit_gross_cents??0)*$l->paid_quantity||($l->reimbursed_cents>0&&($l->paid_by!=='organizer'||$l->kind!=='cost')))throw ValidationException::withMessages(['reimbursed_cents'=>'Remboursement supérieur à l’avance ou ligne non concernée.']);
  if($l->isDirty(['unit_gross_cents','vat_basis_points','paid_quantity','kind','scope','stand_id','stand_partner_id','pricing_status','price_source'])){$l->reconciled_at=null;$l->reconciled_by=null;}
  if(($l->isDirty('reconciled_at')||$l->isDirty('receipt_reference'))&&$l->reconciled_at){
   if(!auth('admin')->user()?->is_active||blank($l->receipt_reference)||$l->reconciled_at->isFuture())throw ValidationException::withMessages(['reconciled_at'=>'Administrateur actif, date passée et référence du justificatif requis.']);
   if(self::where('scenario_id',$l->scenario_id)->where('receipt_reference',$l->receipt_reference)->whereNotNull('reconciled_by')->where('id','!=',$l->id)->exists())throw ValidationException::withMessages(['receipt_reference'=>'Ce paiement est déjà rapproché dans ce scénario.']);
   $l->reconciled_by=auth('admin')->id();
  }
 });}
}
