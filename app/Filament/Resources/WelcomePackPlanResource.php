<?php
namespace App\Filament\Resources;
use App\Models\{WelcomePackPlan,BudgetLine,Sponsorship};
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\{Select,TextInput,Textarea,Toggle,Repeater,Placeholder};
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\{EditAction,Action};
class WelcomePackPlanResource extends Resource {
 protected static ?string $model=WelcomePackPlan::class;
 protected static ?string $modelLabel='Welcome pack · t-shirts';
 protected static ?string $pluralModelLabel='Welcome pack · effectifs et tailles';
 protected static string|\UnitEnum|null $navigationGroup='2 · Chiffrer';
 protected static ?int $navigationSort=36;
 public static function form(Schema $schema): Schema {
  $costs=[];foreach(['shirt_cost_line_id'=>'T-shirts, impression comprise · quantité du lot','setup_cost_line_id'=>'Création, BAT et préparation · un lot','delivery_cost_line_id'=>'Livraison / conditionnement · un lot'] as $key=>$label)$costs[]=Select::make($key)->label($label)->options(fn(Get $get)=>BudgetLine::whereHas('scenario',fn($q)=>$q->where('event_project_id',$get('event_project_id'))->where('is_archived',false))->where('kind','cost')->whereNull('superseded_by_id')->get()->mapWithKeys(fn($l)=>[$l->id=>$l->scenario->name.' · '.$l->name]))->searchable();
  return $schema->columns(2)->components([
   Select::make('event_project_id')->label('Édition')->relationship('eventProject','name')->required()->live()->disabledOn('edit'),TextInput::make('name')->label('Lot')->required(),
   Repeater::make('cohorts')->label('Effectifs prévisionnels · personnes distinctes')->schema([Select::make('category')->label('Groupe')->options(WelcomePackPlan::CATEGORIES)->required(),TextInput::make('quantity')->label('Personnes')->integer()->minValue(0)->maxValue(10000)->required()])->columns(2)->minItems(1)->maxItems(20)->columnSpanFull(),
   TextInput::make('shirts_per_person')->label('T-shirts par personne pour toute l’édition')->integer()->minValue(1)->maxValue(3)->default(1)->required(),Placeholder::make('reserve_rule')->label('Réserve')->content('20 % des t-shirts nécessaires, arrondis une seule fois au supérieur. Répartition proportionnelle par taille ; pas de seconde majoration du prix.'),
   Repeater::make('sizes')->label('Répartition indicative · hors réserve')->schema([Select::make('size')->label('Taille')->options(array_combine(WelcomePackPlan::SIZES,WelcomePackPlan::SIZES))->required(),TextInput::make('quantity')->label('Personnes')->integer()->minValue(0)->maxValue(10000)->required()])->columns(2)->minItems(1)->maxItems(8)->columnSpanFull(),
   Toggle::make('use_roster')->label('Calculer sur le relevé nominatif plutôt que les hypothèses')->helperText('Importer les élus après enregistrement, puis ajouter QAPAS et le service. Une référence par personne, même avec plusieurs missions.'),
   Repeater::make('people')->label('Relevé privé · jamais transmis au sponsor')->schema([TextInput::make('person_key')->label('Référence unique au registre')->required()->maxLength(120)->helperText('Conserver la référence importée ; réutiliser cette fiche si la personne aide aussi le service.'),TextInput::make('name')->label('Nom')->required()->maxLength(120),Select::make('category')->label('Groupe principal')->options(WelcomePackPlan::CATEGORIES)->required(),Select::make('size')->label('Taille selon guide fournisseur')->options(array_combine(WelcomePackPlan::SIZES,WelcomePackPlan::SIZES)),Toggle::make('confirmed')->label('Taille confirmée par la personne')->default(false)])->columns(3)->default([])->maxItems(2000)->columnSpanFull(),
   Textarea::make('assumptions')->label('Hypothèses et effectifs à confirmer')->rows(4)->columnSpanFull(),Textarea::make('specification')->label('Modèle, marquage, guide de tailles, contenu du pack et délai')->rows(5)->columnSpanFull(),
   Select::make('sponsor_id')->label('Sponsor du lot')->options(fn(Get $get)=>Sponsorship::where('event_project_id',$get('event_project_id'))->where('scope','welcome_pack')->where('purpose','shirts')->pluck('name','id'))->searchable(),...$costs,
   Placeholder::make('approval')->label('Validation des tailles')->content(fn(?WelcomePackPlan $record)=>$record?->approved_at?'Validé le '.$record->approved_at->format('d/m/Y').' · '.$record->approval_evidence:'Prévision : relevé des tailles à valider. Toute modification du lot annule la validation.'),
  ]);
 }
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('name')->label('Lot')->wrap(),TextColumn::make('quantity')->label('T-shirts, réserve comprise')->state(fn(WelcomePackPlan $record)=>$record->report()['total']),TextColumn::make('use_roster')->label('Base')->formatStateUsing(fn($state)=>$state?'Relevé nominatif':'Hypothèse'),TextColumn::make('sponsor.sponsor_name')->label('Sponsor convenu')])->recordActions([
  Action::make('calculate')->label('Tailles et budget')->modalContent(fn(WelcomePackPlan $record)=>view('filament.welcome.report',['record'=>$record,'report'=>$record->report()]))->modalSubmitAction(false)->modalCancelActionLabel('Fermer')->modalWidth('5xl'),
  EditAction::make()->modalWidth('7xl'),Action::make('import')->label('Importer les joueurs élus')->action(fn(WelcomePackPlan $record)=>$record->importPlayers()),
  Action::make('budget')->label('Reporter la quantité au budget')->requiresConfirmation()->modalDescription('Met à jour le poste des t-shirts non engagé. Revalider le devis après changement de quantité. Les deux autres postes restent des lots distincts.')->action(fn(WelcomePackPlan $record)=>$record->synchronizeBudget()),
  Action::make('approve')->label('Valider les tailles')->schema([Textarea::make('evidence')->label('Guide fournisseur, confirmation individuelle et contrôle des doublons')->required()->maxLength(10000)])->action(fn(WelcomePackPlan $record,array $data)=>$record->update(['approved_at'=>now(),'approval_evidence'=>$data['evidence']])),
 ]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\WelcomePackPlanResource\Pages\ManageRecords::route('/')];}
}
