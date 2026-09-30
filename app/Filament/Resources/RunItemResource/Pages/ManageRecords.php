<?php
namespace App\Filament\Resources\RunItemResource\Pages;
class ManageRecords extends \Filament\Resources\Pages\ManageRecords {
 protected static string $resource=\App\Filament\Resources\RunItemResource::class;
 protected function getHeaderActions(): array {return [\Filament\Actions\CreateAction::make()];}
}
