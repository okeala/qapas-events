<?php
namespace App\Filament\Pages;
class Dashboard extends \Filament\Pages\Dashboard {
 protected static ?string $title='Décider du prochain palier';
 protected static ?string $navigationLabel='Vue de pilotage';
 protected static ?int $navigationSort=-1;
 protected string $view='filament.pages.dashboard';
 public function projects() {return \App\Models\EventProject::with(['scenarios.budgetLines'])->withCount(['interests','teams'])->get();}
}
