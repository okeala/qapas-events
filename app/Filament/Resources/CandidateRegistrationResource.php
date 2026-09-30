<?php
namespace App\Filament\Resources;
use App\Models\CandidateRegistration;
use App\Domain\Registration\{RegistrationDecision,RegistrationPayments};
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\{Select,Textarea};
use Filament\Actions\Action;
class CandidateRegistrationResource extends Resource {
 protected static ?string $model=CandidateRegistration::class;
 protected static ?string $modelLabel='Candidature payante';
 protected static ?string $pluralModelLabel='Candidatures payantes';
 protected static string|\UnitEnum|null $navigationGroup='4 · Préparer';
 protected static ?int $navigationSort=35;
 public static function canCreate(): bool {return false;}
 public static function table(Table $table): Table {return $table->columns([
  TextColumn::make('interest.name')->label('Candidat')->searchable(),TextColumn::make('interest.freguesia')->label('Freguesia')->searchable(),TextColumn::make('campaign.eventProject.name')->label('Édition'),TextColumn::make('interest.expert_roles')->label('Rôles')->formatStateUsing(fn($state)=>__('events.roles.'.$state))->badge(),
  TextColumn::make('payment_status')->label('Paiement')->badge(),TextColumn::make('paid_at')->label('Payé le')->dateTime('d/m/Y H:i'),TextColumn::make('decision')->label('Vote')->badge(),TextColumn::make('invoice_reference')->label('Référence Stripe (facturation fiscale à vérifier)')->toggleable(isToggledHiddenByDefault:true),
 ])->filters([SelectFilter::make('payment_status')->label('Paiement')->options(['pending'=>'En attente','paid'=>'Confirmé','review'=>'À examiner','refunded'=>'Remboursé']),SelectFilter::make('decision')->label('Vote')->options(['pending'=>'En attente','selected'=>'Retenu','not_selected'=>'Non retenu'])])->recordActions([\Filament\Actions\Action::make('ticket')->label('Billet QR')->visible(fn($record)=>$record->ticket!==null)->url(fn($record)=>$record->ticket?->verifyUrl()),
  Action::make('details')->label('Contrat accepté')->modalContent(fn($record)=>view('filament.registration-contract',['record'=>$record]))->modalSubmitAction(false),
  Action::make('refresh')->label('Vérifier Stripe / frais')->visible(fn($record)=>filled($record->stripe_session_id))->action(fn($record)=>app(RegistrationPayments::class)->refresh($record)),
  Action::make('decision')->label('Consigner le vote')->visible(fn($record)=>$record->isPaid()&&$record->decision==='pending')->schema([Select::make('decision')->label('Résultat')->options(['selected'=>'Retenu','not_selected'=>'Non retenu : crédit boissons de 10 €'])->required(),Textarea::make('evidence')->label('Procès-verbal et correspondance avec le candidat')->required()->minLength(10)->maxLength(5000)])->action(fn($record,array $data)=>app(RegistrationDecision::class)->record($record,$data['decision'],$data['evidence'])),
  Action::make('refund')->label('Rembourser 10 €')->color('danger')->visible(fn($record)=>$record->isPaid())->requiresConfirmation()->modalDescription('Demande un remboursement réel auprès de Stripe. Respecter les conditions acceptées et vérifier le motif avec le candidat. Les tickets sont suspendus immédiatement ; la confirmation est reçue par webhook.')->action(fn($record)=>app(RegistrationPayments::class)->requestRefund($record)),
 ]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\CandidateRegistrationResource\Pages\ManageRecords::route('/')];}
}
