<?php
namespace App\Filament\Resources;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Forms\Components\{TextInput,Textarea,Select,Toggle,DateTimePicker,DatePicker};
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
class InterestResource extends Resource {
 protected static ?string $model=\App\Models\Interest::class;
 protected static ?string $modelLabel='Demandes reçues';
 protected static ?string $pluralModelLabel='Demandes reçues';
 protected static string|\UnitEnum|null $navigationGroup='3 · Mobiliser';
 protected static ?int $navigationSort=20;

 public static function canCreate(): bool {return false;}
 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([TextInput::make('profile')->label('Profil')->disabled(),TextInput::make('name')->label('Nom')->maxLength(255)->disabled(),TextInput::make('email')->label('Email')->maxLength(255)->disabled(),TextInput::make('freguesia')->label('Freguesia')->maxLength(255)->disabled(),Select::make('expert_roles')->label('Rôles proposés')->multiple()->options(\App\Domain\Teams\ExpertRoles::labels())->disabled(),Textarea::make('message')->label('Demande')->maxLength(10000)->rows(3)->columnSpanFull()->disabled(),\Filament\Forms\Components\Placeholder::make('sponsorship')->label('Choix de parrainage reçu · sans réservation')->content(fn($record)=>$record?->sponsorship_request?json_encode($record->sponsorship_request,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT):'—')->columnSpanFull(),TextInput::make('expected_guests')->label('Public abrité souhaité')->disabled(),Toggle::make('larger_tent_requested')->label('Demande de tente plus grande')->disabled(),Toggle::make('extra_furniture_requested')->label('Demande d’aide pour louer le mobilier, à sa charge')->disabled(),Select::make('status')->label('Suivi')->options(['new'=>'À lire','qualified'=>'Qualifiée','contacted'=>'Contact établi','closed'=>'Clôturée'])->required()]);}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('name')->label('Nom')->searchable()->wrap(),TextColumn::make('eventProject.name')->label('Édition'),TextColumn::make('expert_roles')->label('Rôles proposés')->formatStateUsing(fn($state)=>\App\Domain\Teams\ExpertRoles::labels()[$state]??$state)->badge(),TextColumn::make('status')->label('État')->badge(),TextColumn::make('updated_at')->label('Mise à jour')->dateTime('d/m/Y H:i')->sortable()])->defaultSort('updated_at','desc')->recordActions([EditAction::make()]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\InterestResource\Pages\ManageRecords::route('/')];}
}
