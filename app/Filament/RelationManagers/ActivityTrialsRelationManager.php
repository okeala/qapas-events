<?php
namespace App\Filament\RelationManagers;
class ActivityTrialsRelationManager extends \Filament\Resources\RelationManagers\RelationManager {
 protected static string $relationship='trials';protected static ?string $title='Essais et répétitions';
 public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema {return $schema->columns(2)->components(\App\Filament\Resources\ActivityTrialResource::fields());}
 public function table(\Filament\Tables\Table $table): \Filament\Tables\Table {return $table->columns(\App\Filament\Resources\ActivityTrialResource::columns())->headerActions([\Filament\Actions\CreateAction::make()])->recordActions([\Filament\Actions\EditAction::make()]);}
}
