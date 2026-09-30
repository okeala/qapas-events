<?php
namespace App\Filament\Resources\RunItemResource\Pages;

use Filament\Actions\{Action, CreateAction};

class ManageRecords extends \Filament\Resources\Pages\ManageRecords
{
    protected static string $resource = \App\Filament\Resources\RunItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('coordination')->label('Organiser la coordination terrain')
                ->modalHeading('Du planning à la coordination terrain')
                ->modalContent(fn () => view('filament.operations.coordination'))
                ->modalWidth('5xl')->modalSubmitAction(false)->modalCancelActionLabel('Fermer'),
            CreateAction::make(),
        ];
    }
}
