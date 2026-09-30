<?php
namespace App\Domain\Finance;
use App\Models\Activity;
final class ActivityCost {
 public function calculate(Activity $activity): array {
  $result=['gross_cents'=>0,'economic_cents'=>0,'missing'=>[]];
  if(!$activity->materials_complete) $result['missing'][]='Inventaire à confirmer';
  $materials=$activity->materials;
  if($materials->isEmpty()) $result['missing'][]='Aucun besoin chiffré';
  foreach($materials as $m){
   if($m->quantity===null||$m->unit_gross_cents===null){$result['missing'][]=$m->name.' : quantité ou prix inconnu';continue;}
   $count=(int)$m->quantity*($m->basis==='per_run'?(int)$activity->planned_runs:1);
   $gross=(int)$m->unit_gross_cents;$result['gross_cents']+=$gross*$count;
   if($m->vat_basis_points===null){$result['missing'][]=$m->name.' : IVA inconnu';continue;}
   if($gross===0&&blank($m->evidence)) $result['missing'][]=$m->name.' : gratuité à justifier';
   $result['economic_cents']+=($m->deductible?Money::net($gross,(int)$m->vat_basis_points):$gross)*$count;
  }
  $result['complete']=empty($result['missing']);return $result;
 }
}
