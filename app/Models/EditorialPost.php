<?php
namespace App\Models;
use Illuminate\Validation\ValidationException;
class EditorialPost extends Record {
 protected $attributes=['status'=>'draft'];
 protected function casts(): array {return ['published_at'=>'datetime'];}
 public function eventProject(){return $this->belongsTo(EventProject::class);}
 public function visible(): bool {return $this->status==='published'&&$this->eventProject?->is_public&&$this->published_at&&$this->published_at->lte(now())&&filled($this->rights_evidence);}
 protected static function booted(): void {parent::booted();static::saving(function(self $p){
  if($p->exists&&$p->isDirty('event_project_id'))throw ValidationException::withMessages(['event_project_id'=>'Conserver l’édition.']);
  if($p->youtube_id&&!preg_match('/^[A-Za-z0-9_-]{11}$/',$p->youtube_id))throw ValidationException::withMessages(['youtube_id'=>'Identifiant vidéo YouTube de 11 caractères requis.']);
  if($p->exists&&$p->getOriginal('status')==='published'&&$p->isDirty(['name','title_pt','body_fr','body_pt','youtube_id','rights_evidence'])){$p->status='draft';$p->published_at=null;}
  if($p->status==='published'&&(!$p->published_at||$p->published_at->isFuture()||blank($p->body_fr)||blank($p->body_pt)||blank($p->title_pt)||blank($p->rights_evidence)))throw ValidationException::withMessages(['status'=>'Article FR/PT, date passée et droits image/musique/personnes requis.']);
 });}
}
