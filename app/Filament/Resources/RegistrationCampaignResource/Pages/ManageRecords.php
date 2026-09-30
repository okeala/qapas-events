<?php
namespace App\Filament\Resources\RegistrationCampaignResource\Pages;
class ManageRecords extends \Filament\Resources\Pages\ManageRecords {
 protected static string $resource=\App\Filament\Resources\RegistrationCampaignResource::class;
 protected function getHeaderActions(): array {return [\Filament\Actions\CreateAction::make()];}
}
