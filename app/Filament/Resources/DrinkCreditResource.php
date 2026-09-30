<?php
namespace App\Filament\Resources;
use App\Models\DrinkCredit;
use App\Domain\Registration\RegistrationDecision;
use App\Domain\Finance\Money;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\{TextInput,Hidden};
use Filament\Actions\Action;
class DrinkCreditResource extends Resource {
 protected static ?string $model=DrinkCredit::class;
 protected static ?string $modelLabel='Ticket boissons';
 protected static ?string $pluralModelLabel='Tickets boissons';
 protected static string|\UnitEnum|null $navigationGroup='5 · Exploiter';
 protected static ?int $navigationSort=15;
 public static function canCreate(): bool {return false;}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('public_id')->label('Code présenté au bar')->searchable()->copyable(),TextColumn::make('registration.interest.name')->label('Titulaire')->searchable(),TextColumn::make('registration.campaign.eventProject.name')->label('Édition'),TextColumn::make('face_cents')->label('Crédit émis')->formatStateUsing(fn($state)=>Money::format($state)),TextColumn::make('redeemed_cents')->label('Consommé')->formatStateUsing(fn($state)=>Money::format($state)),TextColumn::make('remaining')->label('Disponible')->state(fn($record)=>Money::format($record->availableCents()))])->recordActions([Action::make('redeem')->label('Décompter une consommation')->visible(fn($record)=>$record->availableCents()>0)->schema([Hidden::make('operation')->default(fn()=>(string)\Illuminate\Support\Str::uuid()),TextInput::make('amount')->label('Valeur de la consommation (centimes)')->integer()->minValue(1)->maxValue(1000)->required(),TextInput::make('description')->label('Boisson(s) servie(s) / référence')->maxLength(255)->required()])->action(fn($record,array $data)=>app(RegistrationDecision::class)->redeem($record,(int)$data['amount'],$data['description'],$data['operation']))]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\DrinkCreditResource\Pages\ManageRecords::route('/')];}
}
