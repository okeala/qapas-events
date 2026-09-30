<?php
namespace App\Filament\Resources;
use App\Models\MerchandisingOption;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Forms\Components\{Select,TextInput,Textarea,Repeater,DateTimePicker};
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\{EditAction,Action};
class MerchandisingOptionResource extends Resource {
 protected static ?string $model=MerchandisingOption::class;
 protected static ?string $modelLabel='Variante merchandising';
 protected static ?string $pluralModelLabel='Merchandising · impression et broderie';
 protected static string|\UnitEnum|null $navigationGroup='2 · Chiffrer';
 protected static ?int $navigationSort=37;
 public static function form(Schema $schema): Schema {
  $fields=[];foreach(['prototype_quantity'=>'Prototypes de prospection en plus du lot','setup_minutes'=>'Temps de préparation payé (minutes)','minutes_per_piece'=>'Temps opératrice par pièce (minutes)','hourly_cost_cents'=>'Coût horaire complet payé (centimes)','machine_cost_cents'=>'Achat machine TTC complet (centimes)','other_fixed_cents'=>'Création, BAT, transport, entretien fixe (centimes)','external_unit_cents'=>'Prix comparable externalisé tout compris / pièce (centimes)','external_fixed_cents'=>'Frais fixes du devis externalisé comparable (centimes)','qapas_minutes'=>'Temps QAPAS détourné de la prospection (minutes)','opportunity_hourly_cents'=>'Valeur comparative du temps QAPAS / heure (centimes)'] as $key=>$label)$fields[]=TextInput::make($key)->label($label)->integer()->minValue(0)->helperText('Hypothèse modifiable. Zéro = absence documentée ; inconnu = vide.');
  return $schema->columns(2)->components([Select::make('welcome_pack_plan_id')->label('Welcome pack')->relationship('welcomePackPlan','name')->required()->disabledOn('edit'),TextInput::make('name')->label('Variante')->required(),Select::make('method')->label('Méthode')->options(MerchandisingOption::METHODS)->required()->disabledOn('edit'),Repeater::make('costs')->label('Composants par pièce · ne pas compter deux fois la prestation')->schema([TextInput::make('name')->label('Poste')->required(),TextInput::make('per_piece')->label('Quantité par t-shirt')->integer()->minValue(1)->maxValue(10)->default(1)->required(),TextInput::make('unit_cents')->label('Prix TTC en centimes')->integer()->minValue(0)])->columns(3)->minItems(1)->maxItems(20)->columnSpanFull(),...$fields,Textarea::make('source')->label('Devis / hypothèses / périmètre / IVA')->rows(5)->columnSpanFull(),Textarea::make('specification')->label('Tailles, motifs, emplacements et rendu photo')->rows(5)->columnSpanFull(),DateTimePicker::make('sample_approved_at')->label('Échantillon validé le')->timezone('Europe/Lisbon'),Textarea::make('sample_evidence')->label('Essai réel, lavage, rendu photo et droits du logo')]);
 }
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('name')->label('Variante')->wrap(),TextColumn::make('welcomePackPlan.name')->label('Lot')])->recordActions([Action::make('compare')->label('Comparer les possibilités')->modalContent(fn(MerchandisingOption $record)=>view('filament.merchandising.compare',['options'=>MerchandisingOption::where('welcome_pack_plan_id',$record->welcome_pack_plan_id)->get()]))->modalWidth('7xl')->modalSubmitAction(false)->modalCancelActionLabel('Fermer'),EditAction::make()->modalWidth('7xl')]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\MerchandisingOptionResource\Pages\ManageRecords::route('/')];}
}
