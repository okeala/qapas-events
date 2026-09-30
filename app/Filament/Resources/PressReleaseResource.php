<?php
namespace App\Filament\Resources;
use App\Models\PressRelease;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\{TextInput,Textarea,Select,Toggle,DateTimePicker};
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
class PressReleaseResource extends Resource {
 protected static ?string $model=PressRelease::class;
 protected static ?string $modelLabel='Communiqué de presse';
 protected static ?string $pluralModelLabel='Presse · avant, pendant, après';
 protected static string|\UnitEnum|null $navigationGroup='3 · Mobiliser';
 protected static ?int $navigationSort=50;
 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([
  Select::make('event_project_id')->label('Édition')->relationship('eventProject','name')->required()->live()->disabledOn('edit'),TextInput::make('name')->label('Titre FR')->required()->maxLength(255),TextInput::make('title_pt')->label('Titre PT')->maxLength(255),TextInput::make('owner')->label('Responsable éditorial')->maxLength(255),
  Select::make('phase')->label('Phase')->options(['before'=>'Avant','during'=>'Pendant','after'=>'Après'])->required(),Select::make('kind')->label('Type')->options(['mobilization'=>'Projet / appel à participation','revelation'=>'Révélation après six relais répartis','official'=>'Annonce confirmée','reminder'=>'Rappel / invitation médias','update'=>'Point presse','results'=>'Résultats','review'=>'Bilan'])->required(),
  Textarea::make('body')->label('Texte FR')->rows(8)->maxLength(30000)->columnSpanFull(),Textarea::make('body_pt')->label('Texte PT')->rows(8)->maxLength(30000)->columnSpanFull(),
  Select::make('depends_on')->label('Jalons requis avant diffusion')->multiple()->options(fn(Get $get)=>\App\Models\Idea::where('event_project_id',$get('event_project_id'))->orderBy('sort_order')->pluck('name','id')->all()),Select::make('scenario_id')->label('Scénario de l’annonce confirmée')->relationship('scenario','name',fn(\Illuminate\Database\Eloquent\Builder $query,Get $get)=>$query->where('event_project_id',$get('event_project_id'))),
  DateTimePicker::make('target_at')->label('Date cible fixe (prioritaire)')->timezone('Europe/Lisbon'),TextInput::make('day_offset')->label('Sinon, décalage en jours (J−14 = −14)')->integer()->minValue(-365)->maxValue(365),Select::make('date_anchor')->label('Date de référence')->options(['start'=>'Début de l’événement','end'=>'Fin de l’événement'])->required(),
  Select::make('status')->label('État')->options(['draft'=>'Brouillon','review'=>'À relire','approved'=>'Validé / prêt à diffuser','published'=>'Diffusé','archived'=>'Archivé'])->required(),DateTimePicker::make('published_at')->label('Date réelle de diffusion')->timezone('Europe/Lisbon')->maxDate(now()),Toggle::make('is_public')->label('Visible dans l’espace presse public après validation'),
  Textarea::make('evidence')->label('Validation éditoriale, faits vérifiés et preuve de diffusion')->maxLength(10000)->columnSpanFull(),Textarea::make('media_targets')->label('Médias ciblés · motivation et pertinence du contact')->maxLength(10000),Textarea::make('assets_and_rights')->label('Références photos, liens, crédits et droits vérifiés')->maxLength(10000),Toggle::make('auto_dispatch')->label('Diffuser automatiquement cette version après les jalons validés')->helperText('Préparer les canaux, valider le contenu, puis autoriser la diffusion. Site automatique ; email / FB / X exigent comptes officiels et configuration.'),TextInput::make('publication_version')->label('Version de diffusion')->default('1')->required(),Select::make('channels')->label('Canaux')->multiple()->options(['website'=>'Site officiel','email'=>'Communiqué par email','facebook'=>'Page Facebook officielle','x'=>'Compte X officiel']),\Filament\Forms\Components\TagsInput::make('press_emails')->label('Destinataires presse professionnels explicitement autorisés'),Textarea::make('dispatch_authorization')->label('Autorisation de diffusion et pertinence des destinataires'),Textarea::make('social_text_pt')->label('Texte social PT · tenir compte des noms et du lien ajoutés')->maxLength(3000),Select::make('sponsor_ids')->label('Sponsors convenus à citer')->multiple()->options(fn(Get $get)=>\App\Models\Sponsorship::where('event_project_id',$get('event_project_id'))->where('status','agreed')->pluck('sponsor_name','id')),Select::make('relay_ids')->label('Points-relais actifs à citer')->multiple()->options(fn(Get $get)=>\App\Models\StandPartner::whereHas('stand',fn($q)=>$q->where('event_project_id',$get('event_project_id')))->where('status','active')->whereNotNull('relay_slot')->pluck('name','id')),Textarea::make('coverage')->label('Retombées : articles, liens et dates')->maxLength(10000)->columnSpanFull()
 ]);}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('name')->label('Communiqué')->searchable()->wrap(),TextColumn::make('eventProject.name')->label('Édition'),TextColumn::make('phase')->label('Phase')->badge(),TextColumn::make('status')->label('État')->badge(),TextColumn::make('planned_date')->label('Date cible Lisbonne')->state(fn(PressRelease $r)=>$r->plannedDate()?->timezone('Europe/Lisbon')->format('d/m/Y H:i')??'Date à fixer')])->filters([SelectFilter::make('event_project_id')->label('Édition')->relationship('eventProject','name')])->recordActions([EditAction::make()->modalWidth('7xl')]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\PressReleaseResource\Pages\ManageRecords::route('/')];}
}
