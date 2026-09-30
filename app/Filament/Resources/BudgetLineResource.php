<?php
namespace App\Filament\Resources;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Forms\Components\{TextInput,Textarea,Select,Toggle,DateTimePicker,DatePicker};
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
class BudgetLineResource extends Resource {
 protected static ?string $model=\App\Models\BudgetLine::class;
 protected static ?string $modelLabel='Lignes budgétaires';
 protected static ?string $pluralModelLabel='Lignes budgétaires';
 protected static string|\UnitEnum|null $navigationGroup='2 · Chiffrer';

 protected static ?int $navigationSort=20;

 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([Select::make('scenario_id')->label('Scénario')->relationship('scenario','name')->preload()->searchable()->required()->disabledOn('edit'),TextInput::make('name')->label('Libellé')->maxLength(255)->required(),Select::make('kind')->label('Nature')->options(['revenue'=>'Recette propre QAPAS','cost'=>'Coût QAPAS','deposit'=>'Caution remboursable','earmarked'=>'Soutien affecté à une équipe','third_party'=>'Flux tiers (hors chiffre affaires)'])->required(),TextInput::make('unit_gross_cents')->label('Montant unitaire TTC (centimes)')->integer()->minValue(0)->maxValue(1000000000)->required(),TextInput::make('vat_basis_points')->label('IVA (2300 = 23 %, 0 = exonération confirmée)')->integer()->minValue(0)->maxValue(1000000000)->maxValue(10000)->helperText('Vide = à confirmer ; jamais assimilé automatiquement à zéro.'),Toggle::make('deductible')->label('IVA du coût déductible, confirmé comptablement'),TextInput::make('forecast_quantity')->label('Quantité prévue')->integer()->minValue(0)->maxValue(1000000000)->required()->default(0)->maxValue(1000000),TextInput::make('committed_quantity')->label('Quantité engagée')->integer()->minValue(0)->maxValue(1000000000)->required()->default(0)->lte('forecast_quantity'),TextInput::make('paid_quantity')->label('Quantité encaissée / réglée')->integer()->minValue(0)->maxValue(1000000000)->required()->default(0)->lte('committed_quantity'),Textarea::make('evidence')->label('Référence devis, engagement, paiement ou justificatif')->maxLength(10000)->rows(3)->columnSpanFull()]);}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('name')->label('Nom')->searchable()->wrap(),TextColumn::make('scenario.name')->label('Scénario'),TextColumn::make('kind')->label('Nature'),TextColumn::make('updated_at')->label('Mise à jour')->dateTime('d/m/Y H:i')->sortable()])->defaultSort('updated_at','desc')->recordActions([EditAction::make()]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\BudgetLineResource\Pages\ManageRecords::route('/')];}
}
