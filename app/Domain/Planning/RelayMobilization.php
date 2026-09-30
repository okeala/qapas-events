<?php
namespace App\Domain\Planning;
use App\Models\EventProject;
use Illuminate\Support\Str;
final class RelayMobilization {
 public function report(EventProject $p): array {
  $scenario=$p->launchScenario();
  $villages=[];if($scenario)foreach($scenario->includedStands()->where('kind','village')->get() as $stand){if(blank($stand->freguesia))continue;$key=Str::lower(Str::ascii(trim($stand->freguesia)));if($stand->partners()->whereNotNull('relay_slot')->where('status','active')->get()->contains(fn($r)=>filled($r->mission)&&filled($r->evidence)))$villages[$key]=true;}
  return ['active_freguesias'=>count($villages),'required'=>6,'ready'=>count($villages)>=6];
 }
}
