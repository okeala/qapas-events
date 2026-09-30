<?php
namespace Database\Seeders;
use App\Models\EventProject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class OfficialActivitiesSeeder extends Seeder {
 public function run(): void {
  DB::transaction(function(){
   $project=EventProject::where('slug','os-jogos-do-agricultor')->first() ?? EventProject::where('slug','forqua-de-ouro')->first();if(!$project)return;
   if($project->name==='Forqua de Ouro') $project->name='Os Jogos do Agricultor';
   if($project->slug==='forqua-de-ouro'&&!EventProject::where('slug','os-jogos-do-agricultor')->exists()) $project->slug='os-jogos-do-agricultor';
   $project->save();
   // Archive only unedited starter ideas. Never delete or overwrite an organizer's work.
   $oldRules='Idée à prototyper : règles simples, temps court, critères visibles. Matériel léger ; pas de machines conduites, outils tranchants, alcool ou épreuve de force. Prévoir une variante accessible et des essais publics séparés.';
   $project->activities()->whereNull('template_key')->where('status','idea')->where('rules',$oldRules)->whereIn('name',['Panier de fruits en mousse','Plateau coopératif de récolte','Mini-tracteur télécommandé','Puzzle sec de circulation de l’eau','Empilage de caisses légères','Transfert de fruits avec grandes moufles'])->update(['status'=>'archived','is_public'=>false]);
   $catalog=json_decode(file_get_contents(database_path('data/official-activities.json')),true,512,JSON_THROW_ON_ERROR);
   foreach($catalog as $index=>$item){
    $materials=$item['materials'];unset($item['materials']);
    $activity=$project->activities()->firstOrCreate(['template_key'=>$item['template_key']],$item+['track'=>'official','status'=>'idea','is_public'=>true,'sort_order'=>$index+1]);
    if($activity->wasRecentlyCreated) foreach($materials as $material) $activity->materials()->create($material);
   }
  });
 }
}
