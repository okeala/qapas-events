<?php
namespace App\Filament\Resources\DistributorResource\Pages;
class ManageRecords extends \Filament\Resources\Pages\ManageRecords {protected static string $resource=\App\Filament\Resources\DistributorResource::class;protected function getHeaderActions(): array {return [\Filament\Actions\CreateAction::make()];}}
