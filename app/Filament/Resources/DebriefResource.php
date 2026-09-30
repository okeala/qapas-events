<?php
namespace App\Filament\Resources;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Forms\Components\{TextInput,Textarea,Select,Toggle,DateTimePicker,DatePicker};
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
class DebriefResource extends Resource {
 protected static ?string $model=\App\Models\Debrief::class;
 protected static ?string $modelLabel='Bilans et prochaine édition';
 protected static ?string $pluralModelLabel='Bilans et prochaine édition';
 protected static string|\UnitEnum|null $navigationGroup='6 · Clôturer';

 protected static ?int $navigationSort=10;

 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([Select::make('event_project_id')->label('Édition')->relationship('eventProject','name')->preload()->searchable()->required()->disabledOn('edit'),TextInput::make('name')->label('Bilan')->required()->maxLength(120),TextInput::make('observed_visitors')->label('Visiteurs observés, si mesurés')->integer()->minValue(0)->maxValue(1000000),TextInput::make('count_method')->label('Méthode et limites du comptage')->maxLength(255),Textarea::make('deliveries')->label('Prestations livrées, écarts et litiges')->maxLength(10000)->columnSpanFull(),Textarea::make('financial_reconciliation')->label('Rapprochement, factures, cautions et remboursements à traiter')->maxLength(10000)->columnSpanFull(),Textarea::make('lessons')->label('Retours publics, équipes et commerçants')->maxLength(10000)->columnSpanFull(),Textarea::make('next_decision')->label('Ce que la prochaine édition doit changer')->maxLength(10000)->columnSpanFull()]);}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('name')->label('Nom')->searchable()->wrap(),TextColumn::make('eventProject.name')->label('Édition'),TextColumn::make('updated_at')->label('Mise à jour')->dateTime('d/m/Y H:i')->sortable()])->defaultSort('updated_at','desc')->recordActions([EditAction::make()]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\DebriefResource\Pages\ManageRecords::route('/')];}
}
