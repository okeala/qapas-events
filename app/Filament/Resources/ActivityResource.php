<?php
namespace App\Filament\Resources;
use App\Models\Activity;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\{TextInput,Textarea,Select,Toggle,Repeater};
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Illuminate\Database\Eloquent\Builder;
class ActivityResource extends Resource {
 protected static ?string $model=Activity::class;
 protected static ?string $modelLabel='Épreuve';
 protected static ?string $pluralModelLabel='Épreuves et scénarios comiques';
 protected static string|\UnitEnum|null $navigationGroup='1 · Concevoir';
 protected static ?int $navigationSort=30;
 public static function materialFields(): array {return [
  TextInput::make('name')->label('Matériel / prestation')->required()->maxLength(255),
  TextInput::make('quantity')->label('Quantité, vide = inconnue')->integer()->minValue(1)->maxValue(1000000),
  TextInput::make('unit')->label('Unité (pièce, mètre, jour…)')->required()->default('pièce')->maxLength(40),
  Select::make('basis')->label('Besoin')->options(['fixed'=>'Pour toute l’épreuve','per_run'=>'Par passage'])->default('fixed')->required(),
  Select::make('procurement')->label('Obtention')->options(['purchase'=>'Achat','rental'=>'Location','loan'=>'Prêt','sponsor'=>'Apport sponsor'])->default('purchase')->required(),
  TextInput::make('unit_gross_cents')->label('Prix unitaire TTC en centimes, vide = inconnu')->integer()->minValue(0)->maxValue(100000000),
  TextInput::make('vat_basis_points')->label('IVA : 2300 = 23 %, vide = inconnu')->integer()->minValue(0)->maxValue(10000),
  Toggle::make('deductible')->label('IVA déductible confirmé'),
  TextInput::make('shared_cost_key')->label('Clé du poste commun (facultatif)')->maxLength(120)->helperText('Ex. common-broadcast. Si renseigné, le coût figure uniquement dans la ligne budgétaire commune de même clé.'),
  ...\App\Domain\Finance\Pricing::fields(),
  Textarea::make('evidence')->label('Devis, référence ou preuve de gratuité')->maxLength(5000)->columnSpanFull(),
 ];}
 public static function form(Schema $schema): Schema {return $schema->components([
  Section::make('Le spectacle')->columns(2)->schema([
   Select::make('event_project_id')->label('Édition')->relationship('eventProject','name')->required()->live()->disabledOn('edit'),
   TextInput::make('name')->label('Titre')->required()->maxLength(255),
   Select::make('track')->label('Parcours')->options(['official'=>'Compétition officielle','public'=>'Animation / essai public'])->required()->default('public'),
   Select::make('proposer_type')->label('Proposé par')->options(['organization'=>'Organisation','village'=>'Équipe de freguesia','independent'=>'Stand indépendant'])->required()->default('organization'),
   Select::make('stand_id')->label('Stand maintenu si le défi est retiré')->relationship('stand','name',fn($query,Get $get)=>$query->where('event_project_id',$get('event_project_id'))),TextInput::make('proposer_name')->label('Équipe / stand')->maxLength(255),
   TextInput::make('sort_order')->label('Ordre dans le programme')->integer()->minValue(1)->maxValue(1000)->default(100),
   Textarea::make('summary')->label('Accroche de la carte publique')->maxLength(1000)->columnSpanFull(),
   Textarea::make('original_concept')->label('Idée d’origine · interne')->maxLength(10000)->columnSpanFull(),
   Textarea::make('rules')->label('Scénario et déroulement présentés au public')->required()->rows(5)->maxLength(10000)->columnSpanFull(),
   Textarea::make('scoring')->label('Chrono, points, reprises et départage')->maxLength(5000)->columnSpanFull(),
   Toggle::make('relay_reveal')->label('Dévoiler le concept officiel après six freguesias avec relais actif')->helperText('Un relais documenté par freguesia du socle. La réalisation et l’emplacement restent soumis à confirmation.'),Toggle::make('is_public')->label('Afficher la carte'),Select::make('publication_level')->label('Révélation officielle')->options(['hidden'=>'Non dévoilée','teaser'=>'Accroche seulement','details'=>'Règles dévoilées','confirmed'=>'Programme confirmé et financé'])->required()->default('teaser'),Select::make('funding_scenario_id')->label('Scénario de financement')->relationship('fundingScenario','name',fn($query,Get $get)=>$query->where('event_project_id',$get('event_project_id'))),TextInput::make('youtube_id')->label('Identifiant vidéo YouTube (11 caractères)')->regex('/^[A-Za-z0-9_-]{11}$/')->helperText('Lien vers un extrait ou un live préparé dans YouTube ; aucune diffusion automatique.'),
   Select::make('status')->label('État technique')->options(['idea'=>'Concept à valider','testing'=>'Essais / adaptations','approved'=>'Validée','suspended'=>'Suspendue','archived'=>'Archivée'])->required()->default('idea'),
  ])->columnSpanFull(),
  Section::make('Matériel et chiffrage')->description('Les quantités et prix inconnus restent à chiffrer. Sélectionner cette épreuve dans un scénario pour inclure ses coûts, sans recopier les lignes.')->columns(2)->schema([
   TextInput::make('planned_runs')->label('Nombre de passages prévus')->integer()->minValue(1)->maxValue(10000)->required()->default(1),
   Toggle::make('materials_complete')->label('Inventaire complet confirmé'),
   Repeater::make('materials')->hiddenOn('edit')->label('Besoins')->relationship()->schema(self::materialFields())->columns(2)->defaultItems(0)->deletable(false)->collapsible()->itemLabel(fn(array $state)=>$state['name']??'Nouveau besoin')->columnSpanFull(),
  ])->columnSpanFull(),
  Section::make('Accès, implantation et validation')->columns(2)->schema([
   Select::make('risk_category')->label('Type de dispositif')->options(['manual'=>'Jeu manuel','machinery'=>'Engin motorisé','water'=>'Eau','grafting'=>'Démonstration de greffe'])->required()->default('manual'),
   Select::make('access')->label('Qui joue ?')->options(['everyone'=>'Grand public, conditions annoncées','team'=>'Équipe inscrite','qualified'=>'Pilotes / opérateurs qualifiés'])->required()->default('team'),
   Textarea::make('operator_requirements')->label('Compétences et titres à contrôler · texte public')->maxLength(5000)->columnSpanFull(),
   Select::make('terrace_id')->label('Terrasse de l’épreuve')->relationship('terrace','name',fn(Builder $query,Get $get)=>$query->where('event_project_id',$get('event_project_id')))->helperText('Position précise par clic dans le menu Plan des terrasses.'),
   Select::make('spectator_terrace_id')->label('Terrasse des spectateurs')->relationship('spectatorTerrace','name',fn(Builder $query,Get $get)=>$query->where('event_project_id',$get('event_project_id'))),
   TextInput::make('referee')->label('Arbitre / responsable')->maxLength(255),TextInput::make('capacity')->label('Participants par passage')->integer()->minValue(0)->maxValue(1000)->required()->default(0),
   Textarea::make('technical_review')->label('Contraintes et adaptations à vérifier · interne')->rows(4)->maxLength(10000)->columnSpanFull(),
   Toggle::make('risk_reviewed')->label('Évaluation des risques validée'),
   Textarea::make('risk_evidence')->label('Preuve, responsable et périmètre de validation')->maxLength(10000)->columnSpanFull(),
   Toggle::make('broadcast_planned')->label('Captation et grand écran prévus'),
   Textarea::make('media_plan')->label('Régie, droits à l’image et diffusion · interne')->maxLength(5000)->columnSpanFull(),
  ])->columnSpanFull(),
 ]);}
 public static function table(Table $table): Table {return $table->columns([
  TextColumn::make('sort_order')->label('Ordre')->sortable(),TextColumn::make('name')->label('Épreuve')->searchable()->wrap(),TextColumn::make('eventProject.name')->label('Édition'),TextColumn::make('track')->label('Parcours')->badge(),TextColumn::make('status')->label('État')->badge(),TextColumn::make('terrace.name')->label('Terrasse')->placeholder('À placer'),
  TextColumn::make('preparation')->label('Essais')->getStateUsing(fn(Activity $record)=>$record->preparation()->label($record))->wrap(),TextColumn::make('material_total')->label('Matériel TTC')->getStateUsing(fn(Activity $record)=>\App\Domain\Finance\Money::format($record->costReport()['gross_cents']).($record->costReport()['complete']?'':' · incomplet')),
  ])->defaultSort('sort_order')->recordActions([\Filament\Actions\Action::make('withdraw')->label('Maintenir uniquement le stand')->visible(fn($record)=>$record->proposer_type!=='organization'&&$record->participation_decision!=='stand_only')->schema([Textarea::make('reason')->label('Motif du retrait du défi')->required()->minLength(10)->maxLength(5000)])->action(fn($record,array $data)=>$record->preparation()->withdraw($record,$data['reason'])),\Filament\Actions\Action::make('costing')->label('Chiffrer')->url(fn($record)=>static::getUrl('edit',['record'=>$record])),EditAction::make()->modalWidth('7xl')]);}
 public static function getRelations(): array {return [\App\Filament\RelationManagers\MaterialsRelationManager::class,\App\Filament\RelationManagers\ActivityTrialsRelationManager::class];}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\ActivityResource\Pages\ManageRecords::route('/'),'edit'=>\App\Filament\Resources\ActivityResource\Pages\EditRecord::route('/{record}/edit')];}
}
