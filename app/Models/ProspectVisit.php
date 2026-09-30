<?php
namespace App\Models;
use Illuminate\Support\Facades\{DB,Validator};
class ProspectVisit extends Record {
 protected function casts(): array {return ['visited_at'=>'datetime','followup_at'=>'datetime'];}
 public function prospect(){return $this->belongsTo(Prospect::class);}
 public function save(array $options=[]){return DB::transaction(function()use($options){Prospect::whereKey($this->prospect_id)->lockForUpdate()->firstOrFail();return parent::save($options);},3);}
 protected static function booted(): void {parent::booted();static::saving(function(self $v){abort_unless(auth('admin')->user()?->is_active,403);if($v->exists)throw \Illuminate\Validation\ValidationException::withMessages(['visit'=>'Compte rendu conservé ; ajouter une visite rectificative.']);$v->admin_id=auth('admin')->id();Validator::make($v->getAttributes(),['visited_at'=>'required|date|before_or_equal:now','outcome'=>'required|in:absent,contacted,qualified,declined','feedback'=>'required|string|max:10000','followup_at'=>'nullable|date|after_or_equal:visited_at','next_action'=>'required_with:followup_at|nullable|string|max:5000','email'=>'nullable|email|max:254','phone'=>'nullable|string|max:80','contact_name'=>'nullable|string|max:255','contact_source'=>'required_with:email,phone,contact_name|nullable|string|max:3000'])->validate();});
 static::created(function(self $v){$p=$v->prospect;if($p->last_visited_at&&$p->last_visited_at->gt($v->visited_at))return;$data=['last_visited_at'=>$v->visited_at,'followup_at'=>$v->followup_at,'next_action'=>$v->next_action,'status'=>$v->outcome==='absent'?'to_call':$v->outcome];foreach(['contact_name','phone','email','contact_source'] as $field)if(filled($v->$field))$data[$field]=$v->$field;$p->update($data);});}
}
