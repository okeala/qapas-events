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
 TextInput::make('name')->label('Nom de l’emprise')->maxLength(120),Select::make('activity_id')->label('Épreuve')->relationship('activity','name')->required()->live()->disabledOn('edit'),Select::make('site_feature_id')->label('Quartel / zone')->relationship('siteFeature','name',fn($query,Get $get)=>$query->where('event_project_id',\App\Models\Activity::find($get('activity_id'))?->event_project_id))->required(),Select::make('role')->label('Rôle de cette zone')->options(['performance'=>'Performance','spectator'=>'Spectateurs','queue'=>'Attente','technical'=>'Technique'])->required()->default('performance'),Select::make('additional_quartel_ids')->label('Quartéis supplémentaires')->multiple()->options(fn(Get $get)=>\App\Models\SiteFeature::where('event_project_id',\App\Models\Activity::find($get('activity_id'))?->event_project_id)->where('category','quartel')->pluck('name','id')),Select::make('access')->label('Accès de l’emprise')->options(['public'=>'Public','restricted'=>'Restreint','staff'=>'Technique'])->required()->default('restricted'),Toggle::make('is_public')->label('Publier lorsque l’épreuve est révélée'),Textarea::make('coordination_notes')->label('Coordination : horaires, séparation, accès')->helperText('Les chevauchements ne sont pas automatiquement des conflits : confronter les créneaux dans le programme. Le contour se trace dans Plan du site.')->columnSpanFull()
 ]);}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('name')->label('Emprise'),TextColumn::make('overlap_warning')->label('Chevauchements à examiner')->state(fn($record)=>implode(' ; ',$record->overlaps())?:'Aucun')->wrap(),TextColumn::make('activity.name')->label('Épreuve'),TextColumn::make('siteFeature.name')->label('Quartel / zone'),TextColumn::make('role')->label('Rôle')])->recordActions([EditAction::make()->modalWidth('7xl')]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\ActivityLocationResource\Pages\ManageRecords::route('/')];}
}
