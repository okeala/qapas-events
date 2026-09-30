<?php
namespace App\Filament\Resources;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\{Select,TextInput,Textarea,Toggle,DateTimePicker,Placeholder};
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\{EditAction,Action};
class EditorialPostResource extends Resource {
 protected static ?string $model=\App\Models\EditorialPost::class;
 protected static ?string $modelLabel='Blog et making-of';
 protected static ?string $pluralModelLabel='Blog et making-of';
 protected static string|\UnitEnum|null $navigationGroup='3 · Mobiliser';
 protected static ?int $navigationSort=54;
 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([Select::make('event_project_id')->label('Édition')->relationship('eventProject','name')->required()->live()->disabledOn('edit'),TextInput::make('name')->label('Nom')->required()->maxLength(255),
 TextInput::make('title_pt')->label('Titre PT'),Textarea::make('body_fr')->label('Article FR')->rows(8)->columnSpanFull(),Textarea::make('body_pt')->label('Artigo PT')->rows(8)->columnSpanFull(),TextInput::make('youtube_id')->label('Identifiant YouTube, après publication sur la chaîne officielle')->maxLength(11)->helperText('Lien vidéo sans lecture ni traceur intégrés avant clic. Le téléversement est réalisé dans YouTube Studio.'),Textarea::make('rights_evidence')->label('Droits images, musique, personnes et sponsor ; aucune captation privée révélée'),Select::make('status')->label('État')->options(['draft'=>'Brouillon','published'=>'Publié','archived'=>'Archivé'])->default('draft')->required(),DateTimePicker::make('published_at')->label('Publication réelle')->timezone('Europe/Lisbon'),
]);}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('name')->label('Nom')->searchable()->wrap(),TextColumn::make('status')->label('Publication')->badge(),TextColumn::make('published_at')->label('Publié le')->dateTime('d/m/Y'),TextColumn::make('updated_at')->label('Mise à jour')->dateTime('d/m/Y H:i')->sortable()])->defaultSort('updated_at','desc')->recordActions([EditAction::make()->modalWidth('7xl')]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\EditorialPostResource\Pages\ManageRecords::route('/')];}
}
