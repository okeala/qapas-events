<?php
namespace App\Filament\Resources;
use App\Models\FurniturePlan;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Forms\Components\{TextInput,Textarea,Select,Toggle,Repeater};
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\{EditAction,Action};
class FurniturePlanResource extends Resource {
 protected static ?string $model=FurniturePlan::class;
 protected static ?string $modelLabel='Chiffrage mobilier';
 protected static ?string $pluralModelLabel='Fabrication et location du mobilier';
 protected static string|\UnitEnum|null $navigationGroup='2 · Chiffrer';
 protected static ?int $navigationSort=35;
 public static function form(Schema $schema): Schema {
  $money=[];foreach(['wood_price_cents_m3'=>'Bois livré scierie TTC / m³','hardware_cents_unit'=>'Quincaillerie TTC / ensemble','roller_cents_batch'=>'Rouleau et pinceaux TTC / lot','other_cents_batch'=>'Autres achats TTC / lot','hourly_cents'=>'Coût horaire du travail','service_cents_unit'=>'Service complet par location TTC','rental_gross_cents'=>'Location envisagée TTC'] as $key=>$label)$money[]=TextInput::make($key)->label($label.' (centimes)')->integer()->minValue(0)->maxValue(100000000)->helperText('Inconnu = champ vide ; zéro doit être justifié.');
  return $schema->columns(2)->components([Select::make('event_project_id')->label('Édition')->relationship('eventProject','name')->required()->disabledOn('edit'),TextInput::make('name')->label('Nom')->required(),TextInput::make('quantity')->label('Ensembles fabriqués dans ce lot')->integer()->minValue(1)->maxValue(1000)->required()->default(12),TextInput::make('waste_basis_points')->label('Pertes bois en points de base (1500 = 15 %)')->integer()->minValue(0)->maxValue(10000)->default(1500)->required(),Repeater::make('parts')->label('Débit indicatif : dimensions avant usinage, à valider')->schema([TextInput::make('name')->label('Pièce')->required(),TextInput::make('quantity')->label('Quantité par ensemble')->integer()->minValue(1)->maxValue(100)->required(),TextInput::make('length_mm')->label('Longueur mm')->integer()->minValue(1)->maxValue(10000)->required(),TextInput::make('width_mm')->label('Largeur mm')->integer()->minValue(1)->maxValue(1000)->required(),TextInput::make('thickness_mm')->label('Épaisseur mm')->integer()->minValue(1)->maxValue(500)->required()])->default(FurniturePlan::illustrativeParts())->columns(3)->minItems(1)->maxItems(50)->columnSpanFull(),...$money,TextInput::make('can_price_cents')->label('Bidon protection TTC (centimes)')->integer()->default(4000)->required(),TextInput::make('can_litres')->label('Litres par bidon')->numeric()->default(5)->required(),TextInput::make('coverage_m2_litre')->label('Rendement m² / litre / couche, fiche produit')->numeric()->minValue(0.01),TextInput::make('coats')->label('Nombre de couches, fiche produit')->integer()->minValue(1)->maxValue(10),TextInput::make('work_minutes_unit')->label('Temps complet par ensemble (minutes)')->integer()->minValue(0),TextInput::make('vat_basis_points')->label('IVA sur la location (2300 = 23 %)')->integer()->minValue(0)->maxValue(10000),Textarea::make('evidence')->label('Devis, produit adapté au mobilier, débit, temps et transport vérifiés')->columnSpanFull(),Toggle::make('inputs_verified')->label('Valider après enregistrement du chiffrage')->helperText('Toute modification de coûts invalide la validation précédente.')]);
 }
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('name')->label('Lot')->wrap(),TextColumn::make('quantity')->label('Ensembles six places'),TextColumn::make('cost')->label('Coût complet / ensemble')->state(fn($record)=>$record->report()['full_unit_cents']===null?'À chiffrer':\App\Domain\Finance\Money::format($record->report()['full_unit_cents']))])->recordActions([Action::make('calculate')->label('Calcul et comparaison')->modalHeading('Prix de revient et location')->modalContent(fn($record)=>view('filament.furniture.report',compact('record')))->modalSubmitAction(false)->modalCancelActionLabel('Fermer'),EditAction::make()->modalWidth('7xl')]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\FurniturePlanResource\Pages\ManageRecords::route('/')];}
}
