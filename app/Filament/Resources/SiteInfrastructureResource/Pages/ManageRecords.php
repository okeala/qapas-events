<?php
namespace App\Filament\Resources\SiteInfrastructureResource\Pages;
class ManageRecords extends \Filament\Resources\Pages\ManageRecords {protected static string $resource=\App\Filament\Resources\SiteInfrastructureResource::class;protected function getHeaderActions(): array {return [\Filament\Actions\CreateAction::make()];}}
