<?php
namespace App\Filament\Resources;
use App\Models\FreguesiaInvitation;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Forms\Components\{Select,TextInput,Textarea,Toggle,DateTimePicker};
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
class FreguesiaInvitationResource extends Resource {
 protected static ?string $model=FreguesiaInvitation::class;
 protected static ?string $modelLabel='Commune invitée';
 protected static ?string $pluralModelLabel='Communes · invitations et présentation';
 protected static string|\UnitEnum|null $navigationGroup='3 · Mobiliser';
 protected static ?int $navigationSort=28;
 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([
 Select::make('event_project_id')->label('Édition')->relationship('eventProject','name')->required()->disabledOn('edit'),TextInput::make('source_key')->label('Référence stable')->required()->maxLength(255)->disabledOn('edit'),TextInput::make('name')->label('Nom de la freguesia / commune')->required()->maxLength(255),TextInput::make('municipality')->label('Município')->required()->maxLength(255),Select::make('status')->label('État de l’invitation')->options(['planned'=>'À inviter','invited'=>'Invitation remise / envoyée','accepted'=>'Participation acceptée','declined'=>'Décliné'])->required()->default('planned'),Toggle::make('is_public')->label('Publier la fiche et son état réel'),TextInput::make('source_url')->label('Source officielle publique')->url()->maxLength(1000),Textarea::make('summary_fr')->label('Présentation publique FR'),Textarea::make('summary_pt')->label('Présentation publique PT'),DateTimePicker::make('invited_at')->label('Invitation réellement remise / envoyée')->timezone('Europe/Lisbon'),Textarea::make('invitation_evidence')->label('Preuve privée de remise / envoi'),DateTimePicker::make('accepted_at')->label('Réponse positive reçue')->timezone('Europe/Lisbon'),Textarea::make('acceptance_evidence')->label('Preuve privée de la réponse')->helperText('Une réponse positive ne remplace pas la convention signée dans son dossier juridique.'),]);}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('name')->label('Commune')->searchable(),TextColumn::make('municipality')->label('Município')->sortable(),TextColumn::make('status')->label('Invitation')->badge(),TextColumn::make('is_public')->label('Publiée')->formatStateUsing(fn($state)=>$state?'Oui':'Non')])->defaultGroup('municipality')->recordActions([EditAction::make()->modalWidth('5xl')]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\FreguesiaInvitationResource\Pages\ManageRecords::route('/')];}
}
