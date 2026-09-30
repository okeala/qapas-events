<?php
namespace App\Models;
use Illuminate\Validation\ValidationException;
class CommunityAward extends Record {
 protected $attributes=['status'=>'planned','next_host_status'=>'proposed','is_public'=>false];
 protected function casts(): array {return ['paid_at'=>'datetime','is_public'=>'boolean'];}
 public function eventProject(){return $this->belongsTo(EventProject::class);}
 public function budgetLine(){return $this->belongsTo(BudgetLine::class);}
 public function winner(){return $this->belongsTo(Team::class,'winner_team_id');}
 public function agreement(){if(!$this->winner)return null;return FreguesiaAgreement::where('event_project_id',$this->event_project_id)->whereHas('stand',fn($q)=>$q->where('freguesia',$this->winner->freguesia))->first();}
 protected static function booted(): void {parent::booted();static::saving(function(self $a){if($a->isDirty('winner_team_id'))$a->unsetRelation('winner');if($a->isDirty('budget_line_id'))$a->unsetRelation('budgetLine');if($a->isDirty('event_project_id'))$a->unsetRelation('eventProject');
  if($a->exists&&$a->isDirty('event_project_id'))throw ValidationException::withMessages(['event_project_id'=>'Conserver l’édition.']);
  if($a->winner_team_id&&$a->winner?->event_project_id!==$a->event_project_id)throw ValidationException::withMessages(['winner_team_id'=>'Équipe hors édition.']);
  $line=$a->budgetLine;if($a->budget_line_id&&(!$line||$line->scenario->event_project_id!==$a->event_project_id||$line->kind!=='cost'||$line->unit_gross_cents!==50000||$line->forecast_quantity!==1))throw ValidationException::withMessages(['budget_line_id'=>'Lier un unique coût de 500 € dans un scénario de cette édition.']);
  if(!in_array($a->status,['planned','awarded','paid'])||!in_array($a->next_host_status,['proposed','preparing','confirmed','alternative']))throw ValidationException::withMessages(['status'=>'État invalide.']);
  if($a->status!=='planned'&&(!$a->eventProject->ends_at||$a->eventProject->ends_at->isFuture()||!$a->winner||blank($a->results_evidence)||blank($a->recipient_entity)||blank($a->prize_project)||!$a->agreement()?->signed()))throw ValidationException::withMessages(['status'=>'Après clôture : résultat arbitré, convention de la freguesia gagnante, bénéficiaire juridique et fête de Noël à cofinancer requis.']);
  if($a->status==='paid'&&(!$line?->verified()||$line->paid_quantity!==1||!$a->paid_at||$a->paid_at->isFuture()||blank($a->payment_evidence)))throw ValidationException::withMessages(['paid_at'=>'Rapprocher le paiement réel des 500 € dans son unique ligne budgétaire.']);
  if($a->next_host_status==='confirmed'&&($a->status==='planned'||!$a->agreement()?->signed()||blank($a->next_host_evidence)))throw ValidationException::withMessages(['next_host_evidence'=>'Convention 2027, site, financement, autorisations et calendrier à documenter avant confirmation.']);
 });}
}
