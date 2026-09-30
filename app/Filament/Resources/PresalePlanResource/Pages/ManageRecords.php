<?php
namespace App\Filament\Resources\PresalePlanResource\Pages;
class ManageRecords extends \Filament\Resources\Pages\ManageRecords {protected static string $resource=\App\Filament\Resources\PresalePlanResource::class;protected function getHeaderActions(): array {return [\Filament\Actions\CreateAction::make()];}}
