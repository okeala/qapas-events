<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Models\EventProject;
class BudgetAudit extends Command {
 protected $signature='events:budget-audit {--edition=os-jogos-do-agricultor}';
 protected $description='Exporter le chiffrage courant sans contacts ni justificatifs privés';
 public function handle(): int {
  $p=EventProject::where('slug',$this->option('edition'))->firstOrFail();$s=$p->launchScenario();if(!$s){$this->error('Aucun scénario actif.');return 1;}
  $this->line(json_encode(['scenario'=>$s->only(['name','months','organizer_net_monthly_cents','organizer_full_monthly_cents','target_surplus_cents','contingency_cents','refund_reserve_cents']),'report'=>$s->report(),'lines'=>$s->budgetLines->map(fn($l)=>$l->only(['id','name','stand_id','kind','scope','costing_key','unit_gross_cents','vat_basis_points','forecast_quantity','pricing_status'])),'activities'=>$s->includedActivities->map(fn($a)=>['id'=>$a->id,'name'=>$a->name,'cost'=>$a->costReport()])],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));return 0;
 }
}
