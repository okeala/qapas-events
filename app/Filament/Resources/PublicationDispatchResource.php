<?php
namespace App\Filament\Resources;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Forms\Components\{TextInput,Textarea};
class PublicationDispatchResource extends Resource {
 protected static ?string $model=\App\Models\PublicationDispatch::class;protected static ?string $modelLabel='Diffusion';protected static ?string $pluralModelLabel='Diffusions · site, presse, Facebook, X';protected static string|\UnitEnum|null $navigationGroup='3 · Mobiliser';protected static ?int $navigationSort=51;
 public static function canCreate(): bool {return false;}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('pressRelease.name')->label('Communiqué')->searchable(),TextColumn::make('version'),TextColumn::make('channel')->label('Canal')->badge(),TextColumn::make('destination')->label('Destinataire'),TextColumn::make('status')->badge(),TextColumn::make('evidence')->label('État / preuve')->wrap(),TextColumn::make('sent_at')->dateTime('d/m/Y H:i')->timezone('Europe/Lisbon')])->groups([\Filament\Tables\Grouping\Group::make('pressRelease.name')->label('Phase / communiqué')])->defaultGroup('pressRelease.name')->recordActions([Action::make('payload')->label('Texte préparé')->modalContent(fn($record)=>view('filament.publication-payload',['dispatch'=>$record]))->modalSubmitAction(false),Action::make('manual')->label('Consigner une diffusion vérifiée')->schema([TextInput::make('reference')->label('URL publique / référence du message')->required(),Textarea::make('evidence')->label('Compte officiel, date, preuve et absence de doublon')->required()])->action(fn($record,array $data)=>app(\App\Domain\Promotion\PromotionPipeline::class)->manual($record,$data['reference'],$data['evidence']))]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\PublicationDispatchResource\Pages\ManageRecords::route('/')];}
}
