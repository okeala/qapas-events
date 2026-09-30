<?php
namespace App\Models;
class CostConsultation extends Record {
 protected function casts(): array {return ['sent_at'=>'datetime','reply_due_at'=>'date'];}
 public function eventProject(){return $this->belongsTo(EventProject::class);}
 public function costable(){return $this->morphTo();}
 public function quotes(){return $this->hasMany(SupplierQuote::class);}
 protected static function booted(): void {parent::booted();static::saving(function(self $c){
  $types=[BudgetLine::class,ActivityMaterial::class,SiteNeed::class];if(!in_array($c->costable_type,$types,true))throw \Illuminate\Validation\ValidationException::withMessages(['costable_type'=>'Type de poste non admis.']);
  if($c->exists&&$c->isDirty(['costable_id','costable_type','event_project_id']))throw \Illuminate\Validation\ValidationException::withMessages(['name'=>'La consultation reste liée à son poste.']);
  $target=$c->costable;$project=app(\App\Domain\Procurement\Consultations::class)->project($target);if(!$project||$project->id!==$c->event_project_id||($target instanceof BudgetLine&&$target->kind!=='cost'))throw \Illuminate\Validation\ValidationException::withMessages(['name'=>'Rattacher une dépense de cette édition.']);
 });}
}
