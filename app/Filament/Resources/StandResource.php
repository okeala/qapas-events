<?php
namespace App\Filament\Resources;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Forms\Components\{TextInput,Textarea,Select,Toggle,DateTimePicker,DatePicker};
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
class StandResource extends Resource {
 protected static ?string $model=\App\Models\Stand::class;
 protected static ?string $modelLabel='Stands et emplacements';
 protected static ?string $pluralModelLabel='Stands et emplacements';
 protected static string|\UnitEnum|null $navigationGroup='4 · Préparer';

 protected static ?int $navigationSort=50;

 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([Select::make('event_project_id')->label('Édition')->relationship('eventProject','name')->preload()->searchable()->required()->disabledOn('edit'),TextInput::make('name')->label('Stand')->required()->maxLength(120),TextInput::make('freguesia')->label('Freguesia (vide tant que non identifiée)')->maxLength(120),Textarea::make('specialty')->label('Spécialité du village et produits proposés')->maxLength(5000),Textarea::make('sales_staff')->label('Équipe de vente pendant les épreuves')->maxLength(3000),Toggle::make('direct_costs_complete')->label('Coûts directs de l’offre QAPAS recensés'),Select::make('site_feature_id')->label('Objet sur le plan géographique')->relationship('siteFeature','name'),Select::make('kind')->label('Type')->options(['village'=>'Équipe de freguesia','independent'=>'Exposant indépendant'])->required(),TextInput::make('operator')->label('Exploitant / cocontractant à vérifier')->maxLength(255),TextInput::make('zone')->label('Zone proposée')->maxLength(120),Select::make('structure')->label('Structure')->options(['bare'=>'Emplacement nu','provided'=>'Structure fournie'])->required(),Select::make('status')->label('Préparation')->options(['proposed'=>'Proposé','reviewing'=>'Dossier en revue','planned'=>'Implantation prévue','withdrawn'=>'Retiré'])->required(),Textarea::make('needs')->label('Besoins, accès, électricité, activités, coexposants')->maxLength(5000)->columnSpanFull(),Textarea::make('material_support')->label('Aide affectée et justificatifs · hors recettes libres')->maxLength(5000)->columnSpanFull()]);}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('name')->label('Nom')->searchable()->wrap(),TextColumn::make('eventProject.name')->label('Édition'),TextColumn::make('status')->label('État')->badge(),TextColumn::make('updated_at')->label('Mise à jour')->dateTime('d/m/Y H:i')->sortable()])->defaultSort('updated_at','desc')->recordActions([EditAction::make()]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\StandResource\Pages\ManageRecords::route('/')];}
}
