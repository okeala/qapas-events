<?php
namespace App\Filament\Resources;
use App\Models\CupPlan;
use Filament\Resources\Resource;
use Filament\Schemas\{Schema,Components\Section};
use Filament\Forms\Components\{TextInput,Textarea,Select,Placeholder};
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
class CupPlanResource extends Resource {
 protected static ?string $model=CupPlan::class;protected static ?string $modelLabel='Plan de gobelets';protected static ?string $pluralModelLabel='Gobelets, stocks et soupe';protected static string|\UnitEnum|null $navigationGroup='4 · Préparer';protected static ?int $navigationSort=35;
 public static function canCreate(): bool {return false;}
 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([
  TextInput::make('name')->label('Plan de stock')->required()->maxLength(255),Placeholder::make('scenario')->label('Scénario')->content(fn(CupPlan $record)=>$record->scenario->name),
  Section::make('Prévision et réception · aucun encaissement automatique')->columns(2)->columnSpanFull()->schema([
   TextInput::make('quantity')->label('Stock prévu')->integer()->minValue(1)->maxValue(1000000)->required(),TextInput::make('capacity_ml')->label('Contenance · ml')->integer()->minValue(1)->maxValue(10000)->required(),TextInput::make('village_pool')->label('Lot équipes de freguesias')->integer()->minValue(0)->required(),TextInput::make('independent_pool')->label('Lot stands indépendants')->integer()->minValue(0)->required(),TextInput::make('received_quantity')->label('Effectivement reçu chez QAPAS')->integer()->minValue(0)->required(),Textarea::make('receipt_evidence')->label('Bon de livraison / contrôle réception'),
  ]),
  Section::make('Achat, revente et consigne distincts')->description('Le tarif de revente QAPAS au stand se renseigne dans la ligne cups-resale du scénario. Les stands gèrent leurs consignes visiteurs et la restitution. Cela ne leur donne aucun droit de bar payant.')->columnSpanFull()->columns(2)->schema([
   Placeholder::make('reference')->label('Référence transmise, pas un devis pour 1 000')->content(fn(CupPlan $record)=>$record->reference_quantity.' pièces · '.\App\Domain\Finance\Money::format($record->reference_net_cents).' HT / '.\App\Domain\Finance\Money::format($record->reference_gross_cents).' au taux du devis transmis. '.$record->reference_source)->columnSpanFull(),
   Placeholder::make('finance')->label('Prévision du stock')->content(fn(CupPlan $record)=>'Achat budgété : '.($record->report()['cost_cents']===null?'inconnu':\App\Domain\Finance\Money::format($record->report()['cost_cents'])).' · Contribution revente moins achat, avant lavage et logistique : '.($record->report()['stock_margin_cents']===null?'à chiffrer':\App\Domain\Finance\Money::format($record->report()['stock_margin_cents'])).($record->report()['quantity_aligned']?'':' · Revoir la quantité de la ligne cups-resale.'))->columnSpanFull(),
   TextInput::make('suggested_deposit_cents')->label('Consigne visiteur conseillée · centimes')->integer()->minValue(0)->maxValue(100000)->required()->helperText('2,50 € = 250 centimes. Remboursable ; ni chiffre d’affaires QAPAS, ni bénéfice présumé des souvenirs.'),Textarea::make('sales_and_returns_terms')->label('Accord avec les stands : prix, responsabilité, retours visiteurs, casse, invendus, clôture')->maxLength(15000)->columnSpanFull(),
  ]),
  Section::make('Usage et lavage')->columns(2)->columnSpanFull()->schema([
   Select::make('hot_food_status')->label('Soupes et boissons chaudes · référence exacte')->options(['unverified'=>'Non vérifié : ne pas utiliser à chaud','confirmed'=>'Usage à chaud documenté','not_suitable'=>'Inadapté au chaud'])->required(),Textarea::make('hot_food_evidence')->label('Fabricant, référence, déclaration, température, durée et lavages'),Textarea::make('washing_plan')->label('Collecte, circuit sale/propre, lavage, séchage, contrôle et personnel')->maxLength(10000)->columnSpanFull(),Textarea::make('sponsor_deliverables')->label('Partenaire principal : financement, BAT, marquage, quantités, délais et livrables')->maxLength(10000)->columnSpanFull(),
  ]),
 ]);}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('name')->label('Stock'),TextColumn::make('scenario.name')->label('Scénario'),TextColumn::make('quantity')->label('Prévu'),TextColumn::make('received_quantity')->label('Reçu'),TextColumn::make('available')->label('Chez QAPAS')->getStateUsing(fn($record)=>$record->report()['available']),TextColumn::make('hot_food_status')->label('Usage chaud')->badge()])->recordActions([Action::make('edit')->label('Stock, devis et allocations')->url(fn($record)=>self::getUrl('edit',['record'=>$record]))]);}
 public static function getRelations(): array {return [\App\Filament\RelationManagers\CupAllocationsRelationManager::class];}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\CupPlanResource\Pages\ListRecords::route('/'),'edit'=>\App\Filament\Resources\CupPlanResource\Pages\EditRecord::route('/{record}/edit')];}
}
