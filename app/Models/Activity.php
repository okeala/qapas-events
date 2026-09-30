<?php
namespace App\Models;
use App\Domain\Planning\TerraceGeometry;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
class Activity extends Record {
 protected $attributes=['proposer_type'=>'organization','track'=>'public','capacity'=>0,'risk_reviewed'=>false,'status'=>'idea','risk_category'=>'manual','access'=>'team','is_public'=>false,'broadcast_planned'=>false,'materials_complete'=>false,'planned_runs'=>1,'sort_order'=>100,'publication_level'=>'details'];
 protected function casts(): array {return ['relay_reveal'=>'boolean','risk_reviewed'=>'boolean','is_public'=>'boolean','broadcast_planned'=>'boolean','materials_complete'=>'boolean','map_x'=>'float','map_y'=>'float'];}
 public function stand(){return $this->belongsTo(Stand::class);}
 public function trials(){return $this->hasMany(ActivityTrial::class);}
 public function preparation(){return app(\App\Domain\Planning\ActivityPreparation::class);}
 public function eventProject(){return $this->belongsTo(EventProject::class);}
 public function locations(){return $this->hasMany(ActivityLocation::class);}
 public function fundingScenario(){return $this->belongsTo(Scenario::class,'funding_scenario_id');}
 public function relayUnlocked(): bool {return $this->relay_reveal&&$this->track==='official'&&app(\App\Domain\Planning\RelayMobilization::class)->report($this->eventProject)['ready'];}
 public function publicVisible(): bool {return $this->participation_decision!=='stand_only'&&($this->proposer_type==='organization'||!$this->eventProject->require_activity_trials||!$this->eventProject->starts_at||now()->lt($this->eventProject->starts_at)||$this->preparation()->ready($this))&&$this->is_public&&$this->status!=='archived'&&($this->publication_level!=='hidden'||$this->relayUnlocked());}
 public function revealed(): bool {return in_array($this->publication_level,['details','confirmed'],true)||$this->relayUnlocked();}
 public function publicLocationVisible(): bool {return $this->publicVisible()&&$this->revealed()&&(!$this->relay_reveal||($this->publication_level==='confirmed'&&app(\App\Domain\Planning\Revelation::class)->confirmable($this)));}
 public function terrace(){return $this->belongsTo(Terrace::class);}
 public function spectatorTerrace(){return $this->belongsTo(Terrace::class,'spectator_terrace_id');}
 public function materials(){return $this->hasMany(ActivityMaterial::class);}
 public function costReport(): array {return app(\App\Domain\Finance\ActivityCost::class)->calculate($this);}
 protected static function booted(): void {
  parent::booted();static::saving(function(Activity $a){
   if($a->proposer_type==='organization'&&$a->participation_decision==='stand_only')throw ValidationException::withMessages(['participation_decision'=>'Le retrait d’une épreuve officielle demande une révision du programme.']);
   if($a->stand_id&&!Stand::whereKey($a->stand_id)->where('event_project_id',$a->event_project_id)->exists())throw ValidationException::withMessages(['stand_id'=>'Stand d’une autre édition.']);
   if($a->funding_scenario_id&&!Scenario::whereKey($a->funding_scenario_id)->where('event_project_id',$a->event_project_id)->exists())throw ValidationException::withMessages(['funding_scenario_id'=>'Scénario hors édition.']);
   if($a->isDirty('publication_level')&&$a->publication_level==='confirmed'&&!app(\App\Domain\Planning\Revelation::class)->confirmable($a))throw ValidationException::withMessages(['publication_level'=>'Épreuve, financement et préparation doivent être validés avant confirmation.']);
   Validator::make($a->getAttributes(),['participation_decision'=>'sometimes|in:challenge,stand_only','proposer_type'=>'required|in:organization,village,independent','planned_runs'=>'required|integer|min:1|max:10000','map_x'=>'nullable|numeric|between:0,100','map_y'=>'nullable|numeric|between:0,100'])->validate();
   if($a->exists && $a->isDirty(['terrace_id','spectator_terrace_id','map_x','map_y','rules','risk_category','access'])) {$a->risk_reviewed=false;if($a->status==='approved')$a->status='testing';}
   if($a->exists && $a->isDirty('event_project_id')) throw ValidationException::withMessages(['event_project_id'=>'Une épreuve reste liée à son édition.']);
   foreach(['terrace_id','spectator_terrace_id'] as $key) if($a->$key && !Terrace::whereKey($a->$key)->where('event_project_id',$a->event_project_id)->exists()) throw ValidationException::withMessages([$key=>'Choisissez une terrasse de cette édition.']);
   if(($a->map_x===null)!==($a->map_y===null)) throw ValidationException::withMessages(['map_x'=>'Les deux coordonnées sont nécessaires.']);
   if($a->map_x!==null){$t=Terrace::find($a->terrace_id);if(!$t||!TerraceGeometry::contains($t->boundary,$a->map_x,$a->map_y)) throw ValidationException::withMessages(['map_x'=>'Placez le point à l’intérieur de la terrasse choisie.']);}
  });
 }
}
