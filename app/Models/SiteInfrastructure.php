<?php
namespace App\Models;
use Illuminate\Validation\ValidationException;
class SiteInfrastructure extends Record {
 public const KINDS=['water_supply'=>'Alimentation en eau','gas_storage'=>'Zone de stockage gaz','generator'=>'Groupe électrogène','main_supply'=>'Alimentation principale','distribution'=>'Point de distribution','sanitation'=>'Bloc sanitaire','septic'=>'Assainissement / fosse','parking'=>'Parking','food_zone'=>'Zone des baraques QAPAS'];
 protected $attributes=['status'=>'declared','phases'=>'unknown','neutral_verified'=>false,'earth_verified'=>false];
 protected function casts(): array {return ['neutral_verified'=>'boolean','earth_verified'=>'boolean'];}
 public function eventProject(){return $this->belongsTo(EventProject::class);}
 public function source(){return $this->belongsTo(self::class,'source_id');}
 public function siteFeature(){return $this->belongsTo(SiteFeature::class);}
 public function budgetLine(){return $this->belongsTo(BudgetLine::class);}
 public function available(array $seen=[]): bool {if(in_array($this->id,$seen)||$this->status!=='approved'||blank($this->review_evidence))return false;return !$this->source_id||(self::find($this->source_id)?->available([...$seen,$this->id])??false);}
 protected static function booted(): void {parent::booted();static::saving(function(self $i){
  \Illuminate\Support\Facades\Validator::make($i->getAttributes(),['kind'=>'required|in:'.implode(',',array_keys(self::KINDS)),'status'=>'in:declared,to_build,reviewed,approved','phases'=>'in:unknown,single,three','declared_kva'=>'nullable|numeric|min:0','amp_limit'=>'nullable|numeric|min:0','voltage_v'=>'nullable|integer|between:1,1000','conductor_count'=>'nullable|integer|between:1,20','section_mm2'=>'nullable|numeric|min:0'])->validate();
  if($i->exists&&$i->isDirty('event_project_id'))throw ValidationException::withMessages(['event_project_id'=>'Conserver l’édition.']);
  $seen=[$i->id];$next=$i->source_id;while($next){if(in_array($next,$seen))throw ValidationException::withMessages(['source_id'=>'Boucle d’alimentation interdite.']);$s=self::find($next);if(!$s||$s->event_project_id!==$i->event_project_id)throw ValidationException::withMessages(['source_id'=>'Source hors édition.']);$seen[]=$next;$next=$s->source_id;}
  if($i->site_feature_id&&!SiteFeature::whereKey($i->site_feature_id)->where('event_project_id',$i->event_project_id)->exists())throw ValidationException::withMessages(['site_feature_id'=>'Objet hors édition.']);
  if($i->budget_line_id&&(BudgetLine::find($i->budget_line_id)?->scenario?->event_project_id!==$i->event_project_id||BudgetLine::find($i->budget_line_id)?->kind!=='cost'))throw ValidationException::withMessages(['budget_line_id'=>'Rattacher un coût de cette édition.']);
  if($i->exists&&$i->getOriginal('status')==='approved'&&$i->isDirty(['source_id','site_feature_id','declared_kva','voltage_v','phases','amp_limit','cable_marking','conductor_count','section_mm2','neutral_verified','earth_verified','description','review_evidence']))$i->status='reviewed';
  if($i->status==='approved'&&(blank($i->review_evidence)||(in_array($i->kind,['generator','main_supply','distribution'])&&(!$i->amp_limit||!$i->voltage_v||$i->phases==='unknown'||!$i->earth_verified))))throw ValidationException::withMessages(['status'=>'Documenter les vérifications du technicien, protections, terre et caractéristiques réelles avant disponibilité.']);
 });}
}
