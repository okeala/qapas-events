<?php
namespace App\Filament\Resources;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Forms\Components\{TextInput,Textarea,Select,Toggle,DateTimePicker,DatePicker};
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
class OfferResource extends Resource {
 protected static ?string $model=\App\Models\Offer::class;
 protected static ?string $modelLabel='Offres envisagées';
 protected static ?string $pluralModelLabel='Offres envisagées';
 protected static string|\UnitEnum|null $navigationGroup='3 · Mobiliser';

 protected static ?int $navigationSort=10;

 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([Select::make('event_project_id')->label('Édition')->relationship('eventProject','name')->searchable()->preload()->required()->disabledOn('edit'),TextInput::make('name')->label('Offre')->maxLength(255)->required(),Select::make('kind')->label('Famille')->options(['stand'=>'Stand indépendant','relay'=>'Point-relais','sponsor'=>'Visibilité et sponsoring','village'=>'Soutien à une freguesia'])->required(),TextInput::make('price_gross_cents')->label('Prix TOTAL TTC (centimes, vide = devis)')->integer()->minValue(0)->maxValue(1000000000),TextInput::make('capacity')->label('Nombre maximum')->integer()->minValue(0)->maxValue(1000000000)->required()->default(0),Textarea::make('includes')->label('Ce qui est compris')->maxLength(10000)->rows(3)->columnSpanFull()->required(),Textarea::make('excludes')->label('Suppléments et exclusions')->maxLength(10000)->rows(3)->columnSpanFull(),Textarea::make('delivery')->label('Livraison et conditions à préciser')->maxLength(10000)->rows(3)->columnSpanFull(),Toggle::make('is_public')->label('Afficher comme proposition, sans vente')->default(false)]);}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('name')->label('Nom')->searchable()->wrap(),TextColumn::make('eventProject.name')->label('Édition'),TextColumn::make('updated_at')->label('Mise à jour')->dateTime('d/m/Y H:i')->sortable()])->defaultSort('updated_at','desc')->recordActions([EditAction::make()]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\OfferResource\Pages\ManageRecords::route('/')];}
}
