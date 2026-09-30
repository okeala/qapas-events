<?php
namespace App\Filament\Resources;
use App\Models\RegistrationCampaign;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\{Select,TextInput,Textarea,Toggle,DateTimePicker,Placeholder};
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\{EditAction,Action};
class RegistrationCampaignResource extends Resource {
 protected static ?string $model=RegistrationCampaign::class;
 protected static ?string $modelLabel='Campagne de candidatures';
 protected static ?string $pluralModelLabel='Candidats · vote et contreparties';
 protected static string|\UnitEnum|null $navigationGroup='3 · Mobiliser';
 protected static ?int $navigationSort=45;
 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([
  Select::make('event_project_id')->label('Édition')->relationship('eventProject','name')->required()->disabledOn('edit'),TextInput::make('name')->required()->maxLength(255),
  Section::make('Candidature au vote · billet commun et droits historiques')->description('Une seule inscription par personne et édition, quel que soit le nombre de rôles. Le paiement ne vaut pas sélection. Avant ouverture : finaliser le scrutin, les conditions des tickets et les conditions d’annulation.')->columnSpanFull()->columns(2)->schema([
   Toggle::make('unified_ticket_terms')->label('Ces conditions correspondent au ticket commun, sans droits différents selon le vote')->helperText('Les conditions historiques restent figées sur chaque ancienne candidature ; relire et versionner avant de cocher.'),TextInput::make('terms_version')->label('Version du contrat')->required()->maxLength(100),DateTimePicker::make('closes_at')->label('Clôture des candidatures'),
   Textarea::make('terms_fr')->label('Conditions FR')->rows(5),Textarea::make('terms_pt')->label('Conditions PT')->rows(5),Textarea::make('refund_policy_fr')->label('Annulation / report / remboursement FR')->rows(4),Textarea::make('refund_policy_pt')->label('Annulation / report / remboursement PT')->rows(4),
   TextInput::make('vat_basis_points')->label('IVA retenue (2300 = 23 %)')->integer()->minValue(0)->maxValue(10000)->helperText('Ne pas présumer le taux : qualification de la candidature et du bon à faire valider.'),TextInput::make('stripe_tax_rate_id')->label('Identifiant du taux inclusif Stripe')->maxLength(255),
   Textarea::make('billing_procedure')->label('Facturation conforme, traitement des bons et conservation des pièces'),Textarea::make('validation_evidence')->label('Validation juridique / fiscale et conditions d’éligibilité'),
   Placeholder::make('blockers')->label('Prérequis manquants')->content(fn(?RegistrationCampaign $record)=>$record?implode(' · ',$record->blockers()):'Enregistrer la campagne, puis examiner les prérequis.'),Toggle::make('is_open')->label('Ouvrir après validation')->helperText('Une modification contractuelle ferme la campagne. Enregistrer, relire puis ouvrir séparément.'),
  ]),
  Section::make('Affectation interne des préventes candidates')->description('Suivi d’affectation interne ; ne verse pas d’argent au fournisseur. La confirmation Stripe ne garantit pas encore la disponibilité sur le compte bancaire. Les tickets déjà payés ne sont pas une seconde recette du bar.')->columnSpanFull()->columns(2)->schema([
   TextInput::make('drinks_supplier')->label('Fournisseur')->maxLength(255),Textarea::make('drinks_contract_reference')->label('Contrat : référence, acompte, livraisons, reprises des fûts et mobilier'),
   TextInput::make('drinks_contract_cents')->label('Montant à financer (centimes TTC)')->integer()->minValue(0),TextInput::make('bank_available_cents')->label('Fonds inscriptions reçus en banque (centimes, après frais)')->integer()->minValue(0),
   Textarea::make('bank_evidence')->label('Référence du rapprochement bancaire des inscriptions'),TextInput::make('refund_reserve_cents')->label('Réserve de remboursements (centimes ; zéro explicite)')->integer()->minValue(0),
   TextInput::make('drinks_allocated_cents')->label('Affecté au contrat boissons (centimes)')->integer()->minValue(0)->default(0),Textarea::make('funding_evidence')->label('Décision d’affectation, coût des boissons promises et échéances'),
  ]),
  Section::make('Clôture du vote local')->columnSpanFull()->columns(2)->schema([DateTimePicker::make('vote_closed_at')->label('Résultats arrêtés le')->helperText('Après la clôture des candidatures, le dépouillement et le traitement des contestations.'),Textarea::make('vote_minutes_reference')->label('Procès-verbaux et preuve de clôture des scrutins concernés')]),
 ]);}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('name')->searchable(),TextColumn::make('eventProject.name')->label('Édition'),TextColumn::make('is_open')->label('Ouverte')->formatStateUsing(fn($state)=>$state?'Oui':'Non'),TextColumn::make('closes_at')->label('Clôture')->dateTime('d/m/Y H:i')])->recordActions([EditAction::make(),Action::make('funding')->label('Trésorerie / tickets')->modalHeading('Fonds historiques des candidatures et engagements boissons')->modalContent(fn($record)=>view('filament.registration-funding',['campaign'=>$record,'funding'=>$record->funding()]))->modalSubmitAction(false)]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\RegistrationCampaignResource\Pages\ManageRecords::route('/')];}
}
