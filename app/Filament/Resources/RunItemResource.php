<?php
namespace App\Filament\Resources;

use App\Models\RunItem;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Forms\Components\{TextInput, Textarea, Select, DateTimePicker};
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;

class RunItemResource extends Resource
{
    protected static ?string $model = RunItem::class;
    protected static ?string $modelLabel = 'Déroulé opérationnel';
    protected static ?string $pluralModelLabel = 'Déroulé opérationnel';
    protected static string|\UnitEnum|null $navigationGroup = '5 · Exploiter';
    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('event_project_id')->label('Édition')->relationship('eventProject', 'name')->searchable()->preload()->required()->disabledOn('edit'),
            TextInput::make('name')->label('Séquence')->helperText('Le déroulé opérationnel indique qui fait quoi, où et quand : accueil, épreuves, annonces, pauses et clôture.')->maxLength(255)->required(),
            TextInput::make('owner')->label('Responsable')->helperText('Une personne référente pour cette séquence. Cette saisie ne lui envoie aucun message.')->maxLength(255)->required(),
            TextInput::make('location')->label('Zone / point de rendez-vous')->maxLength(255),
            DateTimePicker::make('starts_at')->label('Début · heure du Portugal')->timezone('Europe/Lisbon')->required(),
            DateTimePicker::make('ends_at')->label('Fin · heure du Portugal')->timezone('Europe/Lisbon')->required()->after('starts_at'),
            Select::make('status')->label('État constaté par la coordination')->options(RunItem::STATUSES)->default('planned')->required(),
            Textarea::make('notes')->label('Consignes / retour d’expérience')->helperText('Préciser le résultat attendu, les moyens, le remplaçant et la condition de départ. Signaler les problèmes dans Incidents.')->maxLength(10000)->rows(4)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('starts_at')->label('Début · Portugal')->dateTime('d/m H:i')->timezone('Europe/Lisbon')->sortable(),
            TextColumn::make('ends_at')->label('Fin · Portugal')->dateTime('d/m H:i')->timezone('Europe/Lisbon'),
            TextColumn::make('name')->label('Séquence')->searchable()->wrap(),
            TextColumn::make('owner')->label('Responsable')->searchable()->wrap(),
            TextColumn::make('location')->label('Zone / rendez-vous')->searchable()->placeholder('À affecter')->wrap(),
            TextColumn::make('status')->label('État')->formatStateUsing(fn (string $state) => RunItem::STATUSES[$state] ?? $state)->badge(),
            TextColumn::make('eventProject.name')->label('Édition')->toggleable(),
        ])->filters([
            SelectFilter::make('event_project_id')->label('Édition')->relationship('eventProject', 'name')->searchable()->preload(),
            SelectFilter::make('status')->label('État')->options(RunItem::STATUSES),
        ])->defaultSort('starts_at', 'asc')->poll('15s')->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => \App\Filament\Resources\RunItemResource\Pages\ManageRecords::route('/')];
    }
}
