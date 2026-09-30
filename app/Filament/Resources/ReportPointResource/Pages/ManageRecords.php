<?php
namespace App\Filament\Resources\ReportPointResource\Pages;
use Filament\Actions\{CreateAction,Action};
class ManageRecords extends \Filament\Resources\Pages\ManageRecords {
 protected static string $resource=\App\Filament\Resources\ReportPointResource::class;
 protected function getHeaderActions(): array {return [Action::make('prepare')->label('Préparer les QR des stands et lieux-clés')->action(function(){foreach(\App\Models\EventProject::all() as $p)\App\Models\ReportPoint::prepare($p);}),CreateAction::make()->modalWidth('7xl')];}
}
