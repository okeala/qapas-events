<?php
namespace App\Filament\RelationManagers;
use App\Domain\Finance\{Money,Pricing};
use App\Filament\Resources\BudgetLineResource;
use App\Models\{Scenario,Stand,BudgetLine};
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\{Select,TextInput};
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\{CreateAction,EditAction};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
abstract class BudgetRelationManager extends RelationManager {
 protected static string $relationship='budgetLines';
 protected static string $nature='cost';
 public function scenarios(): Builder {
  $owner=$this->getOwnerRecord();
  return Scenario::where('event_project_id',$owner->event_project_id)->when($owner instanceof Stand,fn($q)=>$q->whereHas('includedStands',fn($q)=>$q->where('stands.id',$owner->id)))->when($owner instanceof Scenario,fn($q)=>$q->whereKey($owner->id));
 }
 private function defaultScenario(): ?int {return $this->scenarios()->orderByRaw("CASE WHEN template_key = 'costing-two-days-v1' THEN 0 ELSE 1 END")->orderByDesc('id')->value('id');}
 public function form(Schema $schema): Schema {
  $stand=$this->getOwnerRecord() instanceof Stand;
  return $schema->columns(2)->components([
   Select::make('scenario_id')->label('Scénario de cette unité')->options(fn()=>$this->scenarios()->pluck('name','id'))->default(fn()=>$this->getTableFilterState('scenario_id')['value']??$this->defaultScenario())->required()->live()->disabledOn('edit')->visible($stand),
   TextInput::make('name')->label('Poste')->required()->maxLength(255),
   Select::make('scope')->label('Périmètre')->options(['common'=>'Commun','stand'=>'Unité stand','bar'=>'Boissons','soup'=>'Soupe','structural'=>'Partenariat général'])->required()->default('common')->live()->visible(!$stand),
   Select::make('stand_id')->label('Stand du scénario')->options(fn()=>$this->getOwnerRecord() instanceof Scenario?$this->getOwnerRecord()->includedStands()->pluck('name','stands.id'):[])->visible(fn(Get $get)=>!$stand&&$get('scope')==='stand')->required(fn(Get $get)=>!$stand&&$get('scope')==='stand')->live(),
   Select::make('stand_partner_id')->label('Parrain / relais payeur')->options(fn(Get $get)=>\App\Models\StandPartner::where('stand_id',$stand?$this->getOwnerRecord()->id:$get('stand_id'))->pluck('name','id'))->nullable(),
   ...BudgetLineResource::financialFields(),
  ]);
 }
 public function contextualData(array $data,?BudgetLine $record=null): array {
  abort_unless(auth('admin')->user()?->is_active,403);
  $owner=$this->getOwnerRecord();$data['kind']=static::$nature;
  if($owner instanceof Stand){$data['stand_id']=$owner->id;$data['scope']='stand';$data['scenario_id']=$record?->scenario_id??($data['scenario_id']??null);}
  else{$data['scenario_id']=$owner->id;if(($data['scope']??'common')!=='stand'){$data['stand_id']=null;$data['stand_partner_id']=null;}}
  if(!$this->scenarios()->whereKey($data['scenario_id'])->exists())throw ValidationException::withMessages(['scenario_id'=>'Choisir un scénario incluant cette unité.']);
  if($data['stand_id']??null){$scenario=Scenario::findOrFail($data['scenario_id']);if(!$scenario->includedStands()->where('stands.id',$data['stand_id'])->exists())throw ValidationException::withMessages(['stand_id'=>'Ce stand doit être inclus dans le scénario.']);}
  return $data;
 }
 public function table(Table $table): Table {
  $stand=$this->getOwnerRecord() instanceof Stand;
  return $table->recordTitleAttribute('name')->modifyQueryUsing(fn(Builder $query)=>$query->where('kind',static::$nature))
   ->columns([
    TextColumn::make('name')->label('Poste')->searchable()->wrap(),
    TextColumn::make('scenario.name')->label('Scénario')->visible($stand),TextColumn::make('stand.name')->label('Unité')->placeholder('Commun / service')->visible(!$stand),
    TextColumn::make('scope')->label('Service')->visible(!$stand),
    TextColumn::make('forecast_quantity')->label('Prévu')->numeric(),TextColumn::make('unit')->label('Unité facturée'),
    TextColumn::make('unit_gross_cents')->label('PU TTC')->formatStateUsing(fn($state)=>Money::format((int)$state))->placeholder('À chiffrer'),
    TextColumn::make('total_gross')->label('Total TTC')->getStateUsing(fn(BudgetLine $record)=>$record->unit_gross_cents===null?'À chiffrer':Money::format($record->unit_gross_cents*$record->forecast_quantity)),
    TextColumn::make('economic')->label(static::$nature==='cost'?'Coût retenu':'Recette hors IVA')->getStateUsing(fn(BudgetLine $record)=>($value=Pricing::forecast($record,$record->forecast_quantity))===null?'À chiffrer':Money::format($value)),
    TextColumn::make('pricing_status')->label('Fiabilité')->formatStateUsing(fn($state)=>Pricing::STATUSES[$state]??$state)->badge(),
    TextColumn::make('committed_quantity')->label('Engagé')->numeric(),TextColumn::make('paid_quantity')->label('Payé')->numeric(),
    TextColumn::make('price_source')->label('Source / limites')->wrap()->toggleable(isToggledHiddenByDefault:true),
   ])->filters($stand?[SelectFilter::make('scenario_id')->label('Scénario — un seul à la fois')->options(fn()=>$this->scenarios()->pluck('name','id'))->default(fn()=>$this->defaultScenario())->query(fn(Builder $query,array $data)=>$query->where('scenario_id',$data['value']??0))]:[])
   ->headerActions([CreateAction::make()->label(static::$nature==='cost'?'Ajouter un coût':'Ajouter une recette')->mutateDataUsing(fn(array $data)=>$this->contextualData($data))])
   ->recordActions([EditAction::make()->mutateDataUsing(fn(array $data,BudgetLine $record)=>$this->contextualData($data,$record))])->defaultSort('id');
 }
}
