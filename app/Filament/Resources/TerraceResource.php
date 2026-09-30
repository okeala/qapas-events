<?php
namespace App\Filament\Resources;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Forms\Components\{TextInput,Select,Toggle,Textarea};
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
class TerraceResource extends Resource {
 protected static ?string $model=\App\Models\Terrace::class;
 protected static ?string $modelLabel='Terrasse';
 protected static ?string $pluralModelLabel='Terrasses et accès';
 protected static string|\UnitEnum|null $navigationGroup='4 · Préparer';
 protected static ?int $navigationSort=20;
 public static function canCreate(): bool {return false;}
 public static function form(Schema $schema): Schema {return $schema->components([TextInput::make('name')->label('Nom')->required()->maxLength(120),Select::make('access')->label('Accès prévu')->options(['public'=>'Public','qualified'=>'Opérateurs qualifiés','restricted'=>'Accès organisation'])->required(),Toggle::make('is_public')->label('Visible sur le plan publié'),Textarea::make('notes')->label('Portance, bordures, accès, barrières · notes internes')->maxLength(5000)]);}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('name')->label('Terrasse')->searchable(),TextColumn::make('eventProject.name')->label('Édition'),TextColumn::make('access')->label('Accès')->badge()])->recordActions([EditAction::make()]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\TerraceResource\Pages\ManageRecords::route('/')];}
}
