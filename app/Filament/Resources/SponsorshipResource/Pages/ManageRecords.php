<?php
namespace App\Filament\Resources\SponsorshipResource\Pages;
class ManageRecords extends \Filament\Resources\Pages\ManageRecords {protected static string $resource=\App\Filament\Resources\SponsorshipResource::class;protected function getHeaderActions(): array {return [\Filament\Actions\CreateAction::make()];}}
