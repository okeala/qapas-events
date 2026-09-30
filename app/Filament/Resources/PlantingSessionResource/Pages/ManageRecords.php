<?php
namespace App\Filament\Resources\PlantingSessionResource\Pages;
class ManageRecords extends \Filament\Resources\Pages\ManageRecords {protected static string $resource=\App\Filament\Resources\PlantingSessionResource::class;protected function getHeaderActions(): array {return [\Filament\Actions\CreateAction::make()];}}
