<?php
namespace App\Models;
class Scenario extends Record {
protected function casts(): array {return ['costs_complete'=>'boolean'];}
 public function eventProject() {return $this->belongsTo(EventProject::class);}
 public function budgetLines() {return $this->hasMany(BudgetLine::class);}
 public function report(): array {return app(\App\Domain\Finance\ScenarioCalculator::class)->calculate($this);}
}
