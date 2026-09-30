<?php
namespace App\Filament\Resources\OutreachVisitResource\Pages;
class ManageRecords extends \Filament\Resources\Pages\ManageRecords {protected static string $resource=\App\Filament\Resources\OutreachVisitResource::class;protected function getHeaderActions(): array {return [\Filament\Actions\CreateAction::make()];}}
