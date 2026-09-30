<?php
namespace App\Filament\Resources;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\{TextInput,Textarea,Select,Toggle,DateTimePicker,Placeholder};
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\{Action,EditAction};
class TicketSettlementResource extends Resource {
 protected static ?string $model=\App\Models\TicketSettlement::class;protected static ?string $modelLabel='Reversement du relais';protected static ?string $pluralModelLabel='Reversements espèces à QAPAS';protected static string|\UnitEnum|null $navigationGroup='3 · Mobiliser';protected static ?int $navigationSort=44;
 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([Select::make('distributor_id')->label('Distributeur')->relationship('distributor','name')->live()->afterStateUpdated(fn($set)=>$set('ticket_ids',[]))->required(),Select::make('ticket_ids')->label('Reçus à rapprocher')->multiple()->options(fn(Get $get)=>\App\Models\EventTicket::where('distributor_id',$get('distributor_id'))->where('is_live',(bool)config('registration.live'))->where('channel','cash')->where('status','pending')->get()->mapWithKeys(fn($t)=>[$t->id=>$t->buyer_name.' · '.$t->public_id.' · à reverser '.\App\Domain\Finance\Money::format($t->expectedRemittance())])->all())->required(),TextInput::make('received_cents')->label('Montant réellement reçu par QAPAS, centimes')->integer()->minValue(1)->required()->helperText('10 € brut − 1 € conservé par le relais = 9 € à recevoir. Ni promesse ni dépôt chez le relais.'),TextInput::make('reference')->label('Référence unique du versement')->required()->maxLength(200),DateTimePicker::make('received_at')->label('Date réelle de réception')->timezone('Europe/Lisbon')->maxDate(now())->required(),Textarea::make('evidence')->label('Caisse/comptage contresigné ou relevé bancaire')->required()->columnSpanFull()]);}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('reference')->searchable(),TextColumn::make('distributor.name'),TextColumn::make('received_cents')->label('Reçu QAPAS')->formatStateUsing(fn($state)=>\App\Domain\Finance\Money::format($state)),TextColumn::make('received_at')->dateTime('d/m/Y H:i'),TextColumn::make('is_live')->label('Mode')->formatStateUsing(fn($state)=>$state?'RÉEL':'TEST')]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\TicketSettlementResource\Pages\ManageRecords::route('/')];}
}
