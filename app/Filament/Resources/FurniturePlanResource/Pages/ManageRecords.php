<?php
namespace App\Filament\Resources\FurniturePlanResource\Pages;
class ManageRecords extends \Filament\Resources\Pages\ManageRecords {
 protected static string $resource=\App\Filament\Resources\FurniturePlanResource::class;
 protected function getHeaderActions(): array {return [\Filament\Actions\CreateAction::make()->modalWidth('7xl')];}
}
