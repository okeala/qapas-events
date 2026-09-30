<?php
namespace App\Filament\Resources\ScenarioResource\Pages;
class EditRecord extends \Filament\Resources\Pages\EditRecord {
 protected static string $resource=\App\Filament\Resources\ScenarioResource::class;
 public function hasCombinedRelationManagerTabsWithContent(): bool {return true;}
}
