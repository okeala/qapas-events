<?php
namespace App\Filament\Resources;
use App\Models\ActivityLocation;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\{TextInput,Textarea,Select,Toggle,Repeater,DatePicker,DateTimePicker};
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
class ActivityLocationResource extends Resource {
 protected static ?string $model=ActivityLocation::class;
 protected static ?string $modelLabel='Implantations des épreuves';
 protected static ?string $pluralModelLabel='Implantations des épreuves';
 protected static string|\UnitEnum|null $navigationGroup='1 · Concevoir';
 protected static ?int $navigationSort=35;
 
 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([
 Select::make('activity_id')->label('Épreuve')->relationship('activity','name')->required()->live()->disabledOn('edit'),Select::make('site_feature_id')->label('Quartel / zone')->relationship('siteFeature','name',fn($query,Get $get)=>$query->where('event_project_id',\App\Models\Activity::find($get('activity_id'))?->event_project_id))->required(),Select::make('role')->label('Rôle de cette zone')->options(['performance'=>'Performance','spectator'=>'Spectateurs','queue'=>'Attente','technical'=>'Technique'])->required()->default('performance')
 ]);}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('activity.name')->label('Épreuve'),TextColumn::make('siteFeature.name')->label('Quartel / zone'),TextColumn::make('role')->label('Rôle')])->recordActions([EditAction::make()->modalWidth('7xl')]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\ActivityLocationResource\Pages\ManageRecords::route('/')];}
}
