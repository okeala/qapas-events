<?php
namespace App\Filament\RelationManagers;
use App\Domain\Teams\ExpertRoles;
use App\Models\{Interest,TeamRoleAssignment};
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Forms\Components\{TextInput,Textarea,Select,Toggle};
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
class TeamRolesRelationManager extends RelationManager {
 protected static string $relationship='roleAssignments';
 protected static ?string $title='Les treize rôles à pourvoir';
 public function form(Schema $schema): Schema {return $schema->columns(2)->components([
  Select::make('role_code')->label('Rôle expert')->options(ExpertRoles::labels())->disabled(),
  Select::make('interest_id')->label('Candidature reçue, facultative')->options(fn(?TeamRoleAssignment $record)=>Interest::where('event_project_id',$this->getOwnerRecord()->event_project_id)->whereJsonContains('expert_roles',$record?->role_code)->get()->mapWithKeys(fn($interest)=>[$interest->id=>$interest->name.' · '.$interest->freguesia]))->searchable()->helperText('Relie la demande à ce poste, sans inscrire automatiquement la personne. Une candidature locale hors internet reste possible.'),
  TextInput::make('candidate_name')->label('Personne proposée')->maxLength(120),
  TextInput::make('candidate_reference')->label('Référence de personne au registre de l’édition')->maxLength(120)->helperText('Réutiliser la même référence pour une même personne. Référence interne, pas de numéro de pièce d’identité. Sert à vérifier les cumuls et l’unicité entre équipes.'),
  Toggle::make('consent_confirmed')->label('La personne accepte cette candidature et ce rôle'),
  Textarea::make('competence_evidence')->label('Compétence vérifiée, disponibilité et référence de contrôle')->maxLength(5000)->helperText('Noter qui a vérifié et sur quelle base. Pour le comptable : exercice professionnel. Pour les engins : expérience, titres et autorisation adaptés à contrôler. Aucun document d’identité ni renseignement médical ici.')->columnSpanFull(),
  Select::make('status')->label('Avancement')->options(['vacant'=>'À recruter','proposed'=>'Personne proposée, à vérifier','confirmed'=>'Rôle pourvu et vérifié'])->required()->helperText('La confirmation d’un rôle ne remplace pas le résultat du vote local. Modifier la personne ou les preuves impose une nouvelle confirmation.'),
 ]);}
 public function table(Table $table): Table {return $table->recordTitleAttribute('role_code')->columns([
  TextColumn::make('role_code')->label('Rôle obligatoire')->formatStateUsing(fn($state)=>ExpertRoles::labels()[$state]??$state),
  TextColumn::make('candidate_name')->label('Personne')->placeholder('À recruter')->searchable(),
  TextColumn::make('status')->label('État')->formatStateUsing(fn($state)=>['vacant'=>'À recruter','proposed'=>'À vérifier','confirmed'=>'Pourvu'][$state]??$state)->badge(),
  TextColumn::make('reviewed_at')->label('Vérifié le')->dateTime('d/m/Y H:i')->placeholder('Non vérifié'),
 ])->paginated(false)->defaultSort('id')->recordActions([EditAction::make()]);}
}
