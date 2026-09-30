<?php
namespace App\Models;
use Illuminate\Support\Facades\{DB,Validator};
use Illuminate\Validation\ValidationException;
class WelcomePackPlan extends Record {
 public const SIZES=['XS','S','M','L','XL','XXL','3XL','4XL'];
 public const CATEGORIES=['players'=>'Joueurs élus','qapas'=>'Organisation QAPAS','service'=>'Personnes de service'];
 protected $attributes=['use_roster'=>false,'shirts_per_person'=>1,'reserve_percent'=>20];
 protected function casts(): array {return ['cohorts'=>'array','sizes'=>'array','people'=>'array','use_roster'=>'boolean','approved_at'=>'datetime'];}
 public function eventProject(){return $this->belongsTo(EventProject::class);}
 public function sponsor(){return $this->belongsTo(Sponsorship::class,'sponsor_id');}
 public function shirtCostLine(){return $this->belongsTo(BudgetLine::class,'shirt_cost_line_id');}
 public function setupCostLine(){return $this->belongsTo(BudgetLine::class,'setup_cost_line_id');}
 public function deliveryCostLine(){return $this->belongsTo(BudgetLine::class,'delivery_cost_line_id');}
 public function report(): array {return app(\App\Domain\Welcome\Shirts::class)->calculate($this);}
 public function electedPeople(): array {
  $people=[];foreach(TeamRoleAssignment::whereHas('team',fn($q)=>$q->where('event_project_id',$this->event_project_id)->where('status','elected'))->where('status','confirmed')->get() as $role){if(!$role->isCovered())continue;$key=$role->interest_id?'interest-'.$role->interest_id:'register-'.mb_strtolower(trim($role->candidate_reference));$people[$key]=['person_key'=>$key,'name'=>$role->candidate_name,'category'=>'players','size'=>null,'confirmed'=>false];}return array_values($people);
 }
 public function importPlayers(): void {
  abort_unless(auth('admin')->user()?->is_active,403);$people=collect($this->people??[])->keyBy('person_key');foreach($this->electedPeople() as $person)if(!$people->has($person['person_key']))$people->put($person['person_key'],$person);$this->update(['people'=>$people->values()->all()]);
 }
 public function synchronizeBudget(): void {
  abort_unless(auth('admin')->user()?->is_active,403);DB::transaction(function(){
   $line=BudgetLine::lockForUpdate()->findOrFail($this->shirt_cost_line_id);$count=$this->report()['total'];
   if($line->committed_quantity||$line->paid_quantity||$line->superseded_by_id)throw ValidationException::withMessages(['shirt_cost_line_id'=>'Commande déjà engagée : documenter un avenant et ses coûts avant de modifier les quantités.']);
   $line->update(['forecast_quantity'=>$count]);
  });
 }
 protected static function booted(): void {parent::booted();static::saving(function(self $p){
  foreach(['sponsor_id'=>'sponsor','shirt_cost_line_id'=>'shirtCostLine','setup_cost_line_id'=>'setupCostLine','delivery_cost_line_id'=>'deliveryCostLine','event_project_id'=>'eventProject'] as $column=>$relation)if($p->isDirty($column))$p->unsetRelation($relation);
  if($p->exists&&$p->isDirty('event_project_id'))throw ValidationException::withMessages(['event_project_id'=>'Conserver l’édition.']);
  $people=$p->people??[];foreach($people as &$person)$person['person_key']=mb_strtolower(trim($person['person_key']??''));unset($person);$p->people=$people;
  Validator::make(array_merge($p->getAttributes(),['cohorts'=>$p->cohorts,'sizes'=>$p->sizes,'people'=>$people]),[
   'cohorts'=>'required|array|min:1|max:20','cohorts.*.category'=>'required|in:players,qapas,service','cohorts.*.quantity'=>'required|integer|between:0,10000',
   'sizes'=>'required|array|min:1|max:8','sizes.*.size'=>'required|distinct|in:'.implode(',',self::SIZES),'sizes.*.quantity'=>'required|integer|between:0,10000',
   'people'=>'array|max:2000','people.*.person_key'=>'required|string|distinct|max:120','people.*.name'=>'required|string|max:120','people.*.category'=>'required|in:players,qapas,service','people.*.size'=>'nullable|in:'.implode(',',self::SIZES),'people.*.confirmed'=>'required|boolean',
   'shirts_per_person'=>'required|integer|between:1,3','reserve_percent'=>'required|integer|in:20',
  ])->validate();
  $ids=[];$scenario=null;foreach(['shirtCostLine','setupCostLine','deliveryCostLine'] as $relation){$l=$p->$relation;if(!$l)continue;if(in_array($l->id,$ids,true)||$l->kind!=='cost'||$l->superseded_by_id||$l->scenario->event_project_id!==$p->event_project_id||($scenario&&$scenario!==$l->scenario_id))throw ValidationException::withMessages(['shirt_cost_line_id'=>'Trois coûts distincts, actifs, du même scénario et de cette édition.']);$ids[]=$l->id;$scenario=$l->scenario_id;}
  if($p->sponsor_id&&(!$p->sponsor||$p->sponsor->event_project_id!==$p->event_project_id||$p->sponsor->scope!=='welcome_pack'||$p->sponsor->purpose!=='shirts'))throw ValidationException::withMessages(['sponsor_id'=>'Choisir le sponsor t-shirts de cette édition.']);
  if($p->exists&&$p->isDirty(['cohorts','sizes','people','use_roster','shirts_per_person','specification','shirt_cost_line_id','setup_cost_line_id','delivery_cost_line_id'])){$p->approved_at=null;$p->approved_by=null;$p->approval_evidence=null;}
  if($p->approved_at&&$p->isDirty('approved_at')){if(!auth('admin')->user()?->is_active||$p->approved_at->isFuture()||blank($p->approval_evidence)||!$p->report()['sizes_ready']||blank($p->specification))throw ValidationException::withMessages(['approved_at'=>'Enregistrer puis confirmer le relevé nominatif complet, les tailles, le modèle et la preuve avant validation.']);$p->approved_by=auth('admin')->id();}
 });}
}
