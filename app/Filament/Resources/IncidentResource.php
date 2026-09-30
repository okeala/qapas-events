<?php
namespace App\Filament\Resources;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Forms\Components\{TextInput,Textarea,Select,Toggle,DateTimePicker,DatePicker};
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
class IncidentResource extends Resource {
 protected static ?string $model=\App\Models\Incident::class;
 protected static ?string $modelLabel='Incidents et suivi';
 protected static ?string $pluralModelLabel='Incidents et suivi';
 protected static string|\UnitEnum|null $navigationGroup='5 · Exploiter';
 
 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([Select::make('event_project_id')->label('Édition')->relationship('eventProject','name')->searchable()->preload()->required()->disabledOn('edit'),TextInput::make('name')->label('Incident')->maxLength(255)->required(),TextInput::make('owner')->label('Responsable du suivi')->maxLength(255)->required(),Select::make('severity')->label('Niveau')->options(['info'=>'Information','attention'=>'Attention','stop'=>'Arrêt nécessaire'])->required(),Select::make('status')->label('État')->options(['open'=>'Ouvert','handled'=>'Pris en charge','closed'=>'Clôturé'])->required(),Textarea::make('action')->label('Actions et bilan · pas de données médicales nominatives')->maxLength(10000)->rows(3)->columnSpanFull()]);}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('name')->label('Nom')->searchable()->wrap(),TextColumn::make('eventProject.name')->label('Édition'),TextColumn::make('status')->label('État')->badge(),TextColumn::make('updated_at')->label('Mise à jour')->dateTime('d/m/Y H:i')->sortable()])->defaultSort('updated_at','desc')->recordActions([EditAction::make()]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\IncidentResource\Pages\ManageRecords::route('/')];}
}
