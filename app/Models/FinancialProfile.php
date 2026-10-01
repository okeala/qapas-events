<?php
namespace App\Models;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
class FinancialProfile extends Record {
 public const CATEGORIES=['sales'=>'Ventes / billetterie','sponsorship'=>'Parrainages / publicité','supplies'=>'Achats / consommables / stocks','rentals'=>'Locations / installations','personnel'=>'Personnel / porteur','marketing'=>'Communication / prospection','insurance'=>'Assurances / autorisations','logistics'=>'Transport / logistique','utilities'=>'Énergie / eau / sanitaires','administration'=>'Administration / services','awards'=>'Prix / récompenses','capex'=>'Investissements','other'=>'Autres / à classer'];
 public const DRIVERS=['source'=>'Quantité de la fiche source','stands'=>'Par stand inclus','villages'=>'Par stand de freguesia','independents'=>'Par stand indépendant','sponsors'=>'Par stand de sponsor','days'=>'Par jour d’événement','tickets'=>'Par ticket prévu','visitors'=>'Par visiteur prévu'];
 public const VAT=['pending'=>'À qualifier','domestic'=>'Opération nationale taxée','exempt'=>'Exonération documentée','outside'=>'Hors champ documenté','foreign'=>'TVA étrangère non déduite au Portugal','reverse'=>'Autoliquidation à documenter'];
 protected $attributes=['category'=>'other','behavior'=>'fixed','driver'=>'source','is_stock'=>false,'vat_treatment'=>'pending','deduction_basis_points'=>0];
 protected function casts(): array {return ['is_stock'=>'boolean','cash_schedule'=>'array','vat_schedule'=>'array'];}
 public function financialPlan(){return $this->belongsTo(FinancialPlan::class);}
 public function source(){return app(\App\Domain\Finance\FinancialSources::class)->resolve($this);}
 protected static function booted(): void {parent::booted();static::saving(function(self $p){
  Validator::make($p->getAttributes(),['source_type'=>'required|in:budget,material,site','source_id'=>'required|integer|min:1','source_kind'=>'required|in:cost,revenue,deposit,earmarked,third_party','category'=>'in:'.implode(',',array_keys(self::CATEGORIES)),'behavior'=>'in:fixed,variable','driver'=>'in:'.implode(',',array_keys(self::DRIVERS)),'vat_treatment'=>'in:'.implode(',',array_keys(self::VAT)),'deduction_basis_points'=>'integer|between:0,10000','depreciation_unit_cents'=>'nullable|integer|between:0,1000000000'])->validate();
  if($p->exists&&$p->isDirty(['financial_plan_id','source_type','source_id']))throw ValidationException::withMessages(['source_id'=>'Conserver le poste et son scénario.']);
  if(!$p->source())throw ValidationException::withMessages(['source_id'=>'Poste absent, remplacé ou hors du scénario sélectionné.']);
  if($p->driver!=='source'&&($p->source_type!=='budget'||$p->source()->stand_id||$p->source()->committed_quantity||$p->source()->paid_quantity))throw ValidationException::withMessages(['driver'=>'Multiplicateur réservé à un poste budgétaire global non engagé. Un poste de stand ou d’épreuve possède déjà sa quantité.']);
  if($p->source_kind!==($p->source_type==='budget'?$p->source()->kind:'cost'))throw ValidationException::withMessages(['source_kind'=>'Nature du poste modifiée : actualiser sa classification.']);
  $keys=array_keys($p->financialPlan->phaseOptions());
  foreach(['cash_schedule','vat_schedule'] as $field){$schedule=$p->$field??[];Validator::make([$field=>$schedule],[$field=>'array|max:24',"$field.*.phase"=>'required|string|distinct',"$field.*.share_basis_points"=>'required|integer|between:1,10000'])->validate();if($schedule&&(array_sum(array_column($schedule,'share_basis_points'))!==10000||array_diff(array_column($schedule,'phase'),$keys)))throw ValidationException::withMessages([$field=>'Répartir exactement 100 % entre les phases de ce scénario.']);}
  foreach(['invoice_phase','consumption_phase'] as $field)if($p->$field&&!in_array($p->$field,$keys,true))throw ValidationException::withMessages([$field=>'Phase hors scénario.']);
  if($p->is_stock&&$p->source_kind!=='cost')throw ValidationException::withMessages(['is_stock'=>'Seul un coût peut constituer un stock.']);
  if($p->is_stock&&$p->invoice_phase&&$p->consumption_phase&&array_search($p->consumption_phase,$keys,true)<array_search($p->invoice_phase,$keys,true))throw ValidationException::withMessages(['consumption_phase'=>'La consommation du stock suit son entrée.']);
  if($p->vat_treatment!=='pending'&&blank($p->vat_evidence))throw ValidationException::withMessages(['vat_evidence'=>'Qualification, droit à déduction et justificatifs à documenter.']);
  if(in_array($p->vat_treatment,['reverse','foreign'],true)&&$p->source_kind!=='cost')throw ValidationException::withMessages(['vat_treatment'=>'Autoliquidation modélisée uniquement pour les achats QAPAS.']);
  if($p->source_kind==='revenue'&&$p->vat_treatment==='domestic'&&$p->financialPlan->vat_regime!=='exempt'){
   $cash=collect($p->cash_schedule??[])->pluck('share_basis_points','phase');$vat=collect($p->vat_schedule??[])->pluck('share_basis_points','phase');$cashTotal=0;$vatTotal=0;$invoiced=false;
   if($cash->isEmpty())$cash=collect([$p->invoice_phase=>10000]);if($vat->isEmpty())$vat=collect([$p->invoice_phase=>10000]);
   foreach($keys as $key){$cashTotal+=$cash->get($key,0);$vatTotal+=$vat->get($key,0);if($key===$p->invoice_phase)$invoiced=true;if($vatTotal<max($cashTotal,$invoiced?10000:0))throw ValidationException::withMessages(['vat_schedule'=>'La TVA ne peut être repoussée après la facture ou les acomptes d’une recette nationale taxée.']);}
  }
  if($p->depreciation_unit_cents!==null&&blank($p->schedule_evidence))throw ValidationException::withMessages(['schedule_evidence'=>'Documenter la charge d’investissement affectée à cette édition.']);
  $p->vat_fingerprint=\App\Domain\Finance\FinancialSources::taxFingerprint($p->source(),$p->source_type);
  if($p->deduction_basis_points&&(!in_array($p->vat_treatment,['domestic','reverse'],true)||$p->source_kind!=='cost'))throw ValidationException::withMessages(['deduction_basis_points'=>'Déduction uniquement sur un coût national ou autoliquidé documenté.']);
 });}
}
