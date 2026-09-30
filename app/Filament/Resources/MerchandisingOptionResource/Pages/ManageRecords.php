<?php
namespace App\Filament\Resources\MerchandisingOptionResource\Pages;
class ManageRecords extends \Filament\Resources\Pages\ManageRecords {protected static string $resource=\App\Filament\Resources\MerchandisingOptionResource::class;protected function getHeaderActions(): array {return [\Filament\Actions\CreateAction::make()];}}
