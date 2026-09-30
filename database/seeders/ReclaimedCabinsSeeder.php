<?php
namespace Database\Seeders;

use App\Models\{CabinProject, EventProject, Sponsorship};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReclaimedCabinsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $project = EventProject::where('slug','os-jogos-do-agricultor')->first();
            $scenario = $project?->launchScenario();
            if (!$scenario) return;
            if (!$project->cabin_policy_version) $project->update(['cabin_policy_version'=>'reclaimed-v1']);
            foreach ($scenario->includedStands as $stand) {
                $rental = $stand->kind==='independent';
                $cost = $rental ? $scenario->budgetLines()->firstOrCreate(['costing_key'=>'cabin-build-'.$stand->public_id], [
                    'name'=>'Cabane QAPAS : fabrication complète · '.$stand->name, 'kind'=>'cost', 'scope'=>'stand', 'stand_id'=>$stand->id,
                    'expense_type'=>'investment', 'unit'=>'cabane', 'forecast_quantity'=>1, 'unit_gross_cents'=>null, 'vat_basis_points'=>null, 'pricing_status'=>'estimate',
                    'price_source'=>'À chiffrer sur prototype : raccords d’échafaudage (pièce maîtresse), tubes récupérés de démolition, préparation mimosa/cannes, sisal, main-d’œuvre, montage/démontage et contrôle. Transport/stockage communs dans leur poste unique. Décaissement intégral cette édition ; aucun amortissement diminuant la trésorerie. Apport matériel non acquis, aucune gratuité présumée.',
                ]) : null;
                $income = $rental ? $scenario->budgetLines()->firstOrCreate(['costing_key'=>'cabin-rental-'.$stand->public_id], [
                    'name'=>'Cabane QAPAS : supplément de location éventuel · '.$stand->name, 'kind'=>'revenue', 'scope'=>'stand', 'stand_id'=>$stand->id,
                    'unit'=>'location', 'forecast_quantity'=>0, 'unit_gross_cents'=>null, 'vat_basis_points'=>null, 'pricing_status'=>'estimate',
                    'price_source'=>'Inactif tant que location non tarifée ou comprise dans la formule du stand. Activer seulement un supplément contractuel distinct après passage du dossier en mode Supplément. Dépôt remboursable séparé, jamais une recette.',
                ]) : null;
                CabinProject::firstOrCreate(['stand_id'=>$stand->id], [
                    'event_project_id'=>$project->id, 'name'=>'Cabane · '.$stand->name, 'supply_mode'=>$rental?'qapas_rental':'team_build',
                    'cost_line_id'=>$cost?->id, 'rental_line_id'=>$income?->id,
                    'summary_fr'=>$rental?'Cabane fabriquée et louée par QAPAS pour accueillir un exposant indépendant. Attribution et prestation à confirmer.':'La freguesia imagine et construit son propre stand en récupération. Ses équipes partagent cette cabane ; candidature au prix du public à préparer.',
                    'summary_pt'=>$rental?'Cabana construída e alugada pela QAPAS para um expositor independente. Atribuição e condições por confirmar.':'A freguesia imagina e constrói o seu espaço com materiais recuperados. As suas equipas partilham a cabana; participação no prémio do público a preparar.',
                ]);
            }
            $scenario->budgetLines()->firstOrCreate(['costing_key'=>'cabins-shared-logistics'], [
                'name'=>'Cabanes : transport mutualisé, manutention, stockage et suivi après démontage', 'kind'=>'cost', 'scope'=>'common',
                'unit'=>'lot commun', 'forecast_quantity'=>1, 'unit_gross_cents'=>null, 'vat_basis_points'=>null, 'pricing_status'=>'estimate',
                'price_source'=>'Coûts supplémentaires aux travaux de terrain déjà provisionnés. Trajets, levage éventuel, stockage sec, entretien et suivi végétation à chiffrer une seule fois. Les 1 000 € de nettoyage/nivellement restent provisionnés ; ne retirer une dépense que sur preuve de coût réellement évité.',
            ]);
            $main = Sponsorship::where('event_project_id',$project->id)->where('template_key','event-main-v1')->first();
            $oldPitch = 'Financement et apports précisément définis, visibilité web hiérarchisée. Mobilier et reprise de fûts seulement selon contrat. Aucun prix ou audience inventé.';
            if ($main && $main->status==='prospecting' && !$main->prospect_id && blank($main->sponsor_name) && blank($main->agreement_evidence) && blank($main->delivery_terms) && $main->target_profile==='Brasseur / distributeur boissons ou fournisseur agricole structurant' && $main->pitch===$oldPitch) {
                $main->update([
                    'target_profile'=>'Fabricant, distributeur ou loueur de raccords d’échafaudage ; tubes récupérés auprès de démolisseurs partenaires',
                    'pitch'=>'Le raccord d’échafaudage EST la pièce maîtresse. Sponsor principal proposé : « Celui qui nous relie et fait tenir les Jeux ». Son produit relie concrètement les tubes récupérés, les cabanes et symboliquement les freguesias. Fourniture/prêt du lot de raccords adapté au prototype, démonstration d’assemblage encadrée et histoire du réemploi selon accord. Un seul sponsor principal ; apport matériel et éventuel financement séparés. Aucun partenaire acquis ni audience garantie.',
                    'delivery_terms'=>'PROJET : nomenclature et quantités de raccords après prototype ; types, diamètres et état compatibles avec les tubes récupérés ; don/prêt/location, inspection, transport, retour et pertes convenus. Présence web et making-of avec droits accordés ; signalétique/imprimés par lot et BAT. Aucun pouvoir sur le vote. Valorisation de l’apport séparée de l’économie réelle et du versement monétaire éventuel.',
                ]);
            }
            $offer = $project->offers()->where('template_key','sponsor-main')->first();
            $oldSummary = 'Premier rang de visibilité web défini au contrat ; contribution et éventuels équipements communs à négocier.';
            if ($offer && !$offer->is_public && $offer->summary===$oldSummary && $offer->includes===$oldSummary) $offer->update([
                'summary'=>'Le raccord d’échafaudage, pièce maîtresse des cabanes : celui qui nous relie et fait tenir les Jeux.',
                'includes'=>'Projet de partenariat principal autour des raccords d’échafaudage : lot adapté au prototype, récit du réemploi, premier rang web contractuel. Don, prêt et éventuel versement définis séparément ; visibilité sur cabanes et making-of selon droits et accord.',
            ]);
            $previous = [];
            foreach ([
                ['cabin-prototype','Valider un prototype autour du raccord d’échafaudage','product','Une pièce maîtresse commune permet des cabanes créatives et réparables.','Relever les tubes des démolisseurs ; faire choisir les raccords compatibles. Monter un prototype de 2,40 m au cube avec sisal et plessis. Contrôler assemblage, ancrage, stabilité, feu et usage sous pluie avant série. Chiffrer tous les coûts réels.'],
                ['cabin-partner','Proposer au fournisseur de raccords le partenariat principal','promotion','Le sponsor incarne physiquement le lien entre les freguesias.','Présenter le prototype et le lot exact. Négocier apport matériel, prêt ou remise, retour, visibilité et cash séparés. Ne pas ajouter un deuxième sponsor principal ni valoriser le même apport comme recette et dépense évitée.'],
                ['cabin-build','Organiser les chantiers des freguesias et les cabanes QAPAS','place','Une cabane commune par stand ; les équipes créent, QAPAS loue aux indépendants.','Répartir responsables, inventaires, récolte documentée, prévention des propagules, ligatures, outils et travail. Préparer conditions de location, état des lieux et caution hors recette. Garder l’accueil 24 abrités / 12 assis distinct de la cabane de 5,76 m².'],
                ['cabin-vote','Préparer le prix du public de la cabane la plus spectaculaire','promotion','La créativité des équipes donne envie de venir les soutenir.','Vote gratuit prévu sur bulletin : décider éligibilité, période, contrôle des doublons, urne, dépouillement, égalités et annonce. Prix du public distinct de la Forquilha de Ouro ; pas de vote en ligne livré. Les locations QAPAS ne concourent pas contre les équipes.'],
                ['cabin-reception','Réceptionner chaque cabane sur son emplacement','place','Une fabrication terminée ne suffit pas à ouvrir au public.','Implanter quartel et numéro, inventorier tubes/raccords/panneaux/ligatures, mesurer le gabarit et enregistrer contrôle sur site, auteur et preuves. Toute modification ou déplacement impose une nouvelle réception.'],
                ['cabin-followup','Démonter, stocker et suivre les repousses','product','La valorisation locale doit accompagner le contrôle des mimosas.','Récupérer les raccords et tubes réemployables ; organiser le devenir des panneaux sans dispersion. Inspecter le chantier, noter repousses/rejets et programmer les interventions adaptées. La coupe seule ne prouve pas l’éradication.'],
            ] as [$key,$name,$pillar,$hypothesis,$experiment]) {
                $idea = $project->ideas()->firstOrCreate(['template_key'=>$key], compact('name','pillar','hypothesis','experiment')+['status'=>'idea','is_public'=>false,'depends_on'=>$previous]);
                $previous = [$idea->id];
            }
            app(\App\Domain\Procurement\Consultations::class)->synchronize();
        });
    }
}
