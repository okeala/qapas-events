<?php
namespace App\Filament\Resources\IdeaResource\Pages;
class ManageRecords extends \Filament\Resources\Pages\ManageRecords {
 protected static string $resource=\App\Filament\Resources\IdeaResource::class;
 protected function getHeaderActions(): array {return [\Filament\Actions\CreateAction::make()];}
}
