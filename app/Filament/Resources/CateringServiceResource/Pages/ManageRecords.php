<?php
namespace App\Filament\Resources\CateringServiceResource\Pages;
class ManageRecords extends \Filament\Resources\Pages\ManageRecords {
 protected static string $resource=\App\Filament\Resources\CateringServiceResource::class;
 protected function getHeaderActions(): array {return [\Filament\Actions\CreateAction::make()->modalWidth('7xl')];}
}
