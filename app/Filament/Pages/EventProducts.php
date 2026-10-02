<?php

namespace App\Filament\Pages;

class EventProducts extends EventWorkspace
{
    protected static ?string $navigationLabel = 'Produits et stocks';

    protected static ?string $title = 'Produits et stocks';

    protected static ?string $slug = 'event-products';

    protected static string|\UnitEnum|null $navigationGroup = 'V2 · Pilotage événementiel';

    protected static ?int $navigationSort = 6;

    protected string $module = 'products';
}
