<?php
namespace App\Filament\Resources;
use App\Models\CommercialPlan;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Forms\Components\{Select,TextInput,Textarea};
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\{EditAction,Action};
class CommercialPlanResource extends Resource {
 protected static ?string $model=CommercialPlan::class;
 protected static ?string $modelLabel='Prix de couverture · 6 + 6';
 protected static ?string $pluralModelLabel='Prix de couverture · 6 + 6';
 protected static string|\UnitEnum|null $navigationGroup='2 · Chiffrer';
 protected static ?int $navigationSort=12;
 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([Select::make('scenario_id')->label('Scénario actif')->relationship('scenario','name')->required()->disabledOn('edit'),TextInput::make('name')->label('Plan')->required(),TextInput::make('independent_price_cents')->label('Prix TTC proposé indépendant (centimes)')->integer()->minValue(0)->required()->default(65000),TextInput::make('village_price_cents')->label('Prix COMMERCIAL TTC pour 100 % du parrainage d’un stand, centimes')->integer()->minValue(5)->step(5)->required()->default(100000)->helperText('Hypothèse 1 000 € TTC, donc 200 € par part de 20 %. Ce choix ne comble pas automatiquement le déficit ; le besoin de couverture reste séparé.'),TextInput::make('vat_basis_points')->label('IVA de simulation (2300 = 23 %)')->integer()->minValue(0)->maxValue(10000)->required()->default(2300),TextInput::make('unknown_allowance_cents')->label('Enveloppe de simulation pour inconnus (centimes)')->integer()->minValue(0)->required()->default(200000)->helperText('En plus des imprévus. À ventiler par devis ; ne prouve pas que les inconnus sont couverts.'),TextInput::make('rounding_cents')->label('Arrondi supérieur du prix parrain (5000 = 50 €)')->integer()->minValue(1)->required()->default(5000),Textarea::make('assumptions')->label('Hypothèses de vente et périmètre')->rows(6)->columnSpanFull()]);}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('name')->label('Plan')->wrap(),TextColumn::make('scenario.name')->label('Scénario'),TextColumn::make('applied_at')->label('Prix proposés reportés le')->dateTime('d/m/Y H:i')])->recordActions([Action::make('report')->label('Seuils et prix')->modalContent(fn(CommercialPlan $record)=>view('filament.merchandising.prices',['record'=>$record,'r'=>$record->report(),'economics'=>app(\App\Domain\Finance\StandEconomics::class)->report($record)]))->modalWidth('7xl')->modalSubmitAction(false)->modalCancelActionLabel('Fermer'),EditAction::make(),Action::make('apply')->label('Reporter les tarifs proposés')->requiresConfirmation()->modalDescription('Reporte les prix commerciaux choisis sur les 12 recettes non engagées ; ne transforme plus le besoin global calculé en prix des freguesias. Les offres publiques existantes sont conservées. Aucun contrat validé, engagement, paiement ou vente n’est créé. Les coûts inconnus continuent de bloquer la confirmation du lancement.')->action(fn(CommercialPlan $record)=>app(\App\Domain\Finance\CommercialPricing::class)->apply($record))]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\CommercialPlanResource\Pages\ManageRecords::route('/')];}
}
