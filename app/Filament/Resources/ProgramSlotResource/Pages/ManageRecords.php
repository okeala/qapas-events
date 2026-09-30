<?php
namespace App\Filament\Resources\ProgramSlotResource\Pages;
class ManageRecords extends \Filament\Resources\Pages\ManageRecords {
 protected static string $resource=\App\Filament\Resources\ProgramSlotResource::class;
 protected function getHeaderActions(): array {return [\Filament\Actions\CreateAction::make()->modalWidth('7xl')];}
}
