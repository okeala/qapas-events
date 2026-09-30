<?php
namespace App\Filament\Resources;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\{TextInput,Textarea,Select,Toggle,DateTimePicker,Placeholder};
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\{Action,EditAction};
class DistributorResource extends Resource {
 protected static ?string $model=\App\Models\Distributor::class;protected static ?string $modelLabel='Agent point-relais';protected static ?string $pluralModelLabel='Agents de distribution';protected static string|\UnitEnum|null $navigationGroup='3 · Mobiliser';protected static ?int $navigationSort=42;
 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([Select::make('stand_partner_id')->label('Point-relais du stand')->relationship('standPartner','name',fn(\Illuminate\Database\Eloquent\Builder $query)=>$query->whereNotNull('relay_slot'))->required()->disabledOn('edit'),TextInput::make('name')->label('Nom professionnel')->required()->maxLength(120),TextInput::make('email')->email()->required()->unique(ignoreRecord:true),TextInput::make('password')->label('Mot de passe · 16 caractères minimum')->password()->revealable()->minLength(16)->maxLength(200)->required(fn(string $operation)=>$operation==='create')->dehydrated(fn($state)=>filled($state))->afterStateHydrated(fn($component)=>$component->state(null)),Textarea::make('contract_evidence')->label('Mandat signé : 10 %, espèces, reversement, délais, annulation, données et fraude')->columnSpanFull()->required(),Toggle::make('is_active')->label('Habiliter le relais · aucun accès admin')]);}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('name')->searchable(),TextColumn::make('standPartner.stand.freguesia')->label('Freguesia'),TextColumn::make('email'),TextColumn::make('is_active')->label('Habilité')->formatStateUsing(fn($state)=>$state?'Oui':'Non')])->recordActions([EditAction::make(),Action::make('portal')->label('Portail relais')->url(fn()=>route('relay.login'))->openUrlInNewTab()]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\DistributorResource\Pages\ManageRecords::route('/')];}
}
