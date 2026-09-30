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
class TicketMessageResource extends Resource {
 protected static ?string $model=\App\Models\TicketMessage::class;protected static ?string $modelLabel='Confirmation SMS';protected static ?string $pluralModelLabel='SMS · file et livraison';protected static string|\UnitEnum|null $navigationGroup='3 · Mobiliser';protected static ?int $navigationSort=46;
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('ticket.public_id')->label('Billet')->searchable(),TextColumn::make('ticket.buyer_name')->label('Participant'),TextColumn::make('status')->label('État prestataire')->badge(),TextColumn::make('provider_id')->label('Référence Twilio'),TextColumn::make('error_code')->label('Code / incident'),TextColumn::make('attempted_at')->dateTime('d/m/Y H:i'),TextColumn::make('delivered_at')->dateTime('d/m/Y H:i')])->filters([SelectFilter::make('status')->options(['queued'=>'En file','sending'=>'En cours','accepted'=>'Accepté · livraison à vérifier','delivered'=>'Livré','failed'=>'Échec confirmé','unknown'=>'Résultat inconnu · vérifier chez Twilio','cancelled'=>'Annulé'])])->recordActions([Action::make('retry')->label('Réessayer après échec confirmé')->requiresConfirmation()->visible(fn($record)=>$record->status==='failed'&&$record->ticket->valid())->action(fn($record)=>$record->update(['status'=>'queued','error_code'=>null]))]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\TicketMessageResource\Pages\ManageRecords::route('/')];}
}
