<?php
namespace App\Models;
class BudgetLine extends Record {
 protected $attributes=['deductible'=>false,'forecast_quantity'=>0,'committed_quantity'=>0,'paid_quantity'=>0];
protected function casts(): array {return ['deductible'=>'boolean'];}
 public function scenario() {return $this->belongsTo(Scenario::class);}
}
