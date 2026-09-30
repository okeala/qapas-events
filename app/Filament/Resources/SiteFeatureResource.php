<?php
namespace App\Filament\Resources;
use App\Models\SiteFeature;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\{TextInput,Textarea,Select,Toggle,Repeater,DatePicker,DateTimePicker};
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
class SiteFeatureResource extends Resource {
 protected static ?string $model=SiteFeature::class;
 protected static ?string $modelLabel='Éléments du site et besoins';
 protected static ?string $pluralModelLabel='Éléments du site et besoins';
 protected static string|\UnitEnum|null $navigationGroup='1 · Concevoir';
 protected static ?int $navigationSort=26;
 public static function canCreate(): bool {return false;}
 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([
 TextInput::make('name')->label('Élément')->required()->maxLength(120),Select::make('category')->label('Calque')->options(SiteFeature::CATEGORIES)->required(),Select::make('access')->label('Accès')->options(['public'=>'Public','qualified'=>'Opérateurs qualifiés','restricted'=>'Réservé'])->required(),TextInput::make('capacity')->label('Capacité étudiée')->integer()->minValue(0)->maxValue(100000),TextInput::make('owner')->label('Responsable')->maxLength(255),Toggle::make('is_public')->label('Visible sur le plan public'),Textarea::make('notes')->label('Accès, dimensions, raccordements et contraintes · interne')->maxLength(10000)->columnSpanFull(),Toggle::make('needs_complete')->label('Besoins complets confirmés'),Repeater::make('needs')->relationship()->label('Matériel, personnel, eau, énergie, prestations')->schema([
 TextInput::make('name')->label('Besoin')->required()->maxLength(255),TextInput::make('quantity')->label('Quantité (vide = inconnue)')->integer()->minValue(1)->maxValue(100000),TextInput::make('unit')->label('Unité')->required()->default('unité'),Select::make('basis')->label('Base')->options(['fixed'=>'Pour toute l’édition','per_day'=>'Par jour'])->required()->default('fixed'),TextInput::make('unit_gross_cents')->label('Prix TTC en centimes')->integer()->minValue(0)->maxValue(100000000),TextInput::make('vat_basis_points')->label('IVA : 2300 = 23 %')->integer()->minValue(0)->maxValue(10000),Toggle::make('deductible')->label('IVA déductible confirmé'),...\App\Domain\Finance\Pricing::fields(),Textarea::make('evidence')->label('Devis / preuve de mise à disposition')->maxLength(5000)
 ])->columns(2)->defaultItems(0)->deletable(false)->columnSpanFull()
 ]);}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('name')->label('Élément')->searchable(),TextColumn::make('category')->label('Calque'),TextColumn::make('eventProject.name')->label('Édition')])->recordActions([\Filament\Actions\Action::make('quotes')->label('Préparer les courriers')->action(function($record){abort_unless(auth('admin')->user()?->is_active,403);foreach($record->needs as $need)app(\App\Domain\Procurement\Consultations::class)->ensure($need);\Filament\Notifications\Notification::make()->title('Brouillons disponibles dans Courriers et devis fournisseurs')->success()->send();}),EditAction::make()->modalWidth('7xl')]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\SiteFeatureResource\Pages\ManageRecords::route('/')];}
}
