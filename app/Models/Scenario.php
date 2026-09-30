<?php
namespace App\Models;
class Scenario extends Record {
 protected $attributes=['months'=>1,'target_surplus_cents'=>400000,'organizer_net_monthly_cents'=>100000,'costs_complete'=>false,'team_target'=>0,'stand_target'=>0];
protected function casts(): array {return ['costs_complete'=>'boolean'];}
 public function eventProject() {return $this->belongsTo(EventProject::class);}
 public function budgetLines() {return $this->hasMany(BudgetLine::class);}
 public function report(): array {return app(\App\Domain\Finance\ScenarioCalculator::class)->calculate($this);}
}
