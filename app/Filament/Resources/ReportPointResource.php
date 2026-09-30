<?php
namespace App\Filament\Resources;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\{Select,TextInput,Textarea,Toggle,DateTimePicker,Placeholder};
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\{EditAction,Action};
class ReportPointResource extends Resource {
 protected static ?string $model=\App\Models\ReportPoint::class;
 protected static ?string $modelLabel='QR · stands et lieux-clés';
 protected static ?string $pluralModelLabel='QR · stands et lieux-clés';
 protected static string|\UnitEnum|null $navigationGroup='5 · Exploiter';
 protected static ?int $navigationSort=15;
 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([Select::make('event_project_id')->label('Édition')->relationship('eventProject','name')->required()->live()->disabledOn('edit'),TextInput::make('name')->label('Nom')->required()->maxLength(255),
 Select::make('stand_id')->label('Stand OU lieu ci-dessous')->options(fn(Get $get)=>\App\Models\Stand::where('event_project_id',$get('event_project_id'))->pluck('name','id'))->disabledOn('edit'),Select::make('site_feature_id')->label('Lieu-clé')->options(fn(Get $get)=>\App\Models\SiteFeature::where('event_project_id',$get('event_project_id'))->pluck('name','id'))->disabledOn('edit'),Textarea::make('placement_evidence')->label('QR posé, libellé public vérifié et permanence désignée')->helperText('Le formulaire ne révèle ni coordonnées privées ni épreuves secrètes. Le QR ne remplace pas le 112 ou le signalement direct au personnel.'),Toggle::make('is_active')->label('Activer les signalements sur ce QR'),
]);}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('name')->label('Nom')->searchable()->wrap(),TextColumn::make('stand.name')->label('Stand'),TextColumn::make('feature.name')->label('Lieu'),TextColumn::make('is_active')->label('Signalements')->formatStateUsing(fn($state)=>$state?'Actifs':'À vérifier'),TextColumn::make('updated_at')->label('Mise à jour')->dateTime('d/m/Y H:i')->sortable()])->defaultSort('updated_at','desc')->recordActions([EditAction::make()->modalWidth('7xl'),Action::make('qr')->label('Plaque QR')->url(fn($record)=>route('incident.sheet',['point'=>$record->public_id]))]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\ReportPointResource\Pages\ManageRecords::route('/')];}
}
