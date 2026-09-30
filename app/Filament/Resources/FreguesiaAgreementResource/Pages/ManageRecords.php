<?php
namespace App\Filament\Resources\FreguesiaAgreementResource\Pages;
use Filament\Actions\{CreateAction,Action};
class ManageRecords extends \Filament\Resources\Pages\ManageRecords {
 protected static string $resource=\App\Filament\Resources\FreguesiaAgreementResource::class;
 protected function getHeaderActions(): array {return [CreateAction::make()->modalWidth('7xl')];}
}
