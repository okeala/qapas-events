<?php
namespace App\Models;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
class StandExternalLine extends Record {
 protected $attributes=['unit'=>'lot'];
 public const PARTIES=['team'=>'Équipe / collectif de freguesia','exhibitor'=>'Exposant indépendant','junta'=>'Junta','sponsor'=>'Sponsor','other'=>'Autre intervenant'];
 protected function casts(): array {return ['quantity'=>'integer','unit_gross_cents'=>'integer'];}
 public function stand(){return $this->belongsTo(Stand::class);}
 public function scenario(){return $this->belongsTo(Scenario::class);}
 public function qapasBudgetLine(){return $this->belongsTo(BudgetLine::class,'qapas_budget_line_id');}
 public function total(): ?int {return $this->quantity===0?0:($this->quantity===null||$this->unit_gross_cents===null?null:$this->quantity*$this->unit_gross_cents);}
 protected static function booted(): void {parent::booted();static::saving(function(self $line): void {
  Validator::make($line->getAttributes(),['name'=>'required|string|max:255','holder'=>'required|string|max:255','kind'=>'required|in:cost,revenue','party'=>'required|in:'.implode(',',array_keys(self::PARTIES)),'quantity'=>'nullable|integer|between:0,1000000','unit'=>'required|string|max:80','unit_gross_cents'=>'nullable|integer|between:0,1000000000','evidence'=>'nullable|string|max:10000'])->validate();
  $stand=Stand::find($line->stand_id);$scenario=Scenario::find($line->scenario_id);
  if(!$stand||!$scenario||$scenario->is_archived||$scenario->event_project_id!==$stand->event_project_id||!$scenario->includedStands()->where('stands.id',$stand->id)->exists())throw ValidationException::withMessages(['scenario_id'=>'Choisir un scénario actif incluant ce stand et son édition.']);
  if($line->exists&&$line->isDirty(['stand_id','scenario_id']))throw ValidationException::withMessages(['stand_id'=>'Conserver le stand et le scénario de cette ligne.']);
  if($line->qapas_budget_line_id){$q=BudgetLine::find($line->qapas_budget_line_id);if(!$q||$q->superseded_by_id||$q->stand_id!==$line->stand_id||$q->scenario_id!==$line->scenario_id||$q->kind!==($line->kind==='cost'?'revenue':'cost'))throw ValidationException::withMessages(['qapas_budget_line_id'=>'La contrepartie QAPAS doit être de nature opposée, dans le même stand et scénario.']);}
 });}
}
