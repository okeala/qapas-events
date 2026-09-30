<?php
namespace App\Filament\Resources;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\{Select,TextInput,Textarea,Toggle,DateTimePicker,Placeholder};
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\{EditAction,Action};
class FeedbackCampaignResource extends Resource {
 protected static ?string $model=\App\Models\FeedbackCampaign::class;
 protected static ?string $modelLabel='Remerciements et avis';
 protected static ?string $pluralModelLabel='Remerciements et avis';
 protected static string|\UnitEnum|null $navigationGroup='6 · Clôturer';
 protected static ?int $navigationSort=30;
 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([Select::make('event_project_id')->label('Édition')->relationship('eventProject','name')->required()->live()->disabledOn('edit'),TextInput::make('name')->label('Nom')->required()->maxLength(255),
 TextInput::make('version')->label('Version')->default('1')->required(),Textarea::make('body_fr')->label('Remerciements et invitation FR')->rows(6)->required(),Textarea::make('body_pt')->label('Agradecimento e convite PT')->rows(6)->required(),DateTimePicker::make('send_after')->label('Envoi après clôture')->timezone('Europe/Lisbon'),DateTimePicker::make('closes_at')->label('Fin du recueil des avis')->timezone('Europe/Lisbon'),Textarea::make('authorization')->label('Textes approuvés, destinataires et base de contact vérifiés'),Toggle::make('is_enabled')->label('Autoriser la campagne après l’événement')->helperText('Enregistrer les textes, puis activer. Comptes externes configurés et invitations marquées prêtes nécessaires ; aucune relance automatique des résultats incertains.'),
]);}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('name')->label('Nom')->searchable()->wrap(),TextColumn::make('invitations_count')->counts('invitations')->label('Invitations'),TextColumn::make('responses')->label('Retours')->state(fn($record)=>$record->invitations()->whereNotNull('responded_at')->count()),TextColumn::make('rating')->label('Note moyenne / 5')->state(fn($record)=>number_format((float)$record->invitations()->whereNotNull('responded_at')->avg('rating'),1,',',' ')),TextColumn::make('closes_at')->label('Clôture des avis')->dateTime('d/m/Y')])->defaultSort('updated_at','desc')->recordActions([EditAction::make()->modalWidth('7xl'),Action::make('prepare')->label('Préparer les destinataires')->action(fn($record)=>app(\App\Domain\Community\Feedback::class)->prepare($record))]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\FeedbackCampaignResource\Pages\ManageRecords::route('/')];}
}
