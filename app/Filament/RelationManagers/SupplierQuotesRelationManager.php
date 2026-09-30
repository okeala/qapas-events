<?php
namespace App\Filament\RelationManagers;
use Filament\Schemas\Schema;
use Filament\Forms\Components\{TextInput,Textarea,Select,DatePicker};
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\{CreateAction,EditAction,Action};
use App\Domain\Finance\Money;
class SupplierQuotesRelationManager extends \Filament\Resources\RelationManagers\RelationManager {
 protected static string $relationship='quotes';
 protected static ?string $title='Offres reçues · alternatives sur le même poste';
 public function form(Schema $schema): Schema {return $schema->columns(2)->components([
  TextInput::make('supplier')->label('Fournisseur / prêteur')->required()->maxLength(255),TextInput::make('email')->label('Email professionnel')->email()->maxLength(254),Select::make('source_type')->label('Piste')->options(['supplier'=>'Fournisseur','local_loan'=>'Prêt du village','sponsor'=>'Sponsor / apport','word_of_mouth'=>'Bouche-à-oreille'])->default('supplier')->required(),TextInput::make('reference')->label('Référence offre / version')->maxLength(255),DatePicker::make('received_at')->label('Reçu le')->maxDate(today()),DatePicker::make('valid_until')->label('Valide jusqu’au'),
  TextInput::make('quantity')->label('Quantité proposée')->integer()->minValue(1)->maxValue(1000000)->default(fn()=>app(\App\Domain\Procurement\Consultations::class)->quantity($this->getOwnerRecord()->costable)),TextInput::make('unit')->label('Unité')->maxLength(80)->default(fn()=>$this->getOwnerRecord()->costable->unit),
  TextInput::make('unit_gross_cents')->label('Prix unitaire TTC · centimes')->integer()->minValue(0)->maxValue(1000000000),TextInput::make('vat_basis_points')->label('IVA · 2300 = 23 %')->integer()->minValue(0)->maxValue(10000),TextInput::make('delivery_cents')->label('Transport / reprise TTC · centimes')->integer()->minValue(0)->helperText('Zéro explicite si inclus ; vide = inconnu.'),TextInput::make('other_cents')->label('Autres frais TTC · centimes')->integer()->minValue(0),TextInput::make('deposit_cents')->label('Caution remboursable distincte · centimes')->integer()->minValue(0)->helperText('À financer séparément ; jamais une charge définitive.'),Textarea::make('document_reference')->label('Référence du devis / preuve écrite')->maxLength(5000),Textarea::make('conditions')->label('Périmètre, dates de répétition / événement, gratuité, assurance, exclusions, contreparties')->maxLength(10000)->columnSpanFull(),
 ]);}
 public function table(Table $table): Table {return $table->columns([
  TextColumn::make('supplier')->label('Proposant')->searchable(),TextColumn::make('reference')->label('Offre'),TextColumn::make('quantity')->label('Qté'),TextColumn::make('unit')->label('Unité'),TextColumn::make('total')->label('Total TTC hors caution')->getStateUsing(fn($record)=>$record->total()===null?'Incomplet':Money::format($record->total())),TextColumn::make('deposit_cents')->label('Caution distincte')->formatStateUsing(fn($state)=>Money::format((int)$state))->placeholder('Inconnue'),TextColumn::make('vat_basis_points')->label('IVA')->formatStateUsing(fn($state)=>($state/100).' %')->placeholder('Inconnue'),TextColumn::make('valid_until')->label('Échéance')->date('d/m/Y'),TextColumn::make('applied_at')->label('Repris au budget')->dateTime('d/m/Y H:i')->placeholder('Alternative'),
 ])->headerActions([CreateAction::make()->label('Saisir une offre reçue')])->recordActions([EditAction::make()->visible(fn($record)=>!$record->applied_at),Action::make('apply')->label('Reprendre comme devis à valider')->visible(fn($record)=>!$record->applied_at)->requiresConfirmation()->modalDescription('Reprend le prix tout compris pour la même quantité et unité, sans commande, engagement ni paiement. Les frais et cautions distincts exigent une décomposition manuelle préalable.')->action(fn($record)=>app(\App\Domain\Procurement\Consultations::class)->apply($record))]);}
}
