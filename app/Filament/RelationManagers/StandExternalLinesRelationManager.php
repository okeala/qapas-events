<?php
namespace App\Filament\RelationManagers;
use App\Models\{StandExternalLine,Scenario,BudgetLine};
use App\Domain\Finance\Money;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Forms\Components\{TextInput,Textarea,Select};
use Filament\Tables\{Table,Columns\TextColumn,Filters\SelectFilter,Grouping\Group};
use Filament\Actions\{CreateAction,EditAction};
use Illuminate\Database\Eloquent\Builder;
class StandExternalLinesRelationManager extends RelationManager {
 protected static string $relationship='externalLines';
 protected static ?string $title='Budget des participants · hors QAPAS';
 public function scenarios(): Builder {return Scenario::where('event_project_id',$this->getOwnerRecord()->event_project_id)->where('is_archived',false)->whereHas('includedStands',fn($q)=>$q->where('stands.id',$this->getOwnerRecord()->id));}
 private function defaultScenario(): ?int {$id=$this->getOwnerRecord()->eventProject->launchScenario()?->id;return $this->scenarios()->whereKey($id)->exists()?$id:$this->scenarios()->value('id');}
 public function form(Schema $schema): Schema {return $schema->columns(2)->components([
  Select::make('scenario_id')->label('Scénario')->options(fn()=>$this->scenarios()->pluck('name','id'))->default(fn()=>$this->getTableFilterState('scenario_id')['value']??$this->defaultScenario())->required()->disabledOn('edit'),
  Select::make('party')->label('À qui appartient ce budget ?')->options(StandExternalLine::PARTIES)->required(),TextInput::make('holder')->label('Équipe, entreprise ou bénéficiaire exact')->maxLength(255)->required(),
  TextInput::make('name')->label('Poste')->required()->maxLength(255),Select::make('kind')->label('Nature pour cet intervenant')->options(['cost'=>'Coût à sa charge','revenue'=>'Recette lui revenant'])->required(),TextInput::make('quantity')->label('Quantité prévue')->integer()->minValue(0)->maxValue(1000000),TextInput::make('unit')->label('Unité')->default('lot')->required()->maxLength(80),TextInput::make('unit_gross_cents')->label('Prix unitaire TTC · centimes')->integer()->minValue(0)->maxValue(1000000000)->helperText('Vide = inconnu. Saisie prévisionnelle, aucune preuve d’encaissement.'),
  Select::make('qapas_budget_line_id')->label('Contrepartie dans le budget QAPAS, si échange avec QAPAS')->options(fn()=>BudgetLine::where('stand_id',$this->getOwnerRecord()->id)->whereNull('superseded_by_id')->whereIn('kind',['cost','revenue'])->pluck('name','id'))->searchable()->helperText('Facultatif. Lier le poste QAPAS existant et de nature opposée. Aucun poste QAPAS n’est créé automatiquement.'),
  Textarea::make('evidence')->label('Source, hypothèses, ce qui est compris et qui paie')->maxLength(10000)->columnSpanFull(),
 ]);}
 private function context(array $data): array {abort_unless(auth('admin')->user()?->is_active,403);$data['stand_id']=$this->getOwnerRecord()->id;return $data;}
 public function table(Table $table): Table {return $table->description('Budgets propres des équipes, exposants, juntas et sponsors. Jamais additionnés aux recettes, à la trésorerie ou au break-even QAPAS. Les apports en nature ne sont pas des ventes.')->columns([
  TextColumn::make('name')->label('Poste')->searchable()->wrap(),TextColumn::make('holder')->label('Titulaire'),TextColumn::make('kind')->label('Nature')->formatStateUsing(fn($state)=>$state==='cost'?'Coût':'Recette')->badge(),TextColumn::make('quantity')->label('Quantité')->placeholder('À préciser'),TextColumn::make('unit')->label('Unité'),TextColumn::make('unit_gross_cents')->label('PU TTC')->formatStateUsing(fn($state)=>Money::format((int)$state))->placeholder('À chiffrer'),TextColumn::make('total')->label('Total TTC prévu')->getStateUsing(fn(StandExternalLine $record)=>$record->total()===null?'À chiffrer':Money::format($record->total())),TextColumn::make('qapasBudgetLine.name')->label('Contrepartie QAPAS')->placeholder('Flux extérieur'),
  ])->filters([SelectFilter::make('scenario_id')->label('Scénario')->options(fn()=>$this->scenarios()->pluck('name','id'))->default(fn()=>$this->defaultScenario())->query(fn(Builder $query,array $data)=>$query->where('scenario_id',$data['value']??0)),SelectFilter::make('party')->label('Intervenant')->options(StandExternalLine::PARTIES),SelectFilter::make('kind')->label('Nature')->options(['cost'=>'Coûts','revenue'=>'Recettes'])])->groups([Group::make('holder')->label('Titulaire')])->defaultGroup('holder')->headerActions([CreateAction::make()->label('Ajouter un poste hors QAPAS')->mutateDataUsing(fn(array $data)=>$this->context($data))])->recordActions([EditAction::make()->mutateDataUsing(fn(array $data)=>$this->context($data))]);}
}
