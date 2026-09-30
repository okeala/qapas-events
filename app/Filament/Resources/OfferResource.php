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

 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([Select::make('event_project_id')->label('Édition')->relationship('eventProject','name')->searchable()->preload()->required()->disabledOn('edit'),Select::make('prospect_type')->label('Type de prospect')->options(\App\Models\Offer::PROSPECT_TYPES)->required(),TextInput::make('name')->label('Offre')->maxLength(255)->required(),Select::make('kind')->label('Famille')->options(['stand'=>'Stand indépendant','relay'=>'Point-relais','sponsor'=>'Visibilité et sponsoring','village'=>'Parrain principal de freguesia','support'=>'Soutien / prévente'])->required(),TextInput::make('price_gross_cents')->label('Prix TOTAL TTC (centimes, vide = devis)')->integer()->minValue(0)->maxValue(1000000000),Toggle::make('is_founder')->label('Offre fondateur · contingent limité'),TextInput::make('regular_price_gross_cents')->label('Prix suivant annoncé TTC (centimes)')->integer()->minValue(0)->gte('price_gross_cents'),DatePicker::make('founder_deadline')->label('Fin du tarif fondateur'),TextInput::make('capacity')->label('Nombre maximum')->integer()->minValue(0)->maxValue(1000000000)->required()->default(0),Textarea::make('includes')->label('Ce qui est compris')->maxLength(10000)->rows(3)->columnSpanFull()->required(),Textarea::make('excludes')->label('Suppléments et exclusions')->maxLength(10000)->rows(3)->columnSpanFull(),Textarea::make('delivery')->label('Livraison et conditions à préciser')->maxLength(10000)->rows(3)->columnSpanFull(),Textarea::make('summary')->label('Promesse de l’offre'),\Filament\Forms\Components\Repeater::make('benefits')->label('Avantages et preuves')->schema([TextInput::make('label')->label('Avantage / spécification')->required(),TextInput::make('url')->label('Lien HTTPS ou chemin local vers le détail')])->columns(2)->defaultItems(0)->columnSpanFull(),Textarea::make('regular_price_evidence')->label('Justification du prix de référence barré')->helperText('Sans prix de référence réel documenté, contingent et échéance, aucune remise n’est affichée.'),Select::make('image_key')->label('Illustration')->options(['festival'=>'Ambiance des jeux','village'=>'Vie du village','market'=>'Produits et rencontres']),Toggle::make('preview_current')->label('Inclure dans la prévisualisation locale du catalogue actuel'),Toggle::make('is_public')->label('Afficher comme proposition, sans vente')->default(false)]);}
 public static function table(Table $table): Table {return $table->groups([\Filament\Tables\Grouping\Group::make('prospect_type')->label('Destinataire')->getTitleFromRecordUsing(fn($record)=>\App\Models\Offer::PROSPECT_TYPES[$record->prospect_type]??'À classer')->collapsible()])->defaultGroup('prospect_type')->filters([\Filament\Tables\Filters\SelectFilter::make('prospect_type')->label('Type de prospect')->options(\App\Models\Offer::PROSPECT_TYPES)])->columns([TextColumn::make('name')->label('Nom')->searchable()->wrap(),TextColumn::make('eventProject.name')->label('Édition'),TextColumn::make('updated_at')->label('Mise à jour')->dateTime('d/m/Y H:i')->sortable()])->defaultSort('updated_at','desc')->recordActions([EditAction::make()]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\OfferResource\Pages\ManageRecords::route('/')];}
}
