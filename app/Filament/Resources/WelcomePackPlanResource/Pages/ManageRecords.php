<?php
namespace App\Filament\Resources\WelcomePackPlanResource\Pages;
class ManageRecords extends \Filament\Resources\Pages\ManageRecords {protected static string $resource=\App\Filament\Resources\WelcomePackPlanResource::class;protected function getHeaderActions(): array {return [\Filament\Actions\CreateAction::make()];}}
