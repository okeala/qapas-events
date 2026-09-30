<?php
namespace App\Filament\Resources;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Forms\Components\{TextInput,Textarea,Select,Toggle};
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
class CateringServiceResource extends Resource {
 protected static ?string $model=\App\Models\CateringService::class;
 protected static ?string $modelLabel='Soupe du chef';
 protected static ?string $pluralModelLabel='Soupe du chef Magalhães';
 protected static string|\UnitEnum|null $navigationGroup='4 · Préparer';
 protected static ?int $navigationSort=65;
 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([Select::make('event_project_id')->label('Édition')->relationship('eventProject','name')->required()->disabledOn('edit'),TextInput::make('name')->label('Service')->required(),TextInput::make('recipe')->label('Recette unique : soupe au chou')->required(),TextInput::make('chef')->label('Chef mis à l’honneur')->required(),Toggle::make('chef_confirmed')->label('Accord du chef documenté'),TextInput::make('production_place')->label('Lieu de préparation à valider'),TextInput::make('planned_portions')->label('Portions prévues')->integer()->minValue(0),TextInput::make('prepared_portions')->label('Portions préparées réelles')->integer()->minValue(0),TextInput::make('sold_portions')->label('Portions vendues réelles')->integer()->minValue(0),TextInput::make('price_gross_cents')->label('Prix TTC / portion (centimes)')->integer()->minValue(0),Textarea::make('allergens')->label('Ingrédients et allergènes à confirmer avec le chef')->columnSpanFull(),Textarea::make('service_plan')->label('Production, transport, maintien au chaud, service et équipe QAPAS')->columnSpanFull(),Textarea::make('evidence')->label('Accord, validation sanitaire et justificatifs')->columnSpanFull()]);}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('name')->label('Service'),TextColumn::make('chef')->label('Chef'),TextColumn::make('planned_portions')->label('Prévu'),TextColumn::make('prepared_portions')->label('Produit'),TextColumn::make('sold_portions')->label('Vendu')])->recordActions([EditAction::make()->modalWidth('7xl')]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\CateringServiceResource\Pages\ManageRecords::route('/')];}
}
