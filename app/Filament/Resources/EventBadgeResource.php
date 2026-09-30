<?php
namespace App\Filament\Resources;
use App\Models\{EventBadge,WelcomePackPlan};
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\{Select,Textarea,DateTimePicker};
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\{EditAction,Action};
class EventBadgeResource extends Resource {
 protected static ?string $model=EventBadge::class;
 protected static ?string $modelLabel='Badge officiel';
 protected static ?string $pluralModelLabel='Badges acteurs · QR et révocation';
 protected static string|\UnitEnum|null $navigationGroup='4 · Préparer';
 protected static ?int $navigationSort=65;
 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([Select::make('event_project_id')->label('Édition')->relationship('eventProject','name')->required()->live()->disabledOn('edit'),Select::make('welcome_pack_plan_id')->label('Relevé privé')->options(fn(Get $get)=>WelcomePackPlan::where('event_project_id',$get('event_project_id'))->pluck('name','id'))->required()->live()->disabledOn('edit'),Select::make('person_key')->label('Personne · nom privé')->options(fn(Get $get)=>collect(WelcomePackPlan::find($get('welcome_pack_plan_id'))?->people??[])->mapWithKeys(fn($person)=>[$person['person_key']=>$person['name']]))->required()->searchable()->disabledOn('edit'),Select::make('role')->label('Fonction publique')->options(WelcomePackPlan::CATEGORIES)->required()->disabledOn('edit'),Select::make('status')->label('État')->options(['draft'=>'Brouillon','active'=>'Délivré','revoked'=>'Révoqué définitivement'])->default('draft')->required(),DateTimePicker::make('valid_from')->label('Valable à partir du')->timezone('Europe/Lisbon'),DateTimePicker::make('valid_until')->label('Valable jusqu’au')->timezone('Europe/Lisbon'),Textarea::make('evidence')->label('Preuve de délivrance et rôle vérifié · privé'),Textarea::make('revocation_reason')->label('Motif de révocation · privé')]);}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('serial')->label('Numéro')->searchable(),TextColumn::make('role')->label('Fonction')->formatStateUsing(fn($state)=>WelcomePackPlan::CATEGORIES[$state]??$state),TextColumn::make('status')->label('État')->badge(),TextColumn::make('valid_until')->label('Fin')->dateTime('d/m/Y H:i')])->recordActions([EditAction::make(),Action::make('print')->label('Badge à imprimer')->url(fn(EventBadge $record)=>route('badge.print',['badge'=>$record->public_id]))]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\EventBadgeResource\Pages\ManageRecords::route('/')];}
}
