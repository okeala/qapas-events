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
  Textarea::make('evidence')->label('Devis, référence ou preuve de gratuité')->maxLength(5000)->columnSpanFull(),
 ];}
 public static function form(Schema $schema): Schema {return $schema->components([
  Section::make('Le spectacle')->columns(2)->schema([
   Select::make('event_project_id')->label('Édition')->relationship('eventProject','name')->required()->live()->disabledOn('edit'),
   TextInput::make('name')->label('Titre')->required()->maxLength(255),
   Select::make('track')->label('Parcours')->options(['official'=>'Compétition officielle','public'=>'Animation / essai public'])->required()->default('public'),
   Select::make('proposer_type')->label('Proposé par')->options(['organization'=>'Organisation','village'=>'Équipe de freguesia','independent'=>'Stand indépendant'])->required()->default('organization'),
   TextInput::make('proposer_name')->label('Équipe / stand')->maxLength(255),
   TextInput::make('sort_order')->label('Ordre dans le programme')->integer()->minValue(1)->maxValue(1000)->default(100),
   Textarea::make('summary')->label('Accroche de la carte publique')->maxLength(1000)->columnSpanFull(),
   Textarea::make('original_concept')->label('Idée d’origine · interne')->maxLength(10000)->columnSpanFull(),
   Textarea::make('rules')->label('Scénario et déroulement présentés au public')->required()->rows(5)->maxLength(10000)->columnSpanFull(),
   Textarea::make('scoring')->label('Chrono, points, reprises et départage')->maxLength(5000)->columnSpanFull(),
   Toggle::make('is_public')->label('Afficher la carte · le statut de conception reste visible'),
   Select::make('status')->label('État technique')->options(['idea'=>'Concept à valider','testing'=>'Essais / adaptations','approved'=>'Validée','suspended'=>'Suspendue','archived'=>'Archivée'])->required()->default('idea'),
  ])->columnSpanFull(),
  Section::make('Matériel et chiffrage')->description('Les quantités et prix inconnus restent à chiffrer. Sélectionner cette épreuve dans un scénario pour inclure ses coûts, sans recopier les lignes.')->columns(2)->schema([
   TextInput::make('planned_runs')->label('Nombre de passages prévus')->integer()->minValue(1)->maxValue(10000)->required()->default(1),
   Toggle::make('materials_complete')->label('Inventaire complet confirmé'),
   Repeater::make('materials')->label('Besoins')->relationship()->schema(self::materialFields())->columns(2)->defaultItems(0)->deletable(false)->collapsible()->itemLabel(fn(array $state)=>$state['name']??'Nouveau besoin')->columnSpanFull(),
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
  TextColumn::make('material_total')->label('Matériel TTC')->getStateUsing(fn(Activity $record)=>\App\Domain\Finance\Money::format($record->costReport()['gross_cents']).($record->costReport()['complete']?'':' · incomplet')),
  ])->defaultSort('sort_order')->recordActions([EditAction::make()->modalWidth('7xl')]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\ActivityResource\Pages\ManageRecords::route('/')];}
}
