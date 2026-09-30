<?php
namespace App\Filament\Resources\CabinProjectResource\Pages;
class ManageRecords extends \Filament\Resources\Pages\ManageRecords {
    protected static string $resource = \App\Filament\Resources\CabinProjectResource::class;
    protected function getHeaderActions(): array { return [\Filament\Actions\CreateAction::make()->modalWidth('7xl')]; }
}
