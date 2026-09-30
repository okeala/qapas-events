<?php
namespace App\Filament\RelationManagers;
use Filament\Schemas\Schema;
use Filament\Forms\Components\{TextInput,Textarea,Select};
use Filament\Tables\{Table,Grouping\Group};
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\{CreateAction,EditAction};
class CupAllocationsRelationManager extends \Filament\Resources\RelationManagers\RelationManager {
 protected static string $relationship='allocations';protected static ?string $title='Stocks confiés aux stands';
 public function form(Schema $schema): Schema {return $schema->columns(2)->components([
  Select::make('stand_id')->label('Stand')->options(fn()=>$this->getOwnerRecord()->scenario->includedStands()->pluck('name','stands.id'))->required()->disabledOn('edit'),TextInput::make('planned_quantity')->label('Allocation prévue')->integer()->minValue(0)->maxValue(1000000)->required()->default(0),TextInput::make('issued_quantity')->label('Remis physiquement au stand')->integer()->minValue(0)->required()->default(0),TextInput::make('returned_quantity')->label('Rendu physiquement à QAPAS')->integer()->minValue(0)->required()->default(0),TextInput::make('responsible')->label('Responsable du stock')->maxLength(255),Textarea::make('handover_evidence')->label('Bon signé de remise / retour'),Textarea::make('stock_notes')->label('Inventaire, écarts, casse, nettoyage, retours et règlement distinct')->columnSpanFull(),
 ]);}
 public function table(Table $table): Table {return $table->columns([TextColumn::make('stand.name')->label('Stand'),TextColumn::make('stand.kind')->label('Catégorie'),TextColumn::make('planned_quantity')->label('Prévu'),TextColumn::make('issued_quantity')->label('Remis'),TextColumn::make('returned_quantity')->label('Rendu'),TextColumn::make('responsible')->label('Responsable')])->groups([Group::make('stand.kind')->label('Catégorie')->collapsible()])->defaultGroup('stand.kind')->headerActions([CreateAction::make()])->recordActions([EditAction::make()]);}
}
