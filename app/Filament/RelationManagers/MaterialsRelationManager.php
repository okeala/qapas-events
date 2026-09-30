<?php
namespace App\Filament\RelationManagers;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\{CreateAction,EditAction};
use App\Domain\Finance\{Pricing,Money};
use App\Models\ActivityMaterial;
class MaterialsRelationManager extends \Filament\Resources\RelationManagers\RelationManager {
 protected static string $relationship='materials';
 protected static ?string $title='Matériel et prestations';
 public function form(Schema $schema): Schema {return $schema->columns(2)->components(\App\Filament\Resources\ActivityResource::materialFields());}
 public function table(Table $table): Table {return $table->recordTitleAttribute('name')->columns([
  TextColumn::make('name')->label('Poste')->wrap()->searchable(),TextColumn::make('quantity')->label('Quantité')->placeholder('Inconnue'),TextColumn::make('unit')->label('Unité'),TextColumn::make('basis')->label('Base')->formatStateUsing(fn($state)=>$state==='per_run'?'Par passage':'Toute l’épreuve'),
  TextColumn::make('planned_total')->label('Quantité totale')->getStateUsing(fn(ActivityMaterial $record)=>$record->quantity===null?'Inconnue':$record->quantity*($record->basis==='per_run'?$this->getOwnerRecord()->planned_runs:1)),
  TextColumn::make('unit_gross_cents')->label('PU TTC')->formatStateUsing(fn($state)=>Money::format((int)$state))->placeholder('À chiffrer'),
  TextColumn::make('cost')->label('Coût retenu')->getStateUsing(fn(ActivityMaterial $record)=>filled($record->shared_cost_key)?'Budget commun : '.$record->shared_cost_key:($record->quantity===null?'À chiffrer':(($value=Pricing::forecast($record,$record->quantity*($record->basis==='per_run'?$this->getOwnerRecord()->planned_runs:1)))===null?'À chiffrer':Money::format($value)))),
  TextColumn::make('pricing_status')->label('Fiabilité')->formatStateUsing(fn($state)=>Pricing::STATUSES[$state]??$state)->badge(),
  TextColumn::make('price_source')->label('Source / limites')->wrap()->toggleable(isToggledHiddenByDefault:true),
  ])->headerActions([CreateAction::make()])->recordActions([EditAction::make()])->defaultSort('id');}
}
