<?php

namespace App\Filament\Pages;

class EventPlan extends EventWorkspace
{
    protected static ?string $navigationLabel = 'Plan et carte';

    protected static ?string $title = 'Plan et carte';

    protected static ?string $slug = 'event-plan';

    protected static string|\UnitEnum|null $navigationGroup = 'V2 · Pilotage événementiel';

    protected static ?int $navigationSort = 1;

    protected string $module = 'plan';
}
