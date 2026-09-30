<?php
namespace App\Models;
use Illuminate\Validation\ValidationException;
class StandRequirement extends Record {
 protected $attributes=['provided_by'=>'undecided','status'=>'requested','unit'=>'unité'];
 public function stand(){return $this->belongsTo(Stand::class);}
 public function source(){return $this->belongsTo(SiteInfrastructure::class,'source_id');}
 public function budgetLine(){return $this->belongsTo(BudgetLine::class);}
 public function valid(): bool {
  if($this->status!=='approved'||blank($this->review_evidence))return false;
  $kinds=match($this->kind){'electricity'=>['generator','main_supply','distribution'],'water'=>['water_supply'],'wastewater'=>['septic','sanitation'],default=>null};
  if($kinds===null)return true;$source=SiteInfrastructure::find($this->source_id);return $source&&in_array($source->kind,$kinds,true)&&$source->available();
 }
 protected static function booted(): void {parent::booted();static::saving(function(self $n){
  \Illuminate\Support\Facades\Validator::make($n->getAttributes(),['name'=>'required|string|max:255','kind'=>'required|in:electricity,water,wastewater,gas,access,other','justification'=>'required|string|max:10000','quantity'=>'nullable|integer|between:1,100000','provided_by'=>'in:undecided,qapas,participant,sponsor','status'=>'in:requested,reviewed,approved','power_w'=>'nullable|numeric|min:0','starting_power_w'=>'nullable|numeric|min:0','requested_amps'=>'nullable|numeric|min:0','line_length_m'=>'nullable|numeric|min:0','water_litres'=>'nullable|numeric|min:0','gas_kg'=>'nullable|numeric|min:0'])->validate();
  if($n->exists&&$n->isDirty('stand_id'))throw ValidationException::withMessages(['stand_id'=>'Conserver le stand.']);$project=$n->stand?->event_project_id;
  if($n->source_id&&SiteInfrastructure::find($n->source_id)?->event_project_id!==$project)throw ValidationException::withMessages(['source_id'=>'Source hors édition.']);
  if($n->budget_line_id&&(BudgetLine::find($n->budget_line_id)?->scenario?->event_project_id!==$project||BudgetLine::find($n->budget_line_id)?->kind!=='cost'||(BudgetLine::find($n->budget_line_id)?->stand_id&&BudgetLine::find($n->budget_line_id)->stand_id!==$n->stand_id)))throw ValidationException::withMessages(['budget_line_id'=>'Coût du même stand ou coût commun de cette édition.']);
  if($n->exists&&$n->getOriginal('status')==='approved'&&$n->isDirty(['kind','justification','quantity','power_w','starting_power_w','requested_amps','line_length_m','water_litres','gas_kg','source_id','provided_by','review_evidence']))$n->status='reviewed';
  if($n->status==='approved'&&(!$n->valid()||$n->provided_by==='undecided'||($n->kind==='electricity'&&($n->power_w===null||$n->line_length_m===null))))throw ValidationException::withMessages(['status'=>'Justifier fourniture, longueur, puissance et validation technique ; aucune puissance supposée à partir de la section du câble.']);
 });static::saved(function(self $n){if($n->wasRecentlyCreated||$n->wasChanged(['kind','justification','quantity','power_w','starting_power_w','line_length_m','water_litres','gas_kg','source_id','provided_by','budget_line_id']))Stand::whereKey($n->stand_id)->update(['direct_costs_complete'=>false]);});}
}
