<?php
namespace App\Filament\Resources;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\{Select,TextInput,Textarea,Toggle,DateTimePicker,Placeholder};
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\{EditAction,Action};
class FreguesiaAgreementResource extends Resource {
 protected static ?string $model=\App\Models\FreguesiaAgreement::class;
 protected static ?string $modelLabel='Conventions des freguesias';
 protected static ?string $pluralModelLabel='Conventions des freguesias';
 protected static string|\UnitEnum|null $navigationGroup='3 · Mobiliser';
 protected static ?int $navigationSort=32;
 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([Select::make('event_project_id')->label('Édition')->relationship('eventProject','name')->required()->live()->disabledOn('edit'),TextInput::make('name')->label('Nom')->required()->maxLength(255),Select::make('stand_id')->label('Stand de freguesia')->options(fn(Get $get)=>\App\Models\Stand::where('event_project_id',$get('event_project_id'))->where('kind','village')->pluck('name','id'))->required()->disabledOn('edit'),
 TextInput::make('terms_version')->label('Version')->required(),Select::make('status')->label('État')->options(['draft'=>'Projet','review'=>'Relecture','signed'=>'Signée','withdrawn'=>'Retirée'])->default('draft')->required(),TextInput::make('legal_entity')->label('Personne morale cocontractante'),TextInput::make('signatory')->label('Signataire habilité'),Textarea::make('authority_evidence')->label('Délibération et pouvoirs du signataire'),Textarea::make('legal_review')->label('Relecture juridique : périmètre, durée, commande publique, annulation'),Textarea::make('terms_fr')->label('Convention FR')->rows(10)->required()->columnSpanFull(),Textarea::make('terms_pt')->label('Convenção PT')->rows(10)->required()->columnSpanFull(),DateTimePicker::make('signed_at')->label('Signature effective')->timezone('Europe/Lisbon'),Textarea::make('agreement_evidence')->label('Référence de la convention signée et de ses annexes'),
]);}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('name')->label('Nom')->searchable()->wrap(),TextColumn::make('stand.freguesia')->label('Freguesia')->searchable(),TextColumn::make('status')->label('État')->badge(),TextColumn::make('signed_at')->label('Signature')->dateTime('d/m/Y'),TextColumn::make('updated_at')->label('Mise à jour')->dateTime('d/m/Y H:i')->sortable()])->defaultSort('updated_at','desc')->recordActions([EditAction::make()->modalWidth('7xl')]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\FreguesiaAgreementResource\Pages\ManageRecords::route('/')];}
}
