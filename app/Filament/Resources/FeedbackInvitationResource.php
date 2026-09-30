<?php
namespace App\Filament\Resources;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\{Select,TextInput,Textarea,Toggle,DateTimePicker,Placeholder};
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\{EditAction,Action};
class FeedbackInvitationResource extends Resource {
 protected static ?string $model=\App\Models\FeedbackInvitation::class;
 protected static ?string $modelLabel='Invitations et retours reçus';
 protected static ?string $pluralModelLabel='Invitations et retours reçus';
 protected static string|\UnitEnum|null $navigationGroup='6 · Clôturer';
 protected static ?int $navigationSort=31;
 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([
 Select::make('feedback_campaign_id')->label('Campagne')->relationship('campaign','name')->required()->disabledOn('edit'),TextInput::make('name')->label('Destinataire')->required()->maxLength(255),Select::make('audience')->label('Public')->options(\App\Models\FeedbackInvitation::AUDIENCES)->required(),TextInput::make('email')->label('Email privé')->email()->disabledOn('edit'),TextInput::make('source_key')->label('Référence unique de contact')->default(fn()=>'manual-'.\Illuminate\Support\Str::uuid())->required()->disabledOn('edit'),Select::make('locale')->label('Langue')->options(['pt'=>'Português','fr'=>'Français'])->default('pt')->required(),Textarea::make('contact_basis')->label('Lien avec l’événement et base de contact, sans abonnement marketing')->required(),Select::make('status')->label('État de l’invitation')->options(['draft'=>'À revoir','ready'=>'Prête pour envoi autorisé','sending'=>'En cours','accepted'=>'Acceptée par le transport','unknown'=>'Résultat incertain','cancelled'=>'Annulée'])->default('draft')->required()->disabled(fn($record)=>$record&&in_array($record->status,['sending','accepted','unknown'])),Placeholder::make('response')->label('Avis reçu')->content(fn($record)=>$record&&$record->responded_at?'Note '.$record->rating.'/5 · '.$record->positive.' · À améliorer : '.$record->improvements:'Pas encore de réponse'),
]);}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('name')->label('Nom')->searchable()->wrap(),TextColumn::make('audience')->label('Public')->formatStateUsing(fn($state)=>\App\Models\FeedbackInvitation::AUDIENCES[$state]??$state),TextColumn::make('status')->label('Envoi')->badge(),TextColumn::make('rating')->label('Note / 5')->sortable(),TextColumn::make('responded_at')->label('Réponse reçue')->dateTime('d/m/Y H:i')->sortable()])->defaultSort('updated_at','desc')->filters([\Filament\Tables\Filters\SelectFilter::make('audience')->label('Public')->options(\App\Models\FeedbackInvitation::AUDIENCES),\Filament\Tables\Filters\TernaryFilter::make('responded_at')->label('Réponse reçue')->nullable()])->recordActions([EditAction::make()->modalWidth('7xl'),Action::make('link')->label('Lien privé à remettre')->url(fn($record)=>$record->url())]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\FeedbackInvitationResource\Pages\ManageRecords::route('/')];}
}
