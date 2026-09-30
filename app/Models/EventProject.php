<?php
namespace App\Models;
class EventProject extends Record {
protected function casts(): array {return ['is_public'=>'boolean','starts_at'=>'datetime','ends_at'=>'datetime'];}
 public function scenarios() {return $this->hasMany(Scenario::class);}
 public function offers() {return $this->hasMany(Offer::class);}
 public function requirements() {return $this->hasMany(LegalRequirement::class);}
 public function teams() {return $this->hasMany(Team::class);}
 public function activities() {return $this->hasMany(Activity::class);}
 public function runItems() {return $this->hasMany(RunItem::class);}
 public function interests() {return $this->hasMany(Interest::class);}
 public function ideas() {return $this->hasMany(Idea::class);}
 public function incidents() {return $this->hasMany(Incident::class);}
}
