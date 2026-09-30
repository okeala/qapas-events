<?php
namespace App\Models;
use Illuminate\Support\Facades\{DB,Validator};
use Illuminate\Validation\ValidationException;
class PlantingSession extends Record {
 protected $attributes=['status'=>'draft','is_public'=>false,'capacity'=>0,'trees_received'=>0];
 protected function casts(): array {return ['capacity'=>'integer','trees_received'=>'integer','day_number'=>'integer','starts_at'=>'datetime','is_public'=>'boolean'];}
 public function eventProject(){return $this->belongsTo(EventProject::class);}
 public function quartel(){return $this->belongsTo(SiteFeature::class,'quartel_id');}
 public function nursery(){return $this->belongsTo(Prospect::class,'nursery_prospect_id');}
 public function budgetLine(){return $this->belongsTo(BudgetLine::class);}
 public function slots(){return $this->hasMany(PlantingSlot::class);}
 public function blockers(): array {
  $b=[];if(!$this->quartel||$this->quartel->category!=='quartel')$b[]='Quartel à affecter';if(!$this->nursery||blank($this->partner_agreement))$b[]='Pépiniériste et accord du jour requis';
  foreach(['supervisor','instructions_fr','instructions_pt','preparation_evidence'] as $f)if(blank($this->$f))$b[]=$f.' à compléter';
  if(!$this->starts_at||!$this->eventProject?->starts_at||!$this->eventProject?->ends_at||$this->starts_at->lt($this->eventProject->starts_at)||$this->starts_at->gt($this->eventProject->ends_at))$b[]='Créneau de clôture dans les dates confirmées requis';
  $cost=$this->budgetLine;if($this->starts_at&&$this->eventProject?->starts_at&&$this->starts_at->copy()->timezone('Europe/Lisbon')->toDateString()!==$this->eventProject->starts_at->copy()->timezone('Europe/Lisbon')->startOfDay()->addDays($this->day_number-1)->toDateString())$b[]='La date doit correspondre au numéro de journée';if($cost?->scenario?->is_archived)$b[]='Choisir un budget actif pour cette session';if(!$cost||$cost->kind!=='cost'||$cost->forecast_quantity!==$this->capacity||$cost->unit_gross_cents===null||$cost->vat_basis_points===null||\App\Domain\Finance\Pricing::pending($cost))$b[]='Coût complet par arbre, quantité et financement à valider dans le budget';
  if(!$this->capacity||$this->trees_received<$this->capacity||blank($this->receipt_evidence))$b[]='Un arbre réceptionné par slot promis requis';
  return $b;
 }
 public function ready(): bool {return $this->status==='ready'&&!$this->blockers();}
 public function contains(float $lng,float $lat): bool {
  $g=$this->quartel?->geometry;if(!$g)return false;$polys=$g['type']==='MultiPolygon'?$g['coordinates']:[$g['coordinates']];foreach($polys as $rings){if(!\App\Domain\Planning\TerraceGeometry::contains($rings[0],$lng,$lat))continue;$hole=false;foreach(array_slice($rings,1) as $ring)if(\App\Domain\Planning\TerraceGeometry::contains($ring,$lng,$lat))$hole=true;if(!$hole)return true;}return false;
 }
 public function save(array $options=[]){return DB::transaction(function()use($options){EventProject::whereKey($this->event_project_id)->lockForUpdate()->firstOrFail();return parent::save($options);},3);}
 protected static function booted(): void {parent::booted();static::saving(function(self $s){
  Validator::make($s->getAttributes(),['day_number'=>'required|integer|between:1,30','capacity'=>'integer|between:0,1000','trees_received'=>'integer|between:0,10000','status'=>'in:draft,ready,closed'])->validate();
  if($s->exists&&$s->isDirty('event_project_id'))throw ValidationException::withMessages(['event_project_id'=>'Conserver l’édition.']);
  foreach(['quartel'=>SiteFeature::class,'nursery_prospect'=>Prospect::class] as $key=>$class)if($s->{$key.'_id'}&&!$class::whereKey($s->{$key.'_id'})->where('event_project_id',$s->event_project_id)->exists())throw ValidationException::withMessages([$key.'_id'=>'Objet hors édition.']);
  if($s->quartel_id&&$s->quartel?->category!=='quartel')throw ValidationException::withMessages(['quartel_id'=>'Choisir un quartel.']);
  if($s->budget_line_id&&$s->budgetLine?->scenario?->event_project_id!==$s->event_project_id)throw ValidationException::withMessages(['budget_line_id'=>'Budget d’une autre édition.']);
  if($s->nursery_prospect_id&&self::where('event_project_id',$s->event_project_id)->whereHas('nursery',fn($q)=>$q->where('entity_key',$s->nursery->entity_key))->when($s->exists,fn($q)=>$q->whereKeyNot($s->id))->exists())throw ValidationException::withMessages(['nursery_prospect_id'=>'Choisir un autre pépiniériste pour l’autre journée, même s’il dispose de plusieurs établissements.']);
  if($s->exists&&($s->capacity<$s->slots()->count()||$s->trees_received<$s->slots()->whereIn('status',['reserved','planted'])->count()))throw ValidationException::withMessages(['capacity'=>'Ne pas retirer les arbres déjà affectés.']);
  if($s->exists&&$s->isDirty(['quartel_id','starts_at','supervisor','nursery_prospect_id','capacity','instructions_fr','instructions_pt']))$s->status='draft';
  if($s->status==='ready'&&$s->blockers())throw ValidationException::withMessages(['status'=>implode(' · ',$s->blockers())]);
 });}
}
