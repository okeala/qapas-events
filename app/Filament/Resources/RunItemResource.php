<?php
namespace App\Filament\Resources;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Forms\Components\{TextInput,Textarea,Select,Toggle,DateTimePicker,DatePicker};
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
class RunItemResource extends Resource {
 protected static ?string $model=\App\Models\RunItem::class;
 protected static ?string $modelLabel='Conducteur';
 protected static ?string $pluralModelLabel='Conducteur';
 protected static string|\UnitEnum|null $navigationGroup='5 · Exploiter';
 
 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([Select::make('event_project_id')->label('Édition')->relationship('eventProject','name')->searchable()->preload()->required()->disabledOn('edit'),TextInput::make('name')->label('Séquence')->maxLength(255)->required(),TextInput::make('owner')->label('Responsable')->maxLength(255)->required(),TextInput::make('location')->label('Zone')->maxLength(255),DateTimePicker::make('starts_at')->label('Début')->timezone('Europe/Lisbon')->required(),DateTimePicker::make('ends_at')->label('Fin')->timezone('Europe/Lisbon')->required()->after('starts_at'),Select::make('status')->label('État')->options(['planned'=>'Prévue','running'=>'En cours','done'=>'Terminée','cancelled'=>'Annulée'])->required(),Textarea::make('notes')->label('Consignes / retour expérience')->maxLength(10000)->rows(3)->columnSpanFull()]);}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('name')->label('Nom')->searchable()->wrap(),TextColumn::make('eventProject.name')->label('Édition'),TextColumn::make('status')->label('État')->badge(),TextColumn::make('updated_at')->label('Mise à jour')->dateTime('d/m/Y H:i')->sortable()])->defaultSort('updated_at','desc')->recordActions([EditAction::make()]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\RunItemResource\Pages\ManageRecords::route('/')];}
}
