<?php
namespace App\Filament\Resources\DrawingContestResource\Pages;
use Filament\Actions\{CreateAction,Action};
class ManageRecords extends \Filament\Resources\Pages\ManageRecords {
 protected static string $resource=\App\Filament\Resources\DrawingContestResource::class;
 protected function getHeaderActions(): array {return [CreateAction::make()->modalWidth('7xl')];}
}
