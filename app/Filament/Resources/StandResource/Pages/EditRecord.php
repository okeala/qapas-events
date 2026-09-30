<?php
namespace App\Filament\Resources\StandResource\Pages;
class EditRecord extends \Filament\Resources\Pages\EditRecord {
 protected static string $resource=\App\Filament\Resources\StandResource::class;
 public function hasCombinedRelationManagerTabsWithContent(): bool {return true;}
}
