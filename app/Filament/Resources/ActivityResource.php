<?php
namespace App\Filament\Resources;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Forms\Components\{TextInput,Textarea,Select,Toggle,DateTimePicker,DatePicker};
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
class ActivityResource extends Resource {
 protected static ?string $model=\App\Models\Activity::class;
 protected static ?string $modelLabel='Épreuves et animations';
 protected static ?string $pluralModelLabel='Épreuves et animations';
 protected static string|\UnitEnum|null $navigationGroup='4 · Préparer';

 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([Select::make('event_project_id')->label('Édition')->relationship('eventProject','name')->searchable()->preload()->required()->disabledOn('edit'),TextInput::make('name')->label('Activité')->maxLength(255)->required(),Select::make('proposer_type')->label('Proposée par')->options(['organization'=>'Organisation','village'=>'Stand de freguesia','independent'=>'Stand indépendant'])->required(),TextInput::make('proposer_name')->label('Équipe / enseigne')->maxLength(255),Select::make('track')->label('Parcours')->options(['official'=>'Compétition officielle','public'=>'Essai grand public'])->required(),Textarea::make('rules')->label('Règles, score, accès et adaptation')->maxLength(10000)->rows(3)->columnSpanFull()->required(),TextInput::make('referee')->label('Arbitre ou responsable indépendant')->maxLength(255),TextInput::make('capacity')->label('Participants par créneau')->integer()->minValue(0)->maxValue(1000000000)->required()->default(0),Toggle::make('risk_reviewed')->label('Risques évalués et mesures confirmées'),Textarea::make('risk_evidence')->label('Évaluation, matériel, accessibilité, secours et créneau dédié')->maxLength(10000)->rows(3)->columnSpanFull(),Select::make('status')->label('État')->options(['idea'=>'Idée','testing'=>'À tester','approved'=>'Validée','suspended'=>'Suspendue'])->required()]);}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('name')->label('Nom')->searchable()->wrap(),TextColumn::make('eventProject.name')->label('Édition'),TextColumn::make('status')->label('État')->badge(),TextColumn::make('updated_at')->label('Mise à jour')->dateTime('d/m/Y H:i')->sortable()])->defaultSort('updated_at','desc')->recordActions([EditAction::make()]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\ActivityResource\Pages\ManageRecords::route('/')];}
}
