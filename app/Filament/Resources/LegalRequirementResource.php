<?php
namespace App\Filament\Resources;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Forms\Components\{TextInput,Textarea,Select,Toggle,DateTimePicker,DatePicker};
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
class LegalRequirementResource extends Resource {
 protected static ?string $model=\App\Models\LegalRequirement::class;
 protected static ?string $modelLabel='Conditions et preuves';
 protected static ?string $pluralModelLabel='Conditions et preuves';
 protected static string|\UnitEnum|null $navigationGroup='4 · Préparer';
 protected static ?int $navigationSort=30;

 public static function canCreate(): bool {return false;}
 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([TextInput::make('name')->label('Condition')->maxLength(255)->disabled(),TextInput::make('code')->label('Code')->maxLength(255)->disabled(),Select::make('status')->label('État de revue')->options(['pending'=>'À instruire','approved'=>'Validation documentée','not_applicable'=>'Non applicable, justifié'])->required(),Textarea::make('evidence')->label('Preuve et périmètre de la validation')->maxLength(10000)->rows(3)->columnSpanFull(),TextInput::make('reviewed_by')->label('Nom et qualité du relecteur')->maxLength(255),DateTimePicker::make('reviewed_at')->label('Date de revue')->maxDate(now()),DatePicker::make('expires_at')->label('Validité jusque')]);}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('name')->label('Nom')->searchable()->wrap(),TextColumn::make('eventProject.name')->label('Édition'),TextColumn::make('status')->label('État')->badge(),TextColumn::make('updated_at')->label('Mise à jour')->dateTime('d/m/Y H:i')->sortable()])->defaultSort('updated_at','desc')->recordActions([EditAction::make()]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\LegalRequirementResource\Pages\ManageRecords::route('/')];}
}
