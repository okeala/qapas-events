<?php
namespace App\Filament\Pages;
use App\Models\Scenario;
class UnitCosting extends \Filament\Pages\Page {
 protected static ?string $title='Chiffrage par unité';
 protected static string|\UnitEnum|null $navigationGroup='2 · Chiffrer';
 protected static ?int $navigationSort=1;
 protected string $view='filament.pages.unit-costing';
 public ?string $scenarioId=null;
 public static function canAccess(): bool {return auth('admin')->user()?->is_active===true;}
 public function mount(): void {abort_unless(self::canAccess(),403);$this->scenarioId=Scenario::orderByRaw("CASE WHEN template_key = 'costing-rental-experts-v1' THEN 0 WHEN template_key = 'costing-two-days-v1' THEN 1 ELSE 2 END")->orderByDesc('id')->value('public_id');}
 public function scenarios(){abort_unless(self::canAccess(),403);return Scenario::with('eventProject')->orderByDesc('id')->get();}
 public function scenario(): ?Scenario {abort_unless(self::canAccess(),403);return Scenario::where('public_id',$this->scenarioId)->first();}
 public function costing(): ?array {$s=$this->scenario();return $s?app(\App\Domain\Finance\UnitCosting::class)->calculate($s):null;}
}
