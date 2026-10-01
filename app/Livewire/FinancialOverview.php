<?php
namespace App\Livewire;
use App\Models\{Scenario,FinancialPlan};
class FinancialOverview extends \Livewire\Component {
 public string $scenarioId;
 public int $price=100;
 public int $volume=100;
 public int $fixed=100;
 public function mount(string $scenarioId): void {$this->guardAdmin();$this->scenarioId=$scenarioId;}
 private function guardAdmin(): void {abort_unless(auth('admin')->user()?->is_active,403);}
 public function resetSimulation(): void {$this->guardAdmin();$this->price=$this->volume=$this->fixed=100;}
 public function render(){
  $this->guardAdmin();$this->validate(['scenarioId'=>'required|uuid','price'=>'integer|between:25,200','volume'=>'integer|between:0,200','fixed'=>'integer|between:25,200']);
  $scenario=Scenario::where('public_id',$this->scenarioId)->where('is_archived',false)->firstOrFail();$plan=FinancialPlan::where('scenario_id',$scenario->id)->first();$report=$plan?->report(['price'=>$this->price,'volume'=>$this->volume,'fixed'=>$this->fixed]);
  return view('livewire.financial-overview',compact('scenario','plan','report'));
 }
}
