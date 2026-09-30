<?php
namespace App\Filament\Resources;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\{Select,TextInput,Textarea,Toggle,DateTimePicker,Placeholder};
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\{EditAction,Action};
class BarPartnershipResource extends Resource {
 protected static ?string $model=\App\Models\BarPartnership::class;
 protected static ?string $modelLabel='Bar QAPAS · partenariat cafés';
 protected static ?string $pluralModelLabel='Bar QAPAS · partenariat cafés';
 protected static string|\UnitEnum|null $navigationGroup='2 · Chiffrer';
 protected static ?int $navigationSort=75;
 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([Select::make('event_project_id')->label('Édition')->relationship('eventProject','name')->required()->live()->disabledOn('edit'),TextInput::make('name')->label('Nom')->required()->maxLength(255),
 Select::make('stand_partner_id')->label('Café / relais')->options(fn(Get $get)=>\App\Models\StandPartner::whereHas('stand',fn($q)=>$q->where('event_project_id',$get('event_project_id')))->pluck('name','id'))->required()->disabledOn('edit'),Select::make('status')->label('Variante à étudier')->options(['study'=>'Étude','agreed'=>'Convention conclue','active'=>'Créneau actif','closed'=>'Clôturé'])->default('study')->required(),DateTimePicker::make('starts_at')->label('Début du service')->timezone('Europe/Lisbon'),DateTimePicker::make('ends_at')->label('Fin du service')->timezone('Europe/Lisbon'),TextInput::make('commission_basis_points')->label('Taux convenu sur ventes nouvelles (1 000 = 10 %)')->integer()->minValue(0)->maxValue(10000),TextInput::make('forecast_ticket_sales_cents')->label('Prévision de ventes nouvelles de tickets, centimes')->default(0)->integer()->minValue(0)->required(),TextInput::make('forecast_other_costs_cents')->label('Autres coûts TTC du service, centimes')->integer()->minValue(0),Placeholder::make('comparison')->label('Comparaison de la variante')->content(fn(?\App\Models\BarPartnership $record)=>$record?'Commission prévue : '.($record->forecastCommission()===null?'à définir':\App\Domain\Finance\Money::format($record->forecastCommission())).' · autres frais : '.($record->forecast_other_costs_cents===null?'à chiffrer':\App\Domain\Finance\Money::format($record->forecast_other_costs_cents)).'. La commission normale du billet est déjà réservée : aucun ajout automatique au budget.':'Renseigner les hypothèses, sans engagement.'),
Select::make('budget_line_id')->label('Coût budgétaire existant · ne pas dupliquer')->options(fn(Get $get)=>\App\Models\BudgetLine::whereHas('scenario',fn($q)=>$q->where('event_project_id',$get('event_project_id')))->where('kind','cost')->pluck('name','id'))->searchable(),Textarea::make('terms')->label('Ventes directes, caisse, stock, commission unique, reversement, remboursements')->rows(5)->helperText('Commission sur vente nouvelle encaissée uniquement ; jamais sur utilisation du crédit de 10 €. QAPAS conserve la responsabilité du bar. Tout autre taux requiert une offre et un mandat versionnés.'),Textarea::make('operator_evidence')->label('Personnel habilité, hygiène, assurance, contrôle alcool et supervision QAPAS'),Textarea::make('agreement_evidence')->label('Accord des parties et horaires ; aucune vente activée par cette étude'),]);}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('name')->label('Nom')->searchable()->wrap(),TextColumn::make('updated_at')->label('Mise à jour')->dateTime('d/m/Y H:i')->sortable()])->defaultSort('updated_at','desc')->recordActions([EditAction::make()->modalWidth('7xl')]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\BarPartnershipResource\Pages\ManageRecords::route('/')];}
}
