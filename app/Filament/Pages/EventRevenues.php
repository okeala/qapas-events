<?php

namespace App\Filament\Pages;

class EventRevenues extends EventWorkspace
{
    protected static ?string $navigationLabel = 'Recettes et offres';

    protected static ?string $title = 'Recettes et offres';

    protected static ?string $slug = 'event-revenues';

    protected static string|\UnitEnum|null $navigationGroup = 'V2 · Pilotage événementiel';

    protected static ?int $navigationSort = 3;

    protected string $module = 'revenues';
}
