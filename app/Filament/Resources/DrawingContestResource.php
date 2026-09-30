<?php
namespace App\Filament\Resources;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\{Select,TextInput,Textarea,Toggle,DateTimePicker,Placeholder};
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\{EditAction,Action};
class DrawingContestResource extends Resource {
 protected static ?string $model=\App\Models\DrawingContest::class;
 protected static ?string $modelLabel='Concours de dessins';
 protected static ?string $pluralModelLabel='Concours de dessins';
 protected static string|\UnitEnum|null $navigationGroup='4 · Préparer';
 protected static ?int $navigationSort=76;
 public static function form(Schema $schema): Schema {return $schema->columns(2)->components([Select::make('event_project_id')->label('Édition')->relationship('eventProject','name')->required()->live()->disabledOn('edit'),TextInput::make('name')->label('Nom')->required()->maxLength(255),Select::make('budget_line_id')->label('Coût budgétaire existant · ne pas dupliquer')->options(fn(Get $get)=>\App\Models\BudgetLine::whereHas('scenario',fn($q)=>$q->where('event_project_id',$get('event_project_id')))->where('kind','cost')->pluck('name','id'))->searchable(),
 DateTimePicker::make('opens_at')->label('Ouverture des dépôts')->timezone('Europe/Lisbon'),DateTimePicker::make('closes_at')->label('Clôture le lendemain')->timezone('Europe/Lisbon'),Textarea::make('rules_fr')->label('Règlement FR : critères, âges, départage, recours, données')->rows(6),Textarea::make('rules_pt')->label('Regulamento PT')->rows(6),Textarea::make('jury_evidence')->label('Jury, impartialité et méthode de décision'),Textarea::make('prize_description')->label('Prix pour un enfant par freguesia et remise par le président à la junta'),Toggle::make('is_open')->label('Ouvrir une fois règlement, prix et budget prêts'),
]);}
 public static function table(Table $table): Table {return $table->columns([TextColumn::make('name')->label('Nom')->searchable()->wrap(),TextColumn::make('updated_at')->label('Mise à jour')->dateTime('d/m/Y H:i')->sortable()])->defaultSort('updated_at','desc')->recordActions([EditAction::make()->modalWidth('7xl')]);}
 public static function getPages(): array {return ['index'=>\App\Filament\Resources\DrawingContestResource\Pages\ManageRecords::route('/')];}
}
