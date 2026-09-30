<?php
namespace App\Filament\Resources\BarPartnershipResource\Pages;
use Filament\Actions\{CreateAction,Action};
class ManageRecords extends \Filament\Resources\Pages\ManageRecords {
 protected static string $resource=\App\Filament\Resources\BarPartnershipResource::class;
 protected function getHeaderActions(): array {return [CreateAction::make()->modalWidth('7xl')];}
}
