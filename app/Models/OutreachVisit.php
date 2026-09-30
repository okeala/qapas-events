<?php
namespace App\Models;
class OutreachVisit extends Record {
 protected $attributes=['junta_status'=>'to_visit','letters_delivered'=>0,'cards_delivered'=>0,'visit_order'=>100];
 protected function casts(): array {return ['planned_at'=>'datetime','visited_at'=>'datetime','meeting_at'=>'datetime','followup_at'=>'datetime'];}
 public function eventProject(){return $this->belongsTo(EventProject::class);}
 protected static function booted(): void {parent::booted();static::saving(function(self $v){
  \Illuminate\Support\Facades\Validator::make($v->getAttributes(),['name'=>'required|string|max:255','freguesia'=>'required|string|max:255','president_email'=>'nullable|email','treasurer_email'=>'nullable|email','cards_delivered'=>'integer|between:0,100000','letters_delivered'=>'integer|between:0,100000','junta_status'=>'in:to_visit,deposited,following_up,agreed,declined'])->validate();
  if($v->junta_status==='agreed'&&blank($v->junta_evidence))throw \Illuminate\Validation\ValidationException::withMessages(['junta_evidence'=>'Consigner l’accord de la junta.']);
  if($v->meeting_at&&($v->junta_status!=='agreed'||blank($v->prospective_relay)||blank($v->meeting_agreement)))throw \Illuminate\Validation\ValidationException::withMessages(['meeting_at'=>'Réunion confirmée après accord de la junta et du café hôte. Une prise de contact exploratoire reste possible dans la prochaine action.']);
  if(($v->letters_delivered||$v->cards_delivered)&&(!$v->visited_at||blank($v->delivery_evidence)))throw \Illuminate\Validation\ValidationException::withMessages(['delivery_evidence'=>'Dater et documenter le dépôt réel.']);
  if($v->exists&&$v->isDirty('event_project_id'))throw \Illuminate\Validation\ValidationException::withMessages(['event_project_id'=>'Conserver l’édition de cette tournée.']);
 });}
}
