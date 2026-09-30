<?php
namespace App\Filament\Resources;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
class ActivityMaterialResource extends Resource {
 protected static ?string $model=\App\Models\ActivityMaterial::class;
 protected static ?string $modelLabel='Besoin matériel';
 protected static ?string $pluralModelLabel='Matériel des épreuves';
 protected static string|\UnitEnum|null $navigationGroup='2 · Chiffrer';
 protected static ?int $navigationSort=30;
 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([Select::make('activity_id')->label('Épreuve')->relationship('activity','name')->required()->searchable()->preload()->disabledOn('edit')->live(),Select::make('activity_location_id')->label('Implantation concernée (facultatif)')->options(fn(\Filament\Schemas\Components\Utilities\Get $get)=>\App\Models\ActivityLocation::where('activity_id',$get('activity_id'))->get()->mapWithKeys(fn($l)=>[$l->id=>$l->name?:$l->siteFeature->name.' · '.$l->role]))->helperText('Ce besoin reste compté une seule fois dans le coût de l’épreuve.'),...ActivityResource::materialFields()]);}
 public static function table(Table $table): Table {return $table->groups([\Filament\Tables\Grouping\Group::make('activity.name')->label('Épreuve')->collapsible()])->defaultGroup('activity.name')->filters([\Filament\Tables\Filters\SelectFilter::make('activity_id')->label('Épreuve')->relationship('activity','name'),\Filament\Tables\Filters\SelectFilter::make('procurement')->label('Approvisionnement')->options(['purchase'=>'Achat','rental'=>'Location','loan'=>'Prêt','sponsor'=>'Apport sponsor']),\Filament\Tables\Filters\Filter::make('unpriced')->label('À chiffrer')->query(fn(\Illuminate\Database\Eloquent\Builder $query)=>$query->whereNull('unit_gross_cents')),\Filament\Tables\Filters\SelectFilter::make('edition')->label('Édition')->options(fn()=>\App\Models\EventProject::pluck('name','id'))->query(fn(\Illuminate\Database\Eloquent\Builder $query,array $data)=>$query->when($data['value']??null,fn($q,$id)=>$q->whereHas('activity',fn($a)=>$a->where('event_project_id',$id))))])->columns([TextColumn::make('name')->label('Besoin')->searchable()->wrap(),TextColumn::make('activity.name')->label('Épreuve')->searchable(),TextColumn::make('quantity')->label('Quantité')->placeholder('À préciser'),TextColumn::make('unit')->label('Unité'),TextColumn::make('basis')->label('Base'),TextColumn::make('unit_gross_cents')->label('Prix unitaire TTC')->formatStateUsing(fn($state)=>\App\Domain\Finance\Money::format((int)$state))->placeholder('À chiffrer')])->recordActions([\App\Filament\Resources\CostConsultationResource::requestAction(),EditAction::make()]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\ActivityMaterialResource\Pages\ManageRecords::route('/')];}
}
