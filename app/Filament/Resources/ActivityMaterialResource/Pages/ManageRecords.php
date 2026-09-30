<?php
namespace App\Filament\Resources\ActivityMaterialResource\Pages;
class ManageRecords extends \Filament\Resources\Pages\ManageRecords {
 protected static string $resource=\App\Filament\Resources\ActivityMaterialResource::class;
 public function getTabs(): array {
  $tabs=['official'=>\Filament\Schemas\Components\Tabs\Tab::make('QAPAS · toutes les épreuves')->modifyQueryUsing(fn(\Illuminate\Database\Eloquent\Builder $query)=>$query->whereHas('activity',fn($a)=>$a->where('track','official')))];
  foreach(\App\Models\Activity::where('track','official')->with('eventProject')->orderBy('event_project_id')->orderBy('name')->get() as $a)$tabs['activity-'.$a->id]=\Filament\Schemas\Components\Tabs\Tab::make($a->name)->modifyQueryUsing(fn(\Illuminate\Database\Eloquent\Builder $query)=>$query->where('activity_id',$a->id));
  $tabs['teams']=\Filament\Schemas\Components\Tabs\Tab::make('Équipes et autres')->modifyQueryUsing(fn(\Illuminate\Database\Eloquent\Builder $query)=>$query->whereHas('activity',fn($a)=>$a->where('track','!=','official')));return $tabs;
 }
 protected function getHeaderActions(): array {return [\Filament\Actions\CreateAction::make()];}
}
