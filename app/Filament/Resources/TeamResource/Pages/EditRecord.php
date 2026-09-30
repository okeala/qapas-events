<?php
namespace App\Filament\Resources\TeamResource\Pages;
class EditRecord extends \Filament\Resources\Pages\EditRecord {
 protected static string $resource=\App\Filament\Resources\TeamResource::class;
 public function hasCombinedRelationManagerTabsWithContent(): bool {return true;}
}
