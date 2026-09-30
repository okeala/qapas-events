<?php
namespace App\Filament\Resources\ActivityResource\Pages;
class EditRecord extends \Filament\Resources\Pages\EditRecord {
 protected static string $resource=\App\Filament\Resources\ActivityResource::class;
 public function hasCombinedRelationManagerTabsWithContent(): bool {return true;}
}
