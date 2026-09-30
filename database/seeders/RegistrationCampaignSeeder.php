<?php
namespace Database\Seeders;
use App\Models\{EventProject,RegistrationCampaign};
use Illuminate\Database\Seeder;
class RegistrationCampaignSeeder extends Seeder {
 public function run(): void {
  $p=EventProject::where('slug','os-jogos-do-agricultor')->first();if(!$p)return;
  RegistrationCampaign::firstOrCreate(['event_project_id'=>$p->id],['name'=>'Candidatures experts · 10 € TTC','terms_version'=>'draft-2026-09-30','terms_fr'=>'Une candidature par personne et par édition : 10 € TTC, même si plusieurs rôles sont proposés. Le paiement valide la candidature, sans garantir la sélection. Les citoyens choisissent les membres lors du vote local. Un candidat payé non retenu reçoit un crédit de 10 € en tickets boissons auprès du bar QAPAS. Une consommation réglée en tickets ne constitue pas un second paiement. Conditions de scrutin, éligibilité, utilisation des tickets et annulation à compléter avant ouverture.','terms_pt'=>'Uma candidatura por pessoa e edição: 10 € com IVA, mesmo para vários papéis. O pagamento valida a candidatura, sem garantir a seleção. Os cidadãos escolhem os membros através da votação local. Cada candidato com pagamento confirmado que não seja selecionado recebe 10 € em vales de bebidas no bar QAPAS. Uma bebida paga com vale não é cobrada novamente. Completar as regras de votação, elegibilidade, utilização dos vales e cancelamento antes da abertura.']);
 }
}
