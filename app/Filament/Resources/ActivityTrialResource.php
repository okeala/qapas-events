<?php
namespace App\Filament\Resources;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Forms\Components\{Select,TextInput,Textarea,DateTimePicker};
use Filament\Tables\{Table,Grouping\Group};
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
class ActivityTrialResource extends Resource {
 protected static ?string $model=\App\Models\ActivityTrial::class;
 protected static ?string $modelLabel='Essai';
 protected static ?string $pluralModelLabel='Répétitions et essais des défis';
 protected static string|\UnitEnum|null $navigationGroup='4 · Préparer';
 protected static ?int $navigationSort=15;
 public const STAGES=['official_rehearsal'=>'Répétition officielle avec les volontaires du village','local_test'=>'Essai dans la freguesia / chez le proposant','site_installation'=>'Installation et contrôle à la Quinta · semaine avant ouverture'];
 public static function fields(): array {return [Select::make('stage')->label('Étape')->options(self::STAGES)->required()->disabledOn('edit'),DateTimePicker::make('planned_at')->label('Prévu le')->timezone('Europe/Lisbon'),DateTimePicker::make('performed_at')->label('Réalisé le')->timezone('Europe/Lisbon')->maxDate(now()),TextInput::make('location')->label('Lieu effectif')->maxLength(255),TextInput::make('responsible')->label('Responsable des essais')->maxLength(255),TextInput::make('volunteers')->label('Nombre de volontaires présents')->integer()->minValue(1)->maxValue(1000),TextInput::make('duration_seconds')->label('Durée constatée en secondes')->integer()->minValue(1)->maxValue(86400),Select::make('result')->label('Résultat')->options(['planned'=>'À réaliser / à revalider','passed'=>'Essai validé','failed'=>'À corriger / non validé'])->default('planned')->required()->helperText('Une modification du compte rendu annule sa validation. Enregistrer puis revalider. Un changement du jeu ou de son implantation impose un nouvel examen.'),Textarea::make('evidence')->label('Compte rendu : jouabilité, sécurité, accessibilité, besoins, arbitrage et preuve')->maxLength(10000)->columnSpanFull(),Textarea::make('adjustments')->label('Corrections, responsable et délai')->maxLength(10000)->columnSpanFull()];}
 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([Select::make('activity_id')->label('Épreuve')->relationship('activity','name')->required()->searchable()->preload()->disabledOn('edit'),...self::fields()]);}
 public static function columns(): array {return [TextColumn::make('activity.name')->label('Épreuve')->searchable()->wrap(),TextColumn::make('stage')->label('Étape')->formatStateUsing(fn($state)=>self::STAGES[$state]??$state)->wrap(),TextColumn::make('planned_at')->label('Prévu')->dateTime('d/m/Y H:i'),TextColumn::make('performed_at')->label('Réalisé')->dateTime('d/m/Y H:i'),TextColumn::make('result')->label('Compte rendu')->badge(),TextColumn::make('preparation')->label('Situation actuelle du défi')->getStateUsing(fn($record)=>$record->activity->preparation()->label($record->activity))->wrap(),TextColumn::make('responsible')->label('Responsable')];}
 public static function table(Table $table): Table {return $table->columns(self::columns())->groups([Group::make('activity.name')->label('Épreuve')->collapsible(),Group::make('stage')->label('Étape')->collapsible()])->defaultGroup('activity.name')->recordActions([EditAction::make()]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\ActivityTrialResource\Pages\ManageRecords::route('/')];}
}
