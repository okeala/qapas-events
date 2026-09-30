<?php
namespace App\Domain\Teams;
final class ExpertRoles {
 public const CODES=['cook','grafter','fruit_grower','vegetable_grower','excavator_operator','tractor_driver','accountant','endurance_athlete','strong_person','dexterity_expert','salesperson','storyteller','leader'];
 public static function labels(?string $locale=null): array {return collect(self::CODES)->mapWithKeys(fn($code)=>[$code=>__('events.roles.'.$code,[], $locale)])->all();}
 public static function seedSlots(\App\Models\Team $team): void {foreach(self::CODES as $code)$team->roleAssignments()->firstOrCreate(['role_code'=>$code]);}
}
