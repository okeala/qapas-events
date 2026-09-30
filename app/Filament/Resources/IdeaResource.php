<?php
namespace App\Filament\Resources;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Forms\Components\{TextInput,Textarea,Select,Toggle,DateTimePicker,DatePicker};
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
class IdeaResource extends Resource {
 protected static ?string $model=\App\Models\Idea::class;
 protected static ?string $modelLabel='Parcours de lancement';
 protected static ?string $pluralModelLabel='Parcours de lancement';
 protected static string|\UnitEnum|null $navigationGroup='1 · Concevoir';

 protected static ?int $navigationSort=20;

 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([Select::make('event_project_id')->label('Édition')->relationship('eventProject','name')->live()->searchable()->preload()->required()->disabledOn('edit'),TextInput::make('name')->label('Hypothèse')->maxLength(255)->required(),Select::make('pillar')->label('4P')->options(['product'=>'Produit','price'=>'Prix','place'=>'Distribution','promotion'=>'Promotion'])->required(),Textarea::make('hypothesis')->label('Ce que nous pensons')->maxLength(10000)->rows(3)->columnSpanFull()->required(),Textarea::make('experiment')->label('Comment le vérifier')->maxLength(10000)->rows(3)->columnSpanFull(),Textarea::make('evidence')->label('Ce que nous avons observé')->maxLength(10000)->rows(3)->columnSpanFull(),TextInput::make('owner')->label('Responsable')->maxLength(255),DatePicker::make('due_at')->label('Échéance'),TextInput::make('sort_order')->label('Ordre')->integer()->minValue(1)->default(100),Select::make('depends_on')->label('Étapes préalables')->multiple()->options(fn(Get $get)=>\App\Models\Idea::where('event_project_id',$get('event_project_id'))->pluck('name','id')->all()),Toggle::make('is_public')->label('Afficher ce jalon au public'),TextInput::make('public_label')->label('Intitulé public, sans notes internes')->maxLength(255),Select::make('status')->label('Décision')->options(['idea'=>'À explorer','testing'=>'Test en cours','validated'=>'Étape validée avec preuve','rejected'=>'Écarter'])->required()]);}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('name')->label('Nom')->searchable()->wrap(),TextColumn::make('eventProject.name')->label('Édition'),TextColumn::make('status')->label('État')->badge(),TextColumn::make('updated_at')->label('Mise à jour')->dateTime('d/m/Y H:i')->sortable()])->defaultSort('updated_at','desc')->recordActions([EditAction::make()]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\IdeaResource\Pages\ManageRecords::route('/')];}
}
