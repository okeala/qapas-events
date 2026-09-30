<?php
namespace App\Filament\Resources;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Forms\Components\{TextInput,Textarea,Select,Toggle,DateTimePicker,DatePicker};
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
class EventProjectResource extends Resource {
 protected static ?string $model=\App\Models\EventProject::class;
 protected static ?string $modelLabel='Éditions';
 protected static ?string $pluralModelLabel='Éditions';
 protected static string|\UnitEnum|null $navigationGroup='1 · Concevoir';

 protected static ?int $navigationSort=10;

 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([TextInput::make('name')->label('Nom')->maxLength(255)->required(),TextInput::make('slug')->label('Adresse publique')->maxLength(255)->required()->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')->unique(ignoreRecord:true),Toggle::make('plan_is_public')->label('Publier le fond de plan et les terrasses rendues visibles')->default(false),Toggle::make('is_public')->label('Présentation visible · aucun paiement')->default(false),Textarea::make('product')->label('Produit · expérience et publics')->maxLength(10000)->rows(3)->columnSpanFull(),Textarea::make('price')->label('Prix · qui paie quoi et pourquoi')->maxLength(10000)->rows(3)->columnSpanFull(),Textarea::make('place')->label('Distribution · accès, juntas, relais, stands')->maxLength(10000)->rows(3)->columnSpanFull(),Textarea::make('promotion')->label('Promotion · mobilisation et canaux')->maxLength(10000)->rows(3)->columnSpanFull(),TextInput::make('venue')->label('Lieu')->maxLength(255),DateTimePicker::make('starts_at')->label('Début')->timezone('Europe/Lisbon'),DateTimePicker::make('ends_at')->label('Fin')->timezone('Europe/Lisbon')->after('starts_at'),TextInput::make('capacity')->label('Capacité validée')->integer()->minValue(0)->maxValue(1000000000)->required()->default(0)]);}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('name')->label('Nom')->searchable()->wrap(),TextColumn::make('phase')->label('Phase')->badge(),TextColumn::make('updated_at')->label('Mise à jour')->dateTime('d/m/Y H:i')->sortable()])->defaultSort('updated_at','desc')->recordActions([EditAction::make(), \Filament\Actions\Action::make('advance')->label('Passer au palier suivant')->requiresConfirmation()->action(function($record){app(\App\Domain\Planning\PhaseTransition::class)->advance($record);})]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\EventProjectResource\Pages\ManageRecords::route('/')];}
}
