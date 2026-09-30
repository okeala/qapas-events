<?php
namespace App\Filament\Resources;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\{Select,TextInput,Textarea,Toggle,DateTimePicker,Placeholder};
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\{EditAction,Action};
class CommunityAwardResource extends Resource {
 protected static ?string $model=\App\Models\CommunityAward::class;
 protected static ?string $modelLabel='Forquilha de Ouro · prix et 2027';
 protected static ?string $pluralModelLabel='Forquilha de Ouro · prix et 2027';
 protected static string|\UnitEnum|null $navigationGroup='6 · Clôturer';
 protected static ?int $navigationSort=20;
 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([Select::make('event_project_id')->label('Édition')->relationship('eventProject','name')->required()->live()->disabledOn('edit'),TextInput::make('name')->label('Nom')->required()->maxLength(255),Select::make('budget_line_id')->label('Coût budgétaire existant · ne pas dupliquer')->options(fn(Get $get)=>\App\Models\BudgetLine::whereHas('scenario',fn($q)=>$q->where('event_project_id',$get('event_project_id')))->where('kind','cost')->pluck('name','id'))->searchable(),
 Select::make('winner_team_id')->label('Équipe victorieuse')->options(fn(Get $get)=>\App\Models\Team::where('event_project_id',$get('event_project_id'))->pluck('name','id'))->searchable(),Select::make('status')->label('Prix communautaire de 500 €')->options(['planned'=>'Prévu au budget','awarded'=>'Attribué après résultats','paid'=>'Versement rapproché'])->default('planned')->required(),Textarea::make('rules_fr')->label('Règlement FR')->rows(6)->required(),Textarea::make('rules_pt')->label('Regulamento PT')->rows(6)->required(),Textarea::make('results_evidence')->label('Résultats, arbitrage et procès-verbal'),TextInput::make('recipient_entity')->label('Bénéficiaire juridique des 500 €'),Textarea::make('prize_project')->label('Événement convivial de Noël à cofinancer, échéance et justificatifs'),Textarea::make('trophy_custody')->label('Dépôt et exposition de la Forquilha à la junta, remise et restitution'),DateTimePicker::make('paid_at')->label('Paiement réel')->timezone('Europe/Lisbon'),Textarea::make('payment_evidence')->label('Preuve de remise des 500 €'),Select::make('next_host_status')->label('Édition 2027 organisée par QAPAS chez le vainqueur')->options(['proposed'=>'Principe proposé','preparing'=>'Convention et site en préparation','confirmed'=>'Organisation confirmée','alternative'=>'Solution alternative convenue'])->default('proposed')->required(),Textarea::make('next_host_evidence')->label('Convention 2027, site, autorisations, budget et calendrier'),Toggle::make('is_public')->label('Afficher les résultats validés'),
]);}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('name')->label('Nom')->searchable()->wrap(),TextColumn::make('status')->label('Prix')->badge(),TextColumn::make('winner.freguesia')->label('Freguesia victorieuse'),TextColumn::make('next_host_status')->label('Accueil 2027')->badge(),TextColumn::make('updated_at')->label('Mise à jour')->dateTime('d/m/Y H:i')->sortable()])->defaultSort('updated_at','desc')->recordActions([EditAction::make()->modalWidth('7xl')]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\CommunityAwardResource\Pages\ManageRecords::route('/')];}
}
