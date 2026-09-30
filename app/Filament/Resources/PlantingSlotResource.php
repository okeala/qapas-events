<?php
namespace App\Filament\Resources;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Forms\Components\{TextInput,Textarea,Select,DateTimePicker};
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
class PlantingSlotResource extends Resource {
 protected static ?string $model=\App\Models\PlantingSlot::class;protected static ?string $modelLabel='Arbre / participant';protected static ?string $pluralModelLabel='Arbres et remerciements';protected static string|\UnitEnum|null $navigationGroup='5 · Exploiter';protected static ?int $navigationSort=45;
 public static function canCreate(): bool {return false;}
 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([TextInput::make('tree_code')->label('Identité de l’arbre')->disabled(),TextInput::make('participant_name')->label('Participant privé')->disabled(),TextInput::make('species')->label('Espèce / variété'),Select::make('status')->options(['available'=>'Disponible','reserved'=>'Réservé','planted'=>'Planté'])->required(),DateTimePicker::make('planted_at')->label('Planté le')->timezone('Europe/Lisbon'),TextInput::make('latitude')->numeric()->label('Latitude réelle'),TextInput::make('longitude')->numeric()->label('Longitude réelle'),Textarea::make('evidence')->label('Preuve, entretien et suivi'),TextInput::make('public_name')->label('Nom public volontaire'),DateTimePicker::make('thanks_consented_at')->label('Consentement recueilli le')->timezone('Europe/Lisbon')]);}
 public static function table(Table $table): Table {return $table->groups([\Filament\Tables\Grouping\Group::make('session.name')->label('Clôture de journée')])->defaultGroup('session.name')->columns([TextColumn::make('tree_code')->searchable(),TextColumn::make('participant_name')->label('Participant')->searchable(),TextColumn::make('species')->label('Variété'),TextColumn::make('status')->badge(),TextColumn::make('public_name')->label('Remerciement autorisé')])->filters([\Filament\Tables\Filters\SelectFilter::make('planting_session_id')->label('Session')->relationship('session','name')])->recordActions([\Filament\Actions\EditAction::make()]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\PlantingSlotResource\Pages\ManageRecords::route('/')];}
}
