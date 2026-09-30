<?php
namespace App\Filament\Resources;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\{Select,TextInput,Textarea,Toggle,DateTimePicker,Placeholder};
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\{EditAction,Action};
class RallyStopResource extends Resource {
 protected static ?string $model=\App\Models\RallyStop::class;
 protected static ?string $modelLabel='Rallye enfants · étapes culturelles';
 protected static ?string $pluralModelLabel='Rallye enfants · étapes culturelles';
 protected static string|\UnitEnum|null $navigationGroup='4 · Préparer';
 protected static ?int $navigationSort=75;
 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([Select::make('event_project_id')->label('Édition')->relationship('eventProject','name')->required()->live()->disabledOn('edit'),TextInput::make('name')->label('Nom')->required()->maxLength(255),Select::make('stand_id')->label('Stand de freguesia')->options(fn(Get $get)=>\App\Models\Stand::where('event_project_id',$get('event_project_id'))->where('kind','village')->pluck('name','id'))->required()->disabledOn('edit'),
 Select::make('site_feature_id')->label('Point du parcours sur la quinta')->options(fn(Get $get)=>\App\Models\SiteFeature::where('event_project_id',$get('event_project_id'))->pluck('name','id')),TextInput::make('sort_order')->label('Ordre du parcours')->integer()->default(100)->required(),Textarea::make('story_fr')->label('Bienvenue, culture et petite histoire FR')->rows(4),Textarea::make('story_pt')->label('Boas-vindas, cultura e história PT')->rows(4),Textarea::make('nature_fr')->label('Arbre, plante ou animal à découvrir FR'),Textarea::make('nature_pt')->label('Árvore, planta ou animal PT'),Textarea::make('question_fr')->label('Devinette / observation FR'),Textarea::make('question_pt')->label('Enigma / observação PT'),Textarea::make('answer')->label('Réponse réservée aux animateurs'),Textarea::make('review_evidence')->label('Validation culturelle par la freguesia, espèce réelle et sources'),Textarea::make('safety_evidence')->label('Cheminement familial, accompagnement adulte et séparation des engins'),Toggle::make('is_public')->label('Publier cette étape validée'),
]);}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('name')->label('Nom')->searchable()->wrap(),TextColumn::make('stand.freguesia')->label('Freguesia'),TextColumn::make('sort_order')->label('Ordre')->sortable(),TextColumn::make('is_public')->label('Publication')->formatStateUsing(fn($state)=>$state?'Publique':'Brouillon'),TextColumn::make('updated_at')->label('Mise à jour')->dateTime('d/m/Y H:i')->sortable()])->defaultSort('updated_at','desc')->recordActions([EditAction::make()->modalWidth('7xl')]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\RallyStopResource\Pages\ManageRecords::route('/')];}
}
