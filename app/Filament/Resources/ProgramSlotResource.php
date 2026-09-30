<?php
namespace App\Filament\Resources;
use App\Models\ProgramSlot;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\{TextInput,Textarea,Select,Toggle,Repeater,DatePicker,DateTimePicker};
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
class ProgramSlotResource extends Resource {
 protected static ?string $model=ProgramSlot::class;
 protected static ?string $modelLabel='Programme par journée';
 protected static ?string $pluralModelLabel='Programme par journée';
 protected static string|\UnitEnum|null $navigationGroup='4 · Préparer';
 protected static ?int $navigationSort=60;
 
 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([
 Select::make('scenario_id')->label('Scénario')->relationship('scenario','name')->required()->live()->disabledOn('edit'),Select::make('activity_id')->label('Épreuve officielle incluse au budget')->options(fn(Get $get)=>\App\Models\Scenario::find($get('scenario_id'))?->includedActivities()->where('track','official')->pluck('name','activities.id')->all()??[])->required(),TextInput::make('day_number')->label('Jour de l’événement')->integer()->minValue(1)->maxValue(31)->required(),TextInput::make('time_label')->label('Horaire / plage envisagée')->maxLength(120),Textarea::make('notes')->label('Rotation des équipes et essais publics séparés')->maxLength(5000)->columnSpanFull()
 ]);}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('scenario.name')->label('Scénario'),TextColumn::make('day_number')->label('Jour'),TextColumn::make('activity.name')->label('Épreuve'),TextColumn::make('time_label')->label('Horaire')])->recordActions([EditAction::make()->modalWidth('7xl')]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\ProgramSlotResource\Pages\ManageRecords::route('/')];}
}
