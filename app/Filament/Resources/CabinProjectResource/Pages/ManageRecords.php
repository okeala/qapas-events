<?php
namespace App\Filament\Resources\CabinProjectResource\Pages;
class ManageRecords extends \Filament\Resources\Pages\ManageRecords {
    protected static string $resource = \App\Filament\Resources\CabinProjectResource::class;
    protected function getHeaderActions(): array { return [\Filament\Actions\Action::make('structure')->label('Évaluation interne')->modalHeading('Comparatif antérieur · toit à deux pans, hors variante monopente')->modalContent(fn()=>new \Illuminate\Support\HtmlString(\Illuminate\Support\Str::markdown(file_get_contents(base_path('docs/CABIN-STRUCTURE-PRELIMINARY.md')))))->modalSubmitAction(false)->modalCancelActionLabel('Fermer')->modalWidth('5xl'),\Filament\Actions\CreateAction::make()->modalWidth('7xl')]; }
}
