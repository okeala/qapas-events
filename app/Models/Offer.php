<?php
namespace App\Models;
class Offer extends Record {
 public const PROSPECT_TYPES=['independent'=>'Indépendants / exposants','relay'=>'Cafés et points-relais','junta'=>'Juntas de freguesia','team'=>'Équipes participantes','sponsor'=>'Marques et sponsors','public'=>'Visiteurs et candidats'];
protected function casts(): array {return ['benefits'=>'array','preview_current'=>'boolean','is_public'=>'boolean','is_founder'=>'boolean','founder_deadline'=>'date'];}
 public function discountVisible(): bool {return $this->is_founder&&$this->price_gross_cents!==null&&$this->regular_price_gross_cents>$this->price_gross_cents&&filled($this->regular_price_evidence)&&$this->capacity>0&&$this->founder_deadline&&!$this->founder_deadline->isPast();}
 protected static function booted(): void {parent::booted();static::saving(function(self $o){if(!$o->prospect_type)$o->prospect_type=match($o->kind){"stand"=>"independent","relay"=>"relay","support"=>"public",default=>"sponsor"};\Illuminate\Support\Facades\Validator::make($o->getAttributes(),["prospect_type"=>"in:".implode(",",array_keys(self::PROSPECT_TYPES))])->validate();foreach($o->benefits??[] as $b){$url=$b['url']??null;if($url&&!str_starts_with($url,'/')&&!str_starts_with($url,'https://'))throw \Illuminate\Validation\ValidationException::withMessages(['benefits'=>'Liens HTTPS ou relatifs uniquement.']);if($url&&str_starts_with($url,'//'))throw \Illuminate\Validation\ValidationException::withMessages(['benefits'=>'Lien relatif local attendu.']);}});}
 public function eventProject() {return $this->belongsTo(EventProject::class);}
}
