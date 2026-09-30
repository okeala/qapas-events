<?php
namespace App\Filament\Resources;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Forms\Components\{TextInput,Textarea,Select,Toggle,DateTimePicker,DatePicker};
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
class ScenarioResource extends Resource {
 protected static ?string $model=\App\Models\Scenario::class;
 protected static ?string $modelLabel='Scénarios';
 protected static ?string $pluralModelLabel='Scénarios';
 protected static string|\UnitEnum|null $navigationGroup='2 · Chiffrer';
 
 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([Select::make('event_project_id')->label('Édition')->relationship('eventProject','name')->searchable()->preload()->required()->disabledOn('edit'),TextInput::make('name')->label('Format')->maxLength(255)->required(),TextInput::make('months')->label('Mois de travail')->integer()->minValue(0)->maxValue(1000000000)->minValue(1)->maxValue(36)->required()->default(1),TextInput::make('target_surplus_cents')->label('Objectif QAPAS par mois (centimes)')->integer()->minValue(0)->maxValue(1000000000)->required()->default(400000),TextInput::make('organizer_net_monthly_cents')->label('Rémunération nette mensuelle prévue (centimes)')->integer()->minValue(0)->maxValue(1000000000)->required()->default(100000),TextInput::make('organizer_full_monthly_cents')->label('Coût mensuel COMPLET avec charges (centimes)')->integer()->minValue(0)->maxValue(1000000000)->gte('organizer_net_monthly_cents')->helperText('À obtenir du comptable ; ne pas ajouter une seconde ligne de rémunération au budget.'),TextInput::make('team_target')->label('Équipes envisagées')->integer()->minValue(0)->maxValue(1000000000)->required()->default(0),TextInput::make('stand_target')->label('Stands envisagés')->integer()->minValue(0)->maxValue(1000000000)->required()->default(0),Toggle::make('costs_complete')->label('Tous les coûts de ce scénario ont été recensés')->default(false),Textarea::make('assumptions')->label('Hypothèses, exclusions et incertitudes')->maxLength(10000)->rows(3)->columnSpanFull()]);}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('name')->label('Nom')->searchable()->wrap(),TextColumn::make('eventProject.name')->label('Édition'),TextColumn::make('months')->label('Mois'),TextColumn::make('updated_at')->label('Mise à jour')->dateTime('d/m/Y H:i')->sortable()])->defaultSort('updated_at','desc')->recordActions([EditAction::make()]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\ScenarioResource\Pages\ManageRecords::route('/')];}
}
