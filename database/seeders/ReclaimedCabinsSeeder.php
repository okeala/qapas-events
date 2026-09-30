<?php
namespace Database\Seeders;

use App\Models\{CabinProject, EventProject, Sponsorship, EditorialPost};
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
                    'price_source'=>'À chiffrer sur prototype : raccords d’échafaudage (pièce maîtresse), tubes récupérés de démolition (diamètre envisagé 30 mm, épaisseur et état à relever), préparation mimosa/cannes, éventuelle surcouverture de paille récupérée, sisal, main-d’œuvre, montage/démontage et contrôle. Transport/stockage communs dans leur poste unique. Prix complet pour les dimensions du dossier : toute extension impose de revoir quantités, devis et emprise. Décaissement intégral cette édition ; aucun amortissement diminuant la trésorerie. Apport matériel non acquis, aucune gratuité présumée.',
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
                'name'=>'Cabanes : transport mutualisé, tri, broyage, paillage et suivi après démontage', 'kind'=>'cost', 'scope'=>'common',
                'unit'=>'lot commun', 'forecast_quantity'=>1, 'unit_gross_cents'=>null, 'vat_basis_points'=>null, 'pricing_status'=>'estimate',
                'price_source'=>'Coûts supplémentaires aux travaux de terrain déjà provisionnés. Trajets, manutention, tri/retrait des métaux et ligatures, opérateur du broyeur QAPAS, carburant, entretien et pièces d’usure, traitement des lots à risque de propagation, stockage temporaire, épandage sur place et suivi à chiffrer une seule fois. L’achat du broyeur est dans son poste investissement commun unique. Les 1 000 € de nettoyage/nivellement restent provisionnés ; ne retirer une dépense que sur preuve de coût réellement évité.',
            ]);
            $scenario->budgetLines()->firstOrCreate(['costing_key'=>'cabins-shredder-purchase'], [
                'name'=>'Cabanes : achat du broyeur QAPAS livré et mis en service', 'kind'=>'cost', 'scope'=>'common',
                'expense_type'=>'investment', 'unit'=>'machine', 'forecast_quantity'=>1, 'unit_gross_cents'=>null, 'vat_basis_points'=>null, 'pricing_status'=>'estimate',
                'price_source'=>'Achat QAPAS financé par l’initiative, non une location. Devis pour le broyage du mimosa et des cannes de diamètre maximal 60 mm : aptitude réelle aux deux matériaux à confirmer, débit, entraînement, livraison, mise en service et protection inclus. Décaissement intégral cette édition, sans amortissement diminuant le besoin de trésorerie. Aucun achat, financement ou paiement présumé acquis. Opérateur, carburant et entretien dans la logistique commune, sans doublon.',
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
                ['cabin-prototype','Valider un prototype autour du raccord d’échafaudage','product','Une pièce maîtresse commune permet des cabanes créatives et réparables.','Végétaux de 6 cm de diamètre maximum. Relever les tubes des démolisseurs ; faire choisir les raccords compatibles. Monter un prototype avec sisal et plessis : largeur et hauteur fixes à 2,40 m, longueur libre. Tubes de 30 mm envisagés, épaisseur et état à mesurer, raccords compatibles et entraxes des portiques selon note de calcul validée. Aucun pas de travée imposé, aucun objectif d’économie de mimosa. Contrôler assemblage, ancrage, stabilité, feu et usage sous pluie avant série. Chiffrer tous les coûts réels.'],
                ['cabin-partner','Proposer au fournisseur de raccords le partenariat principal','promotion','Le sponsor incarne physiquement le lien entre les freguesias.','Présenter le prototype et le lot exact. Négocier apport matériel, prêt ou remise, retour, visibilité et cash séparés. Ne pas ajouter un deuxième sponsor principal ni valoriser le même apport comme recette et dépense évitée.'],
                ['cabin-demo','Filmer la démonstration QAPAS : du raccord au plessis','promotion','Une démonstration du prototype rend la construction compréhensible.','Préparer une vidéo sur téléphone, avec gros plan du raccord d’échafaudage, tubes de récupération, ligatures, tressage et variante de toit mimosa/paille. Montrer les matériaux de 6 cm maximum, l’essai sous pluie et le tri final pour broyage. Faire relire les gestes du prototype avant tournage ; droits, sous-titres FR/PT et accord sponsor. Temps de tournage/montage dans les postes organisation/captation, sans doublon. Publier l’ID YouTube dans Blog et making-of ; aucune vidéo fictive.'],
                ['cabin-build','Organiser les chantiers des freguesias et les cabanes QAPAS','place','Une cabane commune par stand ; les équipes créent, QAPAS loue aux indépendants.','Répartir responsables, inventaires, récolte documentée sans quota par cabane dans les zones désignées, prévention des propagules, ligatures, outils et travail. Préparer conditions de location, état des lieux et caution hors recette. Garder l’accueil 24 abrités / 12 assis distinct de la cabane de 5,76 m².'],
                ['cabin-vote','Préparer le prix du public de la cabane la plus spectaculaire','promotion','La créativité des équipes donne envie de venir les soutenir.','Vote gratuit prévu sur bulletin : décider éligibilité, période, contrôle des doublons, urne, dépouillement, égalités et annonce. Prix du public distinct de la Forquilha de Ouro ; pas de vote en ligne livré. Les locations QAPAS ne concourent pas contre les équipes.'],
                ['cabin-reception','Réceptionner chaque cabane sur son emplacement','place','Une fabrication terminée ne suffit pas à ouvrir au public.','Implanter quartel et numéro, inventorier tubes/raccords/panneaux/ligatures, mesurer le gabarit et enregistrer contrôle sur site, auteur et preuves. Toute modification ou déplacement impose une nouvelle réception. Pour une extension : note de calcul, auteur, date et entraxe validé indispensables ; forces en N, moments en N·m.'],
                ['cabin-followup','Démonter, broyer, pailler et préparer les plantations','product','La valorisation locale doit accompagner le contrôle des mimosas.','Récupérer raccords, tubes et visserie ; retirer les ligatures avant broyage. Trier les végétaux, isoler les lots contenant graines/rhizomes/fragments capables de reprise et valider leur traitement. Acheter le broyeur QAPAS sur financement de l’initiative ; capacité réelle pour mimosa/cannes de 6 cm maximum validée au devis. Broyer les lots admis, pailler sur place selon les besoins du sol ; le mélange ne devient pas immédiatement assimilable et ne constitue pas nécessairement du BRF. Préparer les plantations après l’événement dès que les parcelles sont prêtes. Suivre repousses, jeunes arbres et reprise ; la coupe seule ne prouve pas l’éradication.'],
            ] as [$key,$name,$pillar,$hypothesis,$experiment]) {
                $idea = $project->ideas()->firstOrCreate(['template_key'=>$key], compact('name','pillar','hypothesis','experiment')+['status'=>'idea','is_public'=>false,'depends_on'=>$previous]);
                $previous = [$idea->id];
            }
            EditorialPost::firstOrCreate(['event_project_id'=>$project->id,'template_key'=>'cabin-demo'], [
                'name'=>'Construire notre cabane : du raccord au plessis', 'title_pt'=>'Construir a nossa cabana: da abraçadeira ao entrançado',
                'body_fr'=>"PROJET DE VIDÉO QAPAS — à tourner sur un prototype validé.
1. Ouvrir en gros plan sur la pièce maîtresse : le raccord d’échafaudage.
2. Présenter les tubes récupérés et leur assemblage compatible sur une largeur et une hauteur de 2,40 m, avec longueur libre ; tubes de 30 mm envisagés et entraxes des portiques selon dimensionnement validé.
3. Montrer le contrôle du diamètre des mimosas/cannes : 6 cm maximum.
4. Démontrer les ligatures en sisal et le tressage d’un panneau.
5. Explorer un toit en plessis de mimosa avec paille en recouvrement : deux pentes, faîtage soigné, décor propre à chaque freguesia ; respecter la largeur et la hauteur totale de 2,40 m.
6. Montrer les essais du prototype, dont pluie et stabilité ; ne publier que les gestes validés.
7. Expliquer le démontage : métaux récupérés, végétaux triés pour broyage et paillage, plantations et suivi.
Prévoir sous-titres FR/PT, droits des personnes et du sponsor. Aucun tournage ni publication confirmés ; renseigner l’identifiant YouTube après réalisation.",
                'body_pt'=>"PROJETO DE VÍDEO QAPAS — filmar um protótipo validado.
1. Começar com a peça central em grande plano: a abraçadeira de andaime.
2. Mostrar os tubos recuperados e a montagem compatível com 2,40 m de largura e altura e comprimento livre; tubos previstos de 30 mm e distância entre pórticos conforme dimensionamento validado.
3. Verificar o diâmetro da mimosa e das canas: máximo 6 cm.
4. Demonstrar as ligações em sisal e o entrançado de um painel.
5. Explorar um telhado em entrançado de mimosa coberto com palha sobreposta: duas águas, cumeeira cuidada e decoração de cada freguesia; respeitar os 2,40 m de largura e altura total.
6. Mostrar os ensaios do protótipo, incluindo chuva e estabilidade; publicar apenas gestos validados.
7. Explicar a desmontagem: metais recuperados, vegetais separados para trituração e cobertura do solo, plantações e acompanhamento.
Prever legendas FR/PT e direitos das pessoas e do parceiro. Filmagem e publicação por concretizar; inserir o identificador YouTube após produção.",
            ]);
            app(\App\Domain\Procurement\Consultations::class)->synchronize();
        });
    }
}
