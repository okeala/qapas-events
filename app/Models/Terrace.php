<?php
namespace App\Models;
use App\Domain\Planning\TerraceGeometry;
use Illuminate\Validation\ValidationException;
class Terrace extends Record {
 protected function casts(): array {return ['boundary'=>'array','is_public'=>'boolean'];}
 public function eventProject(){return $this->belongsTo(EventProject::class);}
 public function activities(){return $this->hasMany(Activity::class);}
 protected static function booted(): void {
  parent::booted();
  static::saved(function(Terrace $t){
   if($t->wasChanged(['boundary','access'])) foreach(Activity::where('terrace_id',$t->id)->orWhere('spectator_terrace_id',$t->id)->get() as $a){$a->risk_reviewed=false;if($a->status==='approved')$a->status='testing';$a->save();}
  });
  static::saving(function(Terrace $t){
   TerraceGeometry::validate($t->boundary);
   if($t->exists && $t->isDirty('event_project_id')) throw ValidationException::withMessages(['event_project_id'=>'Une terrasse reste liée à son édition.']);
   if($t->exists && $t->isDirty('boundary')) foreach($t->activities()->whereNotNull('map_x')->get() as $a) if(!TerraceGeometry::contains($t->boundary,(float)$a->map_x,(float)$a->map_y)) throw ValidationException::withMessages(['boundary'=>'Déplacez les épreuves avant de réduire leur terrasse.']);
  });
 }
}
