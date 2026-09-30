<?php
namespace App\Models;
use Illuminate\Validation\ValidationException;
class Idea extends Record {
 protected function casts(): array {return ['depends_on'=>'array','is_public'=>'boolean','due_at'=>'date'];}
 public function eventProject(){return $this->belongsTo(EventProject::class);}
 public function isValidated(array $seen=[]): bool {
  if(in_array($this->id,$seen)||$this->status!=='validated'||blank($this->owner)||blank($this->evidence))return false;$seen[]=$this->id;
  if(in_array($this->template_key,['forquilha-prefund','forquilha-ready'],true)){$award=app(\App\Domain\Awards\Forquilha::class)->report($this->eventProject);if(!$award[$this->template_key==='forquilha-prefund'?'secured':'production_ready'])return false;}
  foreach($this->depends_on??[] as $id){$d=self::find($id);if(!$d||$d->event_project_id!==$this->event_project_id||!$d->isValidated($seen))return false;}return true;
 }
 protected static function booted(): void {parent::booted();static::saving(function(self $i){
  if(!$i->exists)$i->sort_order=$i->sort_order??((int)self::where('event_project_id',$i->event_project_id)->max('sort_order')+1);
  $ids=$i->depends_on??[];if(!is_array($ids)||count($ids)>20)throw ValidationException::withMessages(['depends_on'=>'20 dépendances au maximum.']);
  $walk=function($id,$seen)use(&$walk,$i){if(in_array($id,$seen))throw ValidationException::withMessages(['depends_on'=>'Cycle interdit.']);$d=self::find($id);if(!$d||$d->event_project_id!==$i->event_project_id)throw ValidationException::withMessages(['depends_on'=>'Étape hors édition.']);foreach($d->depends_on??[] as $next)$walk($next,[...$seen,$id]);};
  foreach($ids as $id)$walk($id,[$i->id]);
  if($i->status==='validated'&&!$i->isValidated())throw ValidationException::withMessages(['status'=>'Responsable, preuve et étapes préalables validées requis.']);
 });}
}
