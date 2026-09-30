<?php
namespace App\Filament\Resources;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\{Select,TextInput,Textarea,Toggle,DateTimePicker,Placeholder};
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\{EditAction,Action};
class DrawingEntryResource extends Resource {
 protected static ?string $model=\App\Models\DrawingEntry::class;
 protected static ?string $modelLabel='Dessins reçus et récompenses';
 protected static ?string $pluralModelLabel='Dessins reçus et récompenses';
 protected static string|\UnitEnum|null $navigationGroup='6 · Clôturer';
 protected static ?int $navigationSort=25;
 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([
 Select::make('drawing_contest_id')->label('Concours')->relationship('contest','name')->required()->live()->disabledOn('edit'),Select::make('stand_id')->label('Freguesia représentée')->options(fn(Get $get)=>\App\Models\Stand::where('event_project_id',\App\Models\DrawingContest::find($get('drawing_contest_id'))?->event_project_id)->where('kind','village')->pluck('name','id'))->required()->disabledOn('edit'),TextInput::make('name')->label('Code / prénom ou pseudonyme privé du dessin')->required()->maxLength(120),TextInput::make('age_band')->label('Tranche d’âge, sans date de naissance')->required()->maxLength(50),TextInput::make('artwork_reference')->label('Référence au dessin papier conservé')->required()->maxLength(255),Textarea::make('guardian_contact')->label('Contact privé du responsable légal')->required()->maxLength(1000),Textarea::make('guardian_evidence')->label('Accord de participation, notice et remise du dessin')->required()->maxLength(2000)->helperText('Aucune publication du nom, du dessin ou de la photo de l’enfant dans cette version. Consentement image distinct si nécessaire.'),Select::make('status')->label('État')->options(['received'=>'Reçu','eligible'=>'Recevable','winner'=>'Gagnant de la freguesia','withdrawn'=>'Retiré'])->default('received')->required(),Textarea::make('jury_decision')->label('Décision et départage motivés'),DateTimePicker::make('awarded_at')->label('Prix remis à la junta le')->timezone('Europe/Lisbon'),Textarea::make('award_evidence')->label('Président/représentant, responsable légal et preuve de remise'),
]);}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('name')->label('Nom')->searchable()->wrap(),TextColumn::make('stand.freguesia')->label('Freguesia'),TextColumn::make('artwork_reference')->label('Référence dessin')->searchable(),TextColumn::make('status')->label('Jury')->badge(),TextColumn::make('awarded_at')->label('Remise du prix')->dateTime('d/m/Y'),TextColumn::make('updated_at')->label('Mise à jour')->dateTime('d/m/Y H:i')->sortable()])->defaultSort('updated_at','desc')->recordActions([EditAction::make()->modalWidth('7xl')]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\DrawingEntryResource\Pages\ManageRecords::route('/')];}
}
