<?php
namespace App\Filament\Resources\BudgetLineResource\Pages;
class ManageRecords extends \Filament\Resources\Pages\ManageRecords {
 protected static string $resource=\App\Filament\Resources\BudgetLineResource::class;
 protected function getHeaderActions(): array {return [\Filament\Actions\CreateAction::make()];}
}
