<?php
namespace App\Filament\Resources;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Forms\Components\{TextInput,Textarea,Select,Toggle,DateTimePicker,DatePicker};
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
class TeamResource extends Resource {
 protected static ?string $model=\App\Models\Team::class;
 protected static ?string $modelLabel='Équipes et élections';
 protected static ?string $pluralModelLabel='Équipes et élections';
 protected static string|\UnitEnum|null $navigationGroup='4 · Préparer';

 protected static ?int $navigationSort=40;

 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([Select::make('event_project_id')->label('Édition')->relationship('eventProject','name')->searchable()->preload()->required()->disabledOn('edit'),TextInput::make('name')->label('Équipe')->maxLength(255)->required(),TextInput::make('freguesia')->label('Freguesia')->maxLength(255)->required(),TextInput::make('representative')->label('Représentant / cocontractant à vérifier')->maxLength(255),TextInput::make('ballot_location')->label('Junta ou autre lieu accepté')->maxLength(255),Textarea::make('role_cumulation_evidence')->label('Justification des cumuls éventuels')->helperText('Six rôles indispensables : cuisinier, pelliste, tractoriste, comptable, athlète et musicien. Les autres talents complètent l’équipe. Si une personne en couvre plusieurs : compatibilité des horaires, du vote et des responsabilités à documenter.')->columnSpanFull(),Textarea::make('election_protocol')->label('Protocole : volontaires consentants, éligibilité, calendrier, scrutin et recours')->maxLength(10000)->rows(3)->columnSpanFull(),Textarea::make('election_minutes')->label('Référence du procès-verbal (sans publier les bulletins nominatifs)')->maxLength(10000)->rows(3)->columnSpanFull(),Select::make('status')->label('État')->options(['forming'=>'Mobilisation','nominations'=>'Candidatures consenties','ballot'=>'Vote local','elected'=>'Résultat consigné'])->required()]);}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('name')->label('Nom')->searchable()->wrap(),TextColumn::make('eventProject.name')->label('Édition'),TextColumn::make('status')->label('État du choix local')->badge(),TextColumn::make('coverage')->label('Rôles experts')->state(fn($record)=>$record->composition()['covered'].' / '.$record->composition()['required']),TextColumn::make('composition_ready')->label('Composition')->state(fn($record)=>$record->composition()['complete']?'Complète et vérifiée':'À compléter / cumul à examiner'),TextColumn::make('updated_at')->label('Mise à jour')->dateTime('d/m/Y H:i')->sortable()])->defaultSort('updated_at','desc')->recordActions([\Filament\Actions\Action::make('compose')->label('Composer l’équipe')->url(fn($record)=>static::getUrl('edit',['record'=>$record])),EditAction::make()]);}
 public static function getRelations(): array {return [\App\Filament\RelationManagers\TeamRolesRelationManager::class];}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\TeamResource\Pages\ManageRecords::route('/'),'edit'=>\App\Filament\Resources\TeamResource\Pages\EditRecord::route('/{record}/edit')];}
}
