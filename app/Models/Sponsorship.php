<?php
namespace App\Models;
use Illuminate\Support\Facades\{DB,Validator};
use Illuminate\Validation\ValidationException;
class Sponsorship extends Record {
 public const AWARD_ROLES=['prize'=>'Parrain des 500 €','trophy_design'=>'Conception du trophée','trophy_supply'=>'Fourniture / fabrication','trophy_paint'=>'Peinture / finition'];
 protected $attributes=['scope'=>'activity','purpose'=>'general','status'=>'prospecting','is_public'=>false];
 protected function casts(): array {return ['signage_feature_ids'=>'array','is_public'=>'boolean','agreed_at'=>'datetime','print_approved_at'=>'datetime','delivery_due_at'=>'datetime','delivered_at'=>'datetime'];}
 public function eventProject(){return $this->belongsTo(EventProject::class);}
 public function activity(){return $this->belongsTo(Activity::class);}
 public function prospect(){return $this->belongsTo(Prospect::class);}
 public function budgetLine(){return $this->belongsTo(BudgetLine::class);}
 public function deliveryCostLine(){return $this->belongsTo(BudgetLine::class,'delivery_cost_line_id');}
 public function deliveryComplete(): bool {
  $l=$this->deliveryCostLine;return $this->agreed()&&filled($this->delivery_terms)&&$this->delivery_due_at&&$this->delivered_at&&!$this->delivered_at->isFuture()&&filled($this->delivery_evidence)&&$l&&$l->kind==='cost'&&!$l->superseded_by_id&&$l->scenario->event_project_id===$this->event_project_id&&!$l->scenario->is_archived&&$l->forecast_quantity>0&&$l->unit_gross_cents!==null&&$l->vat_basis_points!==null&&$l->pricing_status==='confirmed';
 }
 public function agreed(): bool {return $this->status==='agreed'&&$this->agreed_at&&!$this->agreed_at->isFuture()&&filled($this->agreement_evidence)&&filled($this->sponsor_name);}
 public function visible(): bool {return $this->agreed()&&$this->is_public&&filled($this->web_rights)&&$this->eventProject?->is_public&&(!$this->activity_id||$this->activity?->publicVisible());}
 public function funding(): array {
  $line=$this->budgetLine;$cash=$this->agreed()&&$line?->verified()?min($this->cash_pledged_cents??0,$line->unit_gross_cents*$line->paid_quantity):0;$cost=$this->activity?->costReport();
  return ['agreed'=>$this->agreed(),'paid_cents'=>$cash,'activity_cost_cents'=>$cost['gross_cents']??null,'complete'=>$cost['complete']??false,'remaining_cents'=>$cost?max(0,$cost['gross_cents']-$cash):null];
 }
 public function save(array $options=[]){return DB::transaction(function()use($options){EventProject::whereKey($this->event_project_id)->lockForUpdate()->firstOrFail();return parent::save($options);},3);}
 protected static function booted(): void {parent::booted();static::saving(function(self $s){if($s->isDirty('delivery_cost_line_id'))$s->unsetRelation('deliveryCostLine');if($s->isDirty('budget_line_id'))$s->unsetRelation('budgetLine');if($s->isDirty('activity_id'))$s->unsetRelation('activity');if($s->isDirty('prospect_id'))$s->unsetRelation('prospect');
  Validator::make($s->getAttributes(),['scope'=>'in:activity,main,secondary,signage,award,welcome_pack','purpose'=>'in:general,cups,prize,trophy_design,trophy_supply,trophy_paint,shirts','status'=>'in:prospecting,contacted,agreed,cancelled','secondary_slot'=>'nullable|integer|between:1,3','cash_pledged_cents'=>'nullable|integer|between:0,1000000000','website_url'=>'nullable|url:https|max:255'])->validate();
  if($s->exists&&$s->isDirty('event_project_id'))throw ValidationException::withMessages(['event_project_id'=>'Conserver l’édition.']);
  foreach(['activity'=>Activity::class,'prospect'=>Prospect::class] as $key=>$class)if($s->{$key.'_id'}&&!$class::whereKey($s->{$key.'_id'})->where('event_project_id',$s->event_project_id)->exists())throw ValidationException::withMessages([$key.'_id'=>'Objet d’une autre édition.']);
  foreach($s->signage_feature_ids??[] as $id)if(!SiteFeature::whereKey($id)->where('event_project_id',$s->event_project_id)->exists())throw ValidationException::withMessages(['signage_feature_ids'=>'Plaque hors édition.']);
  if($s->scope==='signage'&&$s->status==='agreed'&&(!count($s->signage_feature_ids??[])||blank($s->signage_deliverables)))throw ValidationException::withMessages(['signage_feature_ids'=>'Emplacements exacts, dimensions, durée et BAT des plaques à convenir.']);
  if(($s->scope==='award')!==array_key_exists($s->purpose,self::AWARD_ROLES)||($s->scope==='welcome_pack')!==($s->purpose==='shirts'))throw ValidationException::withMessages(['purpose'=>'Affectation incompatible avec ce périmètre.']);
  if($s->delivery_cost_line_id&&(!$s->deliveryCostLine||$s->deliveryCostLine->scenario->event_project_id!==$s->event_project_id||$s->deliveryCostLine->kind!=='cost'||$s->deliveryCostLine->superseded_by_id))throw ValidationException::withMessages(['delivery_cost_line_id'=>'Coût actif de cette édition requis.']);
  if($s->status==='agreed'&&$s->scope==='award'&&$s->purpose==='prize'&&($s->cash_pledged_cents??0)<=0)throw ValidationException::withMessages(['cash_pledged_cents'=>'Le prix exige un financement monétaire ; un apport en nature ne remplace pas les 500 €.']);
  if($s->status==='agreed'&&$s->scope==='award'&&$s->purpose!=='prize'&&(blank($s->delivery_terms)||!$s->delivery_due_at||!$s->delivery_cost_line_id))throw ValidationException::withMessages(['delivery_terms'=>'Livrable, coût lié et date limite requis pour chaque contribution au trophée.']);
  if($s->exists&&$s->getOriginal('delivered_at')&&$s->isDirty(['scope','purpose','sponsor_name','delivery_cost_line_id','delivery_terms','delivery_due_at','agreement_evidence'])){$s->delivered_at=null;$s->delivery_evidence=null;}
  if($s->delivered_at&&!$s->deliveryComplete())throw ValidationException::withMessages(['delivered_at'=>'Réception passée, preuve de conformité, accord et coût validé (y compris gratuité justifiée) requis.']);
  if($s->scope==='activity'&&(!$s->activity||$s->activity->track!=='official'))throw ValidationException::withMessages(['activity_id'=>'Choisir une épreuve officielle.']);
  if($s->scope!=='activity'&&$s->activity_id)throw ValidationException::withMessages(['activity_id'=>'L’épreuve est réservée au parrainage d’épreuve.']);
  if($s->purpose==='cups'&&$s->scope!=='secondary')throw ValidationException::withMessages(['scope'=>'Les gobelets occupent un des trois slots secondaires.']);
  if($s->budget_line_id&&(!$s->budgetLine||$s->budgetLine->scenario->event_project_id!==$s->event_project_id||$s->budgetLine->kind!=='revenue'))throw ValidationException::withMessages(['budget_line_id'=>'Recette existante de cette édition requise ; ne pas la recopier.']);
  if($s->status==='agreed'&&(!$s->agreed()||blank($s->web_rights)||($s->cash_pledged_cents===null&&blank($s->in_kind))))throw ValidationException::withMessages(['status'=>'Accord daté, sponsor nommé, droits web et montant/apport documentés requis.']);
  $s->slot_key=null;if($s->status==='agreed'){$s->slot_key=match($s->scope){'main'=>'main','secondary'=>'secondary-'.$s->secondary_slot,'signage'=>null,'award'=>'award-'.$s->purpose,'welcome_pack'=>'welcome-pack-shirts',default=>'activity-'.$s->activity_id};if($s->scope==='secondary'&&!$s->secondary_slot)throw ValidationException::withMessages(['secondary_slot'=>'Choisir l’un des trois slots.']);if($s->slot_key&&self::where('event_project_id',$s->event_project_id)->where('slot_key',$s->slot_key)->when($s->exists,fn($q)=>$q->whereKeyNot($s->id))->exists())throw ValidationException::withMessages(['secondary_slot'=>'Ce rang est déjà attribué par un accord actif.']);if($s->purpose==='cups'&&self::where('event_project_id',$s->event_project_id)->where('purpose','cups')->where('status','agreed')->when($s->exists,fn($q)=>$q->whereKeyNot($s->id))->exists())throw ValidationException::withMessages(['purpose'=>'Un sponsor gobelets est déjà convenu.']);}
  if($s->print_approved_at&&(!$s->agreed()||$s->print_approved_at->isFuture()||blank($s->print_evidence)))throw ValidationException::withMessages(['print_approved_at'=>'Accord et BAT daté du lot imprimé requis ; aucune rétroactivité sur les anciens tirages.']);
 });}
}
