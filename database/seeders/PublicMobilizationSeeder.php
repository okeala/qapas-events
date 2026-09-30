<?php
namespace Database\Seeders;
use App\Models\{EventProject,FreguesiaInvitation,Prospect,Team};
use Illuminate\Database\Seeder;
class PublicMobilizationSeeder extends Seeder {
 public function run(): void {
  $p=EventProject::where('slug','os-jogos-do-agricultor')->first();if(!$p)return;
  foreach([
   ['J-SOUTO','Vale Formoso e Aldeia do Souto','Covilhã','P01','https://www.cm-covilha.pt/?cix=1059&lang=1&tab=795'],
   ['J-BEL','Belmonte','Belmonte','P03','https://www.cm-belmonte.pt/contactos-uteis/'],
   ['J-CARIA','Caria','Belmonte','P04','https://www.cm-belmonte.pt/contactos-uteis/'],
   ['J-COL','Colmeal da Torre','Belmonte','P05','https://www.cm-belmonte.pt/contactos-uteis/'],
   ['J-MAC','Maçainhas','Belmonte','P06','https://www.cm-belmonte.pt/contactos-uteis/'],
   ['J-ING','Inguias','Belmonte','P07','https://www.cm-belmonte.pt/contactos-uteis/'],
  ] as [$key,$name,$municipality,$prospect,$source]){
   $v=FreguesiaInvitation::firstOrCreate(['event_project_id'=>$p->id,'source_key'=>$key],['name'=>$name,'municipality'=>$municipality,'source_url'=>$source,'prospect_id'=>Prospect::where('event_project_id',$p->id)->where('source_key',$prospect)->value('id'),'is_public'=>true,'status'=>'planned','summary_fr'=>'Commune proposée pour les premières prises de contact. Ses habitants et sa diaspora pourront réunir des talents, présenter une spécialité et soutenir leur équipe. L’envoi de l’invitation et la participation restent à confirmer.','summary_pt'=>'Freguesia proposta para os primeiros contactos. Os habitantes e a diáspora poderão reunir talentos, apresentar uma especialidade e apoiar a sua equipa. O envio do convite e a participação estão por confirmar.']);
   // Demonstration teams are real database rows, but cannot stand for an election or reach production pages.
   if(app()->environment('local'))Team::firstOrCreate(['demo_key'=>$p->public_id.'-'.$key],['event_project_id'=>$p->id,'freguesia_invitation_id'=>$v->id,'name'=>'Équipe démo · '.$name,'freguesia'=>$name,'status'=>'forming','is_demo'=>true,'is_public'=>false,'summary_fr'=>'Exemple fictif pour découvrir la fiche d’une équipe. Aucun joueur inscrit, scrutin réalisé ou engagement de cette commune. Les rôles ci-dessous sont à pourvoir.','summary_pt'=>'Exemplo fictício para explorar a ficha de uma equipa. Sem jogadores inscritos, votação realizada ou compromisso desta freguesia. Os papéis abaixo estão por preencher.']);
  }
  $labels=['frame'=>['Le format prend forme','Objectif : 12 stands, 6 communes et 6 indépendants'],'juntas'=>['Les freguesias sont invitées','Les communes sont invitées à participer'],'communication'=>['La fête se prépare dans les villages','La fête se prépare dans les communes'],'growth'=>['La fête pourra grandir','Serez-vous le prochain exposant ?'],'patron'=>['Les premiers parrains rejoignent le projet','Les parrains rendent les Jeux possibles']];
  foreach($labels as $key=>[$old,$new])$p->ideas()->where('template_key',$key)->where('public_label',$old)->update(['public_label'=>$new]);
 }
}
