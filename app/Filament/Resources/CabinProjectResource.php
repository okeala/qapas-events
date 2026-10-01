<?php
namespace App\Filament\Resources;

use App\Domain\Cabins\CabinRules;
use App\Models\{CabinProject, Stand, BudgetLine};
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\{Section, Utilities\Get};
use Filament\Forms\Components\{TextInput, Textarea, Select, Toggle, Repeater, DatePicker};
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\{Action, EditAction};

class CabinProjectResource extends Resource
{
    protected static ?string $model = CabinProject::class;
    protected static ?string $modelLabel = 'Construction de stand';
    protected static ?string $pluralModelLabel = 'Construction des stands';
    protected static string|\UnitEnum|null $navigationGroup = '1 · Concevoir';
    protected static ?int $navigationSort = 45;

    private static function budgetOptions(Get $get, string $kind): array
    {
        return BudgetLine::where('stand_id',$get('stand_id'))->where('kind',$kind)->whereNull('superseded_by_id')
            ->whereHas('scenario',fn($q)=>$q->where('event_project_id',$get('event_project_id'))->where('is_archived',false))->pluck('name','id')->all();
    }
    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Section::make('Da mimosa ao stand')->description('Largeur et hauteur imposées de 2,40 m ; longueur libre à partir de 2,40 m. Les travées ne suivent aucun pas de 2,40 m imposé. Pièce maîtresse : le raccord d’échafaudage, autour duquel s’assemblent les tubes récupérés auprès des démolisseurs ; bardage et couverture en plessis de mimosa et/ou cannes. Ligatures en sisal ou fibre naturelle adaptée. Toiture possible : plessis de mimosa avec surcouverture de paille propre récupérée, à éprouver sur prototype. Pas d’amiante, de plastique ni de bois neuf. Décoration libre en récupération.')->columns(2)->schema([
                Select::make('event_project_id')->label('Édition')->relationship('eventProject','name')->required()->live()->disabledOn('edit'),
                Select::make('stand_id')->label('Stand / emplacement')->options(fn(Get $get)=>Stand::where('event_project_id',$get('event_project_id'))->pluck('name','id')->all())->required()->live()->disabledOn('edit'),
                TextInput::make('name')->label('Nom du dossier de construction')->required()->maxLength(200),
                Select::make('supply_mode')->label('Construction et fourniture')->options(['team_build'=>'L’équipe construit son stand','qapas_rental'=>'QAPAS fabrique et loue à l’indépendant'])->required()->default('team_build')->disabledOn('edit'),
                TextInput::make('owner')->label('Responsable du chantier')->maxLength(255),
                Select::make('status')->label('Avancement')->options(CabinRules::STATUSES)->default('concept')->required()->helperText('La réception requiert inventaire, implantation, contrôle sur site et suivi de la végétation. Elle ne vaut pas validation du financement.'),
                TextInput::make('width_mm')->label('Largeur imposée · mm')->integer()->rules(['in:2400'])->default(2400)->required(),
                TextInput::make('depth_mm')->label('Longueur totale · mm')->integer()->minValue(2400)->maxValue(240000)->default(2400)->required()->helperText('Longueur libre : par exemple 3 500 ou 5 000 mm. Entraxes des portiques selon calcul ; devis et emprise à revoir.'),
                TextInput::make('height_mm')->label('Hauteur totale · mm')->integer()->rules(['in:2400'])->default(2400)->required(),
            ])->columnSpanFull(),
            Section::make('Longueur, portiques et dimensionnement')->description('L’entraxe des portiques vient d’un dimensionnement, pas d’une règle automatique de 2,40 m. Forces en N, charges réparties en N/m ou N/m², moments en N·m. L’application conserve la preuve ; elle ne calcule ni ne certifie la structure.')->columns(2)->schema([
                TextInput::make('frame_diameter_mm')->label('Diamètre extérieur envisagé des tubes · mm')->integer()->minValue(1)->maxValue(2000)->default(30)->required()->helperText('Hypothèse du projet : 30 mm, à mesurer sur les tubes récupérés. Confirmer épaisseur, état et raccords compatibles ; ce diamètre ne valide aucune résistance.'),
                TextInput::make('frame_spacing_mm')->label('Entraxe maximal des portiques prévu par le calcul · mm')->integer()->minValue(1)->maxValue(240000),
                TextInput::make('structural_reviewer')->label('Auteur / relecteur de l’évaluation interne')->maxLength(255),
                DatePicker::make('structural_reviewed_on')->label('Date de revue de l’évaluation interne')->maxDate(today())->helperText('Enregistrer d’abord dimensions, inventaire, diamètre et entraxe ; toute modification annule cette date. Puis vérifier la note et enregistrer sa validation.'),
                Textarea::make('structural_evidence')->label('Référence de l’évaluation interne, calculs, essais et périmètre vérifié')->helperText('Chaque stand, y compris le modèle de base : identifier dimensions, entraxes, sections/état des tubes de 30 mm envisagés, référence des raccords, plessis et paille, vent, toiture sèche/mouillée, ancrages et sol. Inscrire les limites d’utilisation, conditions de montage et constats du prototype. Revoir emprise, circulations, besoins et devis ; agrandir ne crée pas un second stand ni une recette automatique.')->maxLength(10000)->rows(4)->columnSpanFull(),
            ])->columnSpanFull(),
            Section::make('Inventaire de récupération')->description('Aucun quota végétal par stand ; prélèvements dans les zones désignées du chantier. Quantités réelles à relever ; mimosas et cannes de 6 cm de diamètre au maximum. Raccords d’échafaudage : type, référence et compatibilité avec les tubes à documenter dans l’origine. Tubes de démolition, palettes saines pour les décors, cannes identifiées, perches et branchages de contrôle. Seuls connecteurs, visserie et ligatures peuvent être achetés neufs. Les équipements/outils de chantier sont chiffrés séparément.')->schema([
                Repeater::make('materials')->label('Pièces et panneaux')->schema([
                    TextInput::make('name')->label('Pièce / usage précis')->required()->maxLength(200),
                    Select::make('part')->label('Partie du stand')->options(CabinRules::PARTS)->required(),
                    Select::make('material')->label('Matière')->options(CabinRules::MATERIALS)->required()->live(),
                    TextInput::make('max_diameter_mm')->label('Diamètre maximal du lot végétal · mm')->integer()->minValue(1)->maxValue(60)->visible(fn(Get $get)=>in_array($get('material'),['mimosa','cane'],true))->required(fn(Get $get)=>in_array($get('material'),['mimosa','cane'],true))->helperText('Mimosas et cannes : 6 cm maximum. Relever le plus gros diamètre du lot.'),
                    Select::make('source')->label('Provenance')->options(CabinRules::SOURCES)->required(),
                    TextInput::make('quantity')->label('Quantité')->integer()->minValue(1)->maxValue(100000)->required(),
                    TextInput::make('unit')->label('Unité')->required()->maxLength(40)->default('pièce'),
                    Textarea::make('origin')->label('Origine, don/prêt, espèce et état · privé')->maxLength(2000)->required()->columnSpanFull(),
                ])->columns(2)->defaultItems(0)->maxItems(80)->collapsible()->itemLabel(fn(array $state)=>$state['name']??'Matériau'),
            ])->columnSpanFull(),
            Section::make('Contrôle de la végétation et réception')->description('Valoriser le bois du chantier ne prouve pas l’éradication. Prévenir la dispersion de graines, gousses, terre et rhizomes ; préparation des parties végétales et suivi des rejets à documenter. Après démontage : tri des végétaux, broyage et paillage sur place suivant protocole sans propagation ; métaux récupérés. Décomposition progressive, puis plantations et suivi. Pas de chantier de coupe chronométré devant le public.')->columns(2)->schema([
                Textarea::make('harvest_origin')->label('Parcelle, autorisation du propriétaire et origine des végétaux')->maxLength(5000)->columnSpanFull(),
                Textarea::make('control_plan')->label('Méthode de contrôle, tri, broyage/paillage sans propagation et plantations après démontage')->maxLength(10000)->rows(4)->columnSpanFull(),
                TextInput::make('follow_up_owner')->label('Responsable du suivi des rejets / repousses')->maxLength(255),
                DatePicker::make('follow_up_on')->label('Prochaine inspection prévue'),
                Textarea::make('follow_up_notes')->label('Constats du suivi, dates et actions')->maxLength(10000)->columnSpanFull(),
                Textarea::make('reception_evidence')->label('Réception : gabarit mesuré, matériaux inspectés, assemblages, stabilité, fixation, accès, feu et pluie')->helperText('Référence et auteur du contrôle sur place. Aucun dimensionnement structurel ni étanchéité certifiés par l’application. Toute modification du dossier ou déplacement du stand impose une nouvelle réception.')->maxLength(10000)->rows(4)->columnSpanFull(),
            ])->columnSpanFull(),
            Section::make('Coûts et location')->description('Le broyeur est acheté par QAPAS et financé par l’initiative : investissement commun, décaissement intégral. Le bois disponible n’efface pas découpe, raccords, sisal, préparation, main-d’œuvre, montage, reprise, broyage et suivi. Les dépenses QAPAS sont comptées une seule fois dans les lignes du scénario. La caution reste séparée des recettes.')->columns(2)->schema([
                Select::make('cost_line_id')->label('Fabrication QAPAS · poste budgétaire')->options(fn(Get $get)=>self::budgetOptions($get,'cost'))->searchable()->visible(fn(Get $get)=>$get('supply_mode')==='qapas_rental'),
                Select::make('rental_line_id')->label('Location · ligne de supplément éventuel')->options(fn(Get $get)=>self::budgetOptions($get,'revenue'))->searchable()->visible(fn(Get $get)=>$get('supply_mode')==='qapas_rental'),
                Select::make('rental_pricing')->label('Traitement commercial de la location')->options(['unpriced'=>'À chiffrer, aucune nouvelle recette','included'=>'Incluse dans le prix global du stand','extra'=>'Supplément distinct explicite'])->default('unpriced')->required()->helperText('Ligne de location à quantité zéro tant que non tarifée ou incluse. Aucune seconde recette pour une prestation déjà comprise.'),
                TextInput::make('deposit_cents')->label('Caution remboursable · centimes')->integer()->minValue(0),
                Textarea::make('rental_terms')->label('Durée, inclusions, livraison, montage, démontage, état, casse et restitution')->maxLength(10000)->columnSpanFull(),
                TextInput::make('participant_cost_cents')->label('Budget de construction payé par l’équipe · centimes')->integer()->minValue(0),
                Textarea::make('participant_cost_evidence')->label('Détail participant, contributions et justificatifs · hors budget QAPAS')->maxLength(5000),
            ])->columnSpanFull(),
            Section::make('Nomenclature et coût de construction du stand')->description('Hypothèse monopente fournie le 1er octobre : arrière 2,40 m, avant 2,10 m ; tôles bac acier. Le détail sert au chiffrage, pas à valider la structure. Il ne modifie pas l’inventaire ni la configuration réceptionnée. Vérifier les références de raccords pour le diamètre réel, les ancrages et le contreventement.')->schema([
                \Filament\Schemas\Components\View::make('filament.stands.construction')->viewData(function(Get $get){try{$report=\App\Domain\Stands\ConstructionCosting::report($get('construction_costs')??[]);}catch(\Illuminate\Validation\ValidationException){$report=null;}return ['costing'=>$report];}),
                Select::make('construction_price_basis')->label('Base des prix prévisionnels saisis')->options(['unknown'=>'IVA à préciser','gross'=>'TTC','net'=>'HT'])->default('unknown')->required(),
                TextInput::make('construction_vat_basis_points')->label('IVA commune à ce lot · points de base (2300 = 23 %)')->integer()->minValue(0)->maxValue(10000)->helperText('À confirmer. Si plusieurs traitements IVA s’appliquent, ventiler dans le budget ; ne pas transférer un lot unique.'),
                Repeater::make('construction_costs')->label('Composants et prestations · référence neuf / coût prévu')->schema([
                    TextInput::make('name')->label('Composant / prestation')->required()->maxLength(255),Select::make('group')->label('Groupe')->options(\App\Domain\Stands\ConstructionCosting::GROUPS)->required(),
                    TextInput::make('quantity')->label('Quantité')->integer()->minValue(0)->maxValue(10000)->live(onBlur:true),Select::make('basis')->label('Prix par')->options(['metre'=>'Mètre de tube','piece'=>'Pièce','lot'=>'Lot'])->required()->live(),TextInput::make('length_mm')->label('Longueur unitaire · mm')->integer()->minValue(1)->maxValue(240000)->visible(fn(Get $get)=>$get('basis')==='metre')->live(onBlur:true),
                    TextInput::make('reference_unit_cents')->label('Prix neuf indicatif · centimes')->integer()->minValue(0)->maxValue(1000000000)->live(onBlur:true),TextInput::make('unit_cents')->label('Prix prévisionnel retenu · centimes')->integer()->minValue(0)->maxValue(1000000000)->live(onBlur:true),
                    Select::make('source')->label('Approvisionnement prévu')->options(['recovered'=>'Casse / récupération','new'=>'Neuf','donation'=>'Don à documenter','loan'=>'Prêt à documenter','service'=>'Prestation','undecided'=>'À décider'])->required(),Textarea::make('notes')->label('Contenu du lot, devis, exclusions')->maxLength(2000)->columnSpanFull(),
                ])->columns(3)->collapsible()->collapsed()->itemLabel(fn(array $state)=>$state['name']??'Poste')->default(\App\Domain\Stands\ConstructionCosting::defaults())->maxItems(100),
            ])->columnSpanFull(),
            Section::make('Présentation')->columns(2)->schema([
                Toggle::make('is_public')->label('Présenter la construction de ce stand quand le défi et sa fiche sont publics'),
                Textarea::make('summary_fr')->label('Histoire du stand · FR')->maxLength(5000),
                Textarea::make('summary_pt')->label('História do stand · PT')->maxLength(5000),
            ])->columnSpanFull(),
        ]);
    }
    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Stand')->searchable()->wrap(),
            TextColumn::make('stand.freguesia')->label('Freguesia')->placeholder('Indépendant / à attribuer'),
            TextColumn::make('supply_mode')->label('Fourniture')->formatStateUsing(fn($state)=>$state==='team_build'?'Équipe constructrice':'Location QAPAS')->badge(),
            TextColumn::make('stand.pitch_number')->label('Emplacement'),
            TextColumn::make('stand.quartel.name')->label('Quartel'),
            TextColumn::make('status')->label('Avancement')->formatStateUsing(fn($state,CabinProject $record)=>$state==='received'&&!$record->received()?'Réception à refaire':CabinRules::STATUSES[$state])->badge(),
            TextColumn::make('follow_up_on')->label('Suivi végétation')->date('d/m/Y')->sortable(),
        ])->filters([
            SelectFilter::make('event_project_id')->label('Édition')->relationship('eventProject','name'),
            SelectFilter::make('supply_mode')->label('Fourniture')->options(['team_build'=>'Équipes','qapas_rental'=>'Locations QAPAS']),
            SelectFilter::make('status')->label('Avancement')->options(CabinRules::STATUSES),
        ])->defaultSort('stand_id')->recordActions([
            EditAction::make()->modalWidth('7xl'),
            Action::make('budget')->label('Reporter le chiffrage au budget')->modalDescription('Enregistrer le détail avant cette action. Coût QAPAS si QAPAS fournit le stand ; sinon coût de l’équipe, hors break-even QAPAS. Le prix neuf de référence ne sera jamais repris automatiquement.')->schema([
                Select::make('scenario_id')->label('Scénario')->options(fn(CabinProject $record)=>\App\Models\Scenario::where('event_project_id',$record->event_project_id)->where('is_archived',false)->whereHas('includedStands',fn($q)=>$q->where('stands.id',$record->stand_id))->pluck('name','id'))->default(fn(CabinProject $record)=>$record->costLine?->scenario_id??$record->eventProject->launchScenario()?->id)->required(),
            ])->action(function(CabinProject $record,array $data){app(\App\Domain\Stands\ConstructionCosting::class)->apply($record,(int)$data['scenario_id']);\Filament\Notifications\Notification::make()->title('Prévision mise à jour, sans commande ni paiement')->success()->send();}),
            Action::make('stand')->label('Emplacement et budget')->url(fn(CabinProject $record)=>StandResource::getUrl('edit',['record'=>$record->stand])),
            Action::make('preview')->label('Aperçu local')->visible(fn()=>app()->environment('local'))->url(fn(CabinProject $record)=>route('cabins.preview',['project'=>$record->eventProject->slug]).'#stand-'.$record->public_id),
        ]);
    }
    public static function getPages(): array { return ['index'=>\App\Filament\Resources\CabinProjectResource\Pages\ManageRecords::route('/')]; }
}
