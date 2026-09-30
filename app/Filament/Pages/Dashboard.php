<?php
namespace App\Filament\Pages;
class Dashboard extends \Filament\Pages\Dashboard {
 protected static ?string $title='Décider du prochain palier';
 protected static ?string $navigationLabel='Vue de pilotage';
 protected static ?int $navigationSort=-1;
 protected string $view='filament.pages.dashboard';
 public function urgentIncidents(){return \App\Models\Incident::whereIn('status',['open','handled'])->orderByRaw("CASE WHEN severity = 'stop' THEN 0 ELSE 1 END")->orderBy('created_at')->limit(20)->get();}
 public function followups(){return \App\Models\Prospect::whereNotNull('followup_at')->orderBy('followup_at')->limit(30)->get();}
 public function projects() {return \App\Models\EventProject::with(['scenarios'=>fn($q)=>$q->where('is_archived',false)->with('budgetLines')])->withCount(['interests','teams'])->get();}
}
