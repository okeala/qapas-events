<?php
namespace App\Models;
use App\Domain\Planning\TerraceGeometry;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
class Activity extends Record {
 protected $attributes=['proposer_type'=>'organization','track'=>'public','capacity'=>0,'risk_reviewed'=>false,'status'=>'idea','risk_category'=>'manual','access'=>'team','is_public'=>false,'broadcast_planned'=>false,'materials_complete'=>false,'planned_runs'=>1,'sort_order'=>100];
 protected function casts(): array {return ['risk_reviewed'=>'boolean','is_public'=>'boolean','broadcast_planned'=>'boolean','materials_complete'=>'boolean','map_x'=>'float','map_y'=>'float'];}
 public function eventProject(){return $this->belongsTo(EventProject::class);}
 public function terrace(){return $this->belongsTo(Terrace::class);}
 public function spectatorTerrace(){return $this->belongsTo(Terrace::class,'spectator_terrace_id');}
 public function materials(){return $this->hasMany(ActivityMaterial::class);}
 public function costReport(): array {return app(\App\Domain\Finance\ActivityCost::class)->calculate($this);}
 protected static function booted(): void {
  parent::booted();static::saving(function(Activity $a){
   Validator::make($a->getAttributes(),['planned_runs'=>'required|integer|min:1|max:10000','map_x'=>'nullable|numeric|between:0,100','map_y'=>'nullable|numeric|between:0,100'])->validate();
   if($a->exists && $a->isDirty(['terrace_id','spectator_terrace_id','map_x','map_y','rules','risk_category','access'])) {$a->risk_reviewed=false;if($a->status==='approved')$a->status='testing';}
   if($a->exists && $a->isDirty('event_project_id')) throw ValidationException::withMessages(['event_project_id'=>'Une épreuve reste liée à son édition.']);
   foreach(['terrace_id','spectator_terrace_id'] as $key) if($a->$key && !Terrace::whereKey($a->$key)->where('event_project_id',$a->event_project_id)->exists()) throw ValidationException::withMessages([$key=>'Choisissez une terrasse de cette édition.']);
   if(($a->map_x===null)!==($a->map_y===null)) throw ValidationException::withMessages(['map_x'=>'Les deux coordonnées sont nécessaires.']);
   if($a->map_x!==null){$t=Terrace::find($a->terrace_id);if(!$t||!TerraceGeometry::contains($t->boundary,$a->map_x,$a->map_y)) throw ValidationException::withMessages(['map_x'=>'Placez le point à l’intérieur de la terrasse choisie.']);}
  });
 }
}
