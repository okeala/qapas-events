<?php
namespace Database\Seeders;
use App\Models\{CabinProject,EventProject};
use App\Domain\Stands\ConstructionCosting;
use Illuminate\Database\Seeder;
class StandEconomicsSeeder extends Seeder {
 public function run(): void {
  $project=EventProject::where('slug','os-jogos-do-agricultor')->first();if(!$project)return;
  foreach(CabinProject::where('event_project_id',$project->id)->whereNull('construction_costs')->get() as $construction)$construction->update(['construction_costs'=>ConstructionCosting::defaults(),'construction_price_basis'=>'unknown']);
 }
}
