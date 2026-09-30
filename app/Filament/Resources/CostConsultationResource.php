<?php
namespace App\Filament\Resources;
use App\Models\CostConsultation;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Forms\Components\{TextInput,Textarea,DatePicker,DateTimePicker,Placeholder};
use Filament\Tables\{Table,Grouping\Group};
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
class CostConsultationResource extends Resource {
 protected static ?string $model=CostConsultation::class;
 protected static ?string $modelLabel='Consultation fournisseur';
 protected static ?string $pluralModelLabel='Courriers et devis fournisseurs';
 protected static string|\UnitEnum|null $navigationGroup='2 · Chiffrer';
 protected static ?int $navigationSort=25;
 public static function canCreate(): bool {return false;}
 public static function requestAction(): Action {return Action::make('consultation')->label('Courrier / devis')->visible(fn($record)=>!($record instanceof \App\Models\BudgetLine)||$record->kind==='cost')->action(function($record,$livewire){abort_unless(auth('admin')->user()?->is_active,403);$c=app(\App\Domain\Procurement\Consultations::class)->ensure($record);$livewire->redirect(self::getUrl('edit',['record'=>$c]));});}
 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([
  Placeholder::make('need')->label('Poste source actuel')->content(fn(CostConsultation $record)=>$record->costable->name.' · '.(app(\App\Domain\Procurement\Consultations::class)->quantity($record->costable)??'quantité inconnue').' '.$record->costable->unit.' · Relire le courrier si les quantités ou dates ont changé.')->columnSpanFull(),
  TextInput::make('name')->label('Consultation')->required()->maxLength(255),DatePicker::make('reply_due_at')->label('Réponse souhaitée avant'),
  TextInput::make('subject_pt')->label('Objet PT')->required()->maxLength(255),TextInput::make('subject_fr')->label('Objet FR')->required()->maxLength(255),
  Textarea::make('body_pt')->label('Courrier PT · à personnaliser avant envoi')->required()->rows(14)->maxLength(30000),Textarea::make('body_fr')->label('Courrier FR · à personnaliser avant envoi')->required()->rows(14)->maxLength(30000),
  Textarea::make('local_search_notes')->label('Pistes locales, prêt, sponsor, bouche-à-oreille et relances')->maxLength(10000)->columnSpanFull(),DateTimePicker::make('sent_at')->label('Envoyé manuellement le')->maxDate(now())->helperText('L’application ne transmet aucun email. Cette date consigne un envoi effectué par vos soins.'),
 ]);}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('name')->label('Poste')->searchable()->wrap(),TextColumn::make('eventProject.name')->label('Édition'),TextColumn::make('quotes_count')->counts('quotes')->label('Offres'),TextColumn::make('sent_at')->label('Envoi consigné')->dateTime('d/m/Y')->placeholder('Brouillon'),TextColumn::make('reply_due_at')->label('Échéance')->date('d/m/Y')])->groups([Group::make('eventProject.name')->label('Édition')->collapsible()])->recordActions([Action::make('edit')->label('Courriers et offres')->url(fn($record)=>self::getUrl('edit',['record'=>$record]))]);}
 public static function getRelations(): array {return [\App\Filament\RelationManagers\SupplierQuotesRelationManager::class];}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\CostConsultationResource\Pages\ListRecords::route('/'),'edit'=>\App\Filament\Resources\CostConsultationResource\Pages\EditRecord::route('/{record}/edit')];}
}
