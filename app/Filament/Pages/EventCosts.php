<?php

namespace App\Filament\Pages;

class EventCosts extends EventWorkspace
{
    protected static ?string $navigationLabel = 'Coûts';

    protected static ?string $title = 'Coûts';

    protected static ?string $slug = 'event-costs';

    protected static string|\UnitEnum|null $navigationGroup = 'V2 · Pilotage événementiel';

    protected static ?int $navigationSort = 2;

    protected string $module = 'costs';
}
