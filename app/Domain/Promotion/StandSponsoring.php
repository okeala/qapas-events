<?php
namespace App\Domain\Promotion;
use App\Models\{Stand,StandPartner};
use Illuminate\Validation\ValidationException;
final class StandSponsoring {
 public function units(StandPartner $partner): int {return $partner->main_slot?5:(int)$partner->share_units;}
 public function report(Stand $stand,?int $except=null): array {
  $partners=$stand->partners()->whereIn('status',['agreed','active'])->when($except,fn($q)=>$q->whereKeyNot($except))->get()->filter(fn($p)=>$this->units($p)>0);
  $used=$partners->sum(fn($p)=>$this->units($p));$price=$stand->sponsorship_total_cents;
  return ['total_cents'=>$price,'unit_cents'=>$price===null?null:intdiv($price,5),'used'=>$used,'available'=>max(0,5-$used),'exclusive_available'=>$used===0,'partners'=>$partners];
 }
 public function quote(Stand $stand,int $units,bool $exclusive): ?int {
  if($units<1||$units>5||($exclusive&&$units!==5)||(!$exclusive&&$units===5))throw ValidationException::withMessages(['share_units'=>__('sponsoring.invalid_share')]);
  $r=$this->report($stand);if($units>$r['available']||($exclusive&&!$r['exclusive_available']))throw ValidationException::withMessages(['share_units'=>__('sponsoring.unavailable')]);
  return $r['unit_cents']===null?null:$r['unit_cents']*$units;
 }
 public function validatePartner(StandPartner $p): void {
  $units=$this->units($p);
  if($p->main_slot&&$p->share_units)throw ValidationException::withMessages(['share_units'=>'Ne pas additionner un ancien parrain exclusif et de nouvelles parts.']);
  if($p->exclusive_sponsorship&&$units!==5)throw ValidationException::withMessages(['share_units'=>'Le parrainage exclusif occupe les cinq parts.']);
  if(!$p->main_slot&&!$p->exclusive_sponsorship&&$units===5)throw ValidationException::withMessages(['exclusive_sponsorship'=>'Cinq parts pour un même parrain : choisir le parrainage exclusif du stand.']);
  if(in_array($p->status,['agreed','active'],true)&&$units){
   $r=$this->report($p->stand,$p->exists?$p->id:null);
   if($units>$r['available'])throw ValidationException::withMessages(['share_units'=>'Les accords dépasseraient les cinq parts de ce stand.']);
   if($p->share_units){
    if(blank($p->evidence))throw ValidationException::withMessages(['evidence'=>'Accord et répartition de visibilité à documenter.']);
    if(!$p->exists||!in_array($p->getOriginal('status'),['agreed','active'],true))$p->reference_total_cents=$p->stand->sponsorship_total_cents;
    if(!$p->reference_total_cents||$p->reference_total_cents%5)throw ValidationException::withMessages(['reference_total_cents'=>'Tarif total du stand renseigné et divisible en cinq parts exactes requis.']);
    if($p->exists&&in_array($p->getOriginal('status'),['agreed','active'],true)&&$p->isDirty(['share_units','exclusive_sponsorship','reference_total_cents']))throw ValidationException::withMessages(['share_units'=>'Conserver l’accord existant ; revenir en prospection pour préparer une nouvelle répartition documentée.']);
   }
  }
 }
}
