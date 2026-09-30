<?php
namespace App\Filament\Resources;
use App\Models\StandPartner;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\{TextInput,Textarea,Select,Toggle,Repeater,DatePicker,DateTimePicker};
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
class StandPartnerResource extends Resource {
 protected static ?string $model=StandPartner::class;
 protected static ?string $modelLabel='Parrains et relais locaux';
 protected static ?string $pluralModelLabel='Parrains et relais locaux';
 protected static string|\UnitEnum|null $navigationGroup='3 · Mobiliser';
 protected static ?int $navigationSort=30;
 
 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([
 Select::make('stand_id')->label('Stand de freguesia')->relationship('stand','name',fn($query)=>$query->where('kind','village'))->required()->disabledOn('edit'),TextInput::make('name')->label('Entreprise / organisme')->required()->maxLength(255),Select::make('main_slot')->label('Parrain principal')->options([1=>'Parrain principal du stand'])->helperText('Une place par stand ; peut aussi occuper une place de relais.'),Select::make('relay_slot')->label('Place de relais')->options([1=>'Relais 1',2=>'Relais 2',3=>'Relais 3']),Select::make('status')->label('État')->options(['prospect'=>'À contacter','agreed'=>'Accord obtenu','active'=>'Mission active et documentée'])->required()->default('prospect'),Toggle::make('hosts_information_meeting')->label('Réunion d’information convenue chez ce relais'),DateTimePicker::make('meeting_at')->label('Date de la réunion')->timezone('Europe/Lisbon'),TextInput::make('meeting_place')->label('Lieu'),Textarea::make('meeting_agreement')->label('Accord : accueil, consommations, visibilité, invitations')->helperText('Proposer ensuite un atelier pour inventer l’épreuve du village ; répartir les rendez-vous entre les relais. Pas de fréquentation garantie.'),Textarea::make('mission')->label('Supports, affichage, orientation et contreparties convenues')->maxLength(10000)->columnSpanFull(),Textarea::make('evidence')->label('Référence de l’accord et de la mission réalisée')->maxLength(5000)->columnSpanFull()
 ]);}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('name')->label('Partenaire')->searchable(),TextColumn::make('stand.name')->label('Stand'),TextColumn::make('main_slot')->label('Parrain'),TextColumn::make('relay_slot')->label('Relais'),TextColumn::make('status')->label('État')])->recordActions([EditAction::make()->modalWidth('7xl')]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\StandPartnerResource\Pages\ManageRecords::route('/')];}
}
