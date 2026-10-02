<?php

namespace App\Filament\Pages;

class EventCommunications extends EventWorkspace
{
    protected static ?string $navigationLabel = 'Communication';

    protected static ?string $title = 'Communication';

    protected static ?string $slug = 'event-communications';

    protected static string|\UnitEnum|null $navigationGroup = 'V2 · Pilotage événementiel';

    protected static ?int $navigationSort = 5;

    protected string $module = 'communications';
}
