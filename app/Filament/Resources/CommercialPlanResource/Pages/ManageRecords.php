<?php
namespace App\Filament\Resources\CommercialPlanResource\Pages;
class ManageRecords extends \Filament\Resources\Pages\ManageRecords {protected static string $resource=\App\Filament\Resources\CommercialPlanResource::class;protected function getHeaderActions(): array {return [\Filament\Actions\CreateAction::make()];}}
