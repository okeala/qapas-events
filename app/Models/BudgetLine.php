<?php
namespace App\Models;
class BudgetLine extends Record {
protected function casts(): array {return ['deductible'=>'boolean'];}
 public function scenario() {return $this->belongsTo(Scenario::class);}
}
