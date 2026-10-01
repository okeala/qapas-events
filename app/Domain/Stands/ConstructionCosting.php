<?php
namespace App\Domain\Stands;
use App\Models\{CabinProject,Scenario,StandExternalLine};
use Illuminate\Support\Facades\{DB,Validator};
use Illuminate\Validation\ValidationException;
final class ConstructionCosting {
 public const GROUPS=['tubes'=>'Tubes galvanisés','connectors'=>'Raccords structurels','roof_fixings'=>'Fixations toiture','completion'=>'Postes complémentaires'];
 public static function defaults(): array {return json_decode(file_get_contents(database_path('data/stand-construction.json')),true,512,JSON_THROW_ON_ERROR);}
 public static function validate(array $rows): void {Validator::make(['rows'=>$rows],[
  'rows'=>'array|max:100','rows.*.name'=>'required|string|max:255','rows.*.group'=>'required|in:'.implode(',',array_keys(self::GROUPS)),'rows.*.quantity'=>'nullable|integer|between:0,10000','rows.*.basis'=>'required|in:metre,piece,lot','rows.*.length_mm'=>'nullable|integer|between:1,240000','rows.*.reference_unit_cents'=>'nullable|integer|between:0,1000000000','rows.*.unit_cents'=>'nullable|integer|between:0,1000000000','rows.*.source'=>'required|in:recovered,new,donation,loan,service,undecided','rows.*.notes'=>'nullable|string|max:2000',
  ])->validate();foreach($rows as $i=>$row)if($row['basis']==='metre'&&(string)($row['quantity']??'')!=='0'&&empty($row['length_mm']))throw ValidationException::withMessages(['construction_costs.'.$i.'.length_mm'=>'Longueur unitaire en millimètres requise pour le prix au mètre.']);}
 private static function amount(array $row,string $price): ?int {
  if(($row['quantity']??null)===0||($row['quantity']??null)==='0')return 0;
  if(($row['quantity']??null)===null||$row['quantity']===''||($row[$price]??null)===null||$row[$price]==='')return null;
  $n=(int)$row['quantity']*(int)$row[$price];return $row['basis']==='metre'?intdiv($n*(int)$row['length_mm']+500,1000):$n;
 }
 public static function report(array $rows): array {
  self::validate($rows);$out=['reference_known_cents'=>0,'planned_known_cents'=>0,'missing'=>0,'reference_missing'=>0,'tube_length_mm'=>0,'tube_count'=>0,'groups'=>[]];
  foreach($rows as $r){$reference=self::amount($r,'reference_unit_cents');$planned=self::amount($r,'unit_cents');$group=$r['group'];$out['groups'][$group]??=['reference'=>0,'planned'=>0];$out['groups'][$group]['reference']+=$reference??0;$out['groups'][$group]['planned']+=$planned??0;$out['reference_known_cents']+=$reference??0;$out['planned_known_cents']+=$planned??0;$out['missing']+=(int)($planned===null);$out['reference_missing']+=(int)($reference===null);if($group==='tubes'&&$r['basis']==='metre'){$out['tube_count']+=(int)$r['quantity'];$out['tube_length_mm']+=(int)$r['quantity']*(int)$r['length_mm'];}}
  $out['planned_cents']=count($rows)&&!$out['missing']?$out['planned_known_cents']:null;return $out;
 }
 public function apply(CabinProject $construction,int $scenarioId): void {
  abort_unless(auth('admin')->user()?->is_active,403);
  DB::transaction(function()use($construction,$scenarioId){
   $c=CabinProject::lockForUpdate()->findOrFail($construction->id);$scenario=Scenario::findOrFail($scenarioId);$stand=$c->stand;
   if($scenario->is_archived||$scenario->event_project_id!==$c->event_project_id||!$scenario->includedStands()->where('stands.id',$stand->id)->exists())throw ValidationException::withMessages(['scenario_id'=>'Choisir un scénario actif incluant ce stand.']);
   $report=self::report($c->construction_costs??[]);
   if($report['planned_cents']===null||$c->construction_price_basis!=='gross'||$c->construction_vat_basis_points===null)throw ValidationException::withMessages(['construction_costs'=>'Compléter quantités et prix prévisionnels TTC, puis renseigner l’IVA. Le prix neuf de référence ne devient pas une dépense.']);
   $source='Prévision issue du détail de construction du stand '.$stand->name.' ; prix TTC saisis, sans commande ni paiement. Ne pas ajouter à nouveau les composants, transports et services déjà compris.';
   if($c->supply_mode==='qapas_rental'){
    $line=$c->costLine()->lockForUpdate()->first();
    if(!$line||$line->scenario_id!==$scenarioId||$line->kind!=='cost'||$line->stand_id!==$stand->id||$line->superseded_by_id||$line->committed_quantity||$line->paid_quantity||$line->pricing_status==='confirmed')throw ValidationException::withMessages(['cost_line_id'=>'Relier le coût de construction de ce stand dans ce scénario ; conserver tout prix confirmé, engagement ou règlement.']);
    $line->update(['unit_gross_cents'=>$report['planned_cents'],'forecast_quantity'=>1,'unit'=>'stand','vat_basis_points'=>$c->construction_vat_basis_points,'pricing_status'=>'estimate','price_source'=>$source]);
   }else{
    StandExternalLine::updateOrCreate(['stand_id'=>$stand->id,'scenario_id'=>$scenarioId,'template_key'=>'construction-'.$c->public_id],['name'=>'Construction du stand','party'=>'team','holder'=>$stand->operator?:$stand->name,'kind'=>'cost','quantity'=>1,'unit'=>'stand','unit_gross_cents'=>$report['planned_cents'],'evidence'=>$source]);
   }
  });
 }
}
