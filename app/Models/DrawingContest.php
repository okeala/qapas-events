<?php
namespace App\Models;
use Illuminate\Validation\ValidationException;
class DrawingContest extends Record {
 protected $attributes=['is_open'=>false];
 protected function casts(): array {return ['is_open'=>'boolean','opens_at'=>'datetime','closes_at'=>'datetime'];}
 public function eventProject(){return $this->belongsTo(EventProject::class);}
 public function budgetLine(){return $this->belongsTo(BudgetLine::class);}
 public function entries(){return $this->hasMany(DrawingEntry::class);}
 public function ready(): bool {return $this->opens_at&&$this->closes_at&&$this->closes_at->copy()->timezone('Europe/Lisbon')->toDateString()===$this->opens_at->copy()->timezone('Europe/Lisbon')->addDay()->toDateString()&&filled($this->rules_fr)&&filled($this->rules_pt)&&filled($this->jury_evidence)&&filled($this->prize_description)&&$this->budgetLine&&$this->budgetLine->scenario->event_project_id===$this->event_project_id&&$this->budgetLine->kind==='cost'&&$this->budgetLine->unit_gross_cents!==null&&$this->budgetLine->forecast_quantity>=$this->eventProject->stands()->where('kind','village')->count()&&!\App\Domain\Finance\Pricing::pending($this->budgetLine)&&$this->budgetLine->vat_basis_points!==null;}
 protected static function booted(): void {parent::booted();static::saving(function(self $c){if($c->isDirty('budget_line_id'))$c->unsetRelation('budgetLine');if($c->isDirty('event_project_id'))$c->unsetRelation('eventProject');if($c->exists&&$c->isDirty('event_project_id'))throw ValidationException::withMessages(['event_project_id'=>'Conserver l’édition.']);if($c->is_open&&!$c->ready())throw ValidationException::withMessages(['is_open'=>'Clôture le lendemain, règlement FR/PT, jury, prix et budget pour une récompense par freguesia requis.']);});}
}
