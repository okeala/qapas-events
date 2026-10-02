<?php

namespace App\Filament\Pages;

class EventBalance extends EventWorkspace
{
    protected static ?string $navigationLabel = 'Bilan et décisions';

    protected static ?string $title = 'Bilan et décisions';

    protected static ?string $slug = 'event-balance';

    protected static string|\UnitEnum|null $navigationGroup = 'V2 · Pilotage événementiel';

    protected static ?int $navigationSort = 4;

    protected string $module = 'balance';
}
