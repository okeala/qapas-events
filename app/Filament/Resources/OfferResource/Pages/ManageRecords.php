<?php
namespace App\Filament\Resources\OfferResource\Pages;
class ManageRecords extends \Filament\Resources\Pages\ManageRecords {
 protected static string $resource=\App\Filament\Resources\OfferResource::class;
 protected function getHeaderActions(): array {return [\Filament\Actions\CreateAction::make()];}
}
