<?php

namespace Database\Seeders;

use App\Models\EventBudgetLine;
use App\Models\EventCampaign;
use App\Models\EventCapacityPool;
use App\Models\EventOffer;
use App\Models\EventProduct;
use App\Models\FourPReview;
use App\Models\PlannerEvent;
use App\Services\Events\PlanGeometry;
use App\Services\Events\PlanWriter;
use App\Services\Events\PublicPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FarmersGamesDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing') || ! config('event_planner.demo_enabled')) {
            return;
        }
        $fixture = require resource_path('demo/events/farmers-games/fixture.php');
        DB::transaction(function () use ($fixture) {
            $existing = PlannerEvent::query()->where('slug', $fixture['event']['slug'])->first();
            if ($existing) {
                if (! $existing->is_demo) {
                    throw new \LogicException('Le slug de démonstration est déjà utilisé par un événement réel.');
                }

                return; // Ne jamais écraser un scénario travaillé, une commande ou une preuve à la relance.
            }
            $e = PlannerEvent::create(array_merge($fixture['event'], ['is_demo' => true]));
            $settings = $e->settings;
            $settings['demo_provenance'] = $fixture['provenance'];
            $legacy = require resource_path('demo/events/farmers-games/source/faq.php');
            $settings['editorial_source'] = $legacy;
            $settings['public_faq'] = array_values(array_intersect_key($legacy['items'], array_flip(['weekend', 'challenges', 'relays'])));
            $settings['public_faq'][] = ['question' => ['fr' => 'Une proposition confirme-t-elle ma participation ?', 'pt' => 'Uma proposta confirma a minha participação?'],
                'answer' => ['fr' => 'Votre proposition est examinée par QAPAS. Elle ne signe aucune commande et ne réserve aucune place. Les engagements commerciaux sont documentés séparément.',
                    'pt' => 'A QAPAS analisa a sua proposta. A proposta não assina uma encomenda nem reserva um lugar. Os compromissos comerciais são documentados separadamente.']];
            $settings['public_faq'][] = ['question' => ['fr' => 'Quand l’événement sera-t-il confirmé ?', 'pt' => 'Quando será confirmado o evento?'],
                'answer' => ['fr' => 'Après la première précommande signée, l’organisation dispose au maximum de 21 jours calendaires pour décider. Toutes les précommandes gardent la même échéance. La confirmation exige un format de base financé et réalisable.',
                    'pt' => 'Após a primeira pré-encomenda assinada, a organização dispõe de um máximo de 21 dias de calendário para decidir. Todas as pré-encomendas mantêm o mesmo prazo. A confirmação exige um formato de base financiado e realizável.']];
            $settings['public_faq'][] = ['question' => ['fr' => 'Que devient un paiement si l’événement n’est pas confirmé ?', 'pt' => 'O que acontece ao pagamento se o evento não for confirmado?'],
                'answer' => ['fr' => 'Le montant payé pour la prestation conditionnelle doit être restitué intégralement selon les conditions convenues. Aucun avoir ni report à l’année suivante n’est imposé. Dans cette démonstration, aucun paiement réel n’est accepté.',
                    'pt' => 'O montante pago pela prestação condicional deve ser integralmente restituído segundo as condições acordadas. Não é imposto um crédito ou adiamento para o ano seguinte. Nesta demonstração não são aceites pagamentos reais.']];
            $e->update(['settings' => $settings]);
            $s = $e->scenarios()->create(array_merge($fixture['scenario'], ['prerequisites' => array_fill_keys(array_keys(config('event_planner.prerequisites')), false)]));
            $geo = app(PlanGeometry::class);
            $writer = app(PlanWriter::class);
            $offset = fn ($x, $y) => $geo->offset((float) $s->center_lat, (float) $s->center_lng, $x, $y);
            $first = null;
            for ($i = 0; $i < 24; $i++) {
                [$lng,$lat] = $offset(($i % 6) * 10 - 25, floor($i / 6) * 10 - 15);
                $village = $i < 12;
                $name = $village ? ($i === 0 ? 'Aldeia do Souto — stand proposé' : 'Village '.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT).' — à proposer') : 'Stand indépendant '.str_pad((string) ($i - 11), 2, '0', STR_PAD_LEFT);
                $item = $writer->save($s, ['name' => $name, 'category' => 'structures', 'subcategory' => $village ? 'village_stand' : 'independent_stand', 'shape' => 'square', 'color' => $village ? '#258052' : '#d27934',
                    'dimensions' => ['lat' => $lat, 'lng' => $lng, 'width' => 2.4, 'angle' => 0], 'is_public' => true, 'is_essential' => $village, 'tour_order' => $i + 10,
                    'four_ps' => ['product' => $village ? 'Un village présente une activité, une saveur ou un savoir-faire.' : 'Une offre commerciale indépendante à sélectionner.',
                        'price' => $village ? 'Visite gratuite ; coût du stand et financement à établir.' : '250 € TTC emplacement ou 500 € TTC stand monté ; coûts et valeur à valider.',
                        'place' => 'Emplacement illustratif sur le parcours public, pendant le week-end.', 'promotion' => 'Faire découvrir la proposition de ce stand et orienter les visiteurs.'],
                    'success' => ['objective' => 'Diversité des activités et coopération entre visiteurs et villages.', 'target' => 'À définir avec une méthode de constat.'],
                    'public_content' => ['description' => $village ? 'Découvrez ce qu’un village souhaite partager : savoir-faire, produits et défis accessibles aux autres équipes.' : 'Un emplacement proposé à une activité indépendante. Les exposants et leurs offres seront présentés après sélection.',
                        'public_price' => 'Visite libre ; achats éventuels auprès du vendeur.', 'access' => 'Position illustrative ; accès et horaires à confirmer.', 'story' => 'Prenez le temps de rencontrer les personnes et de découvrir leur proposition.'],
                    'operations' => ['status' => 'needed', 'organization' => 'QAPAS', 'notes' => 'Aucun exposant, village ou poste nominatif confirmé par la démonstration.']]);
                if ($i === 0) {
                    $first = $item;
                }
            }
            $outer = array_map(fn ($p) => $offset(...$p), [[-38, -28], [38, -28], [45, 8], [35, 32], [-40, 30], [-38, -28]]);
            $writer->save($s, ['name' => 'Espace public proposé', 'category' => 'areas', 'subcategory' => 'public', 'shape' => 'free_polygon', 'color' => '#85b48a', 'geometry' => ['type' => 'Polygon', 'coordinates' => [$outer]],
                'is_public' => true, 'is_essential' => true, 'tour_order' => 1, 'four_ps' => ['product' => 'Accueil et circulation.', 'price' => 'Entrée libre ; service financé par le format de base.', 'place' => 'Parcours public et accès à vérifier.', 'promotion' => 'Informations pratiques du plan et de la visite.'],
                'public_content' => ['description' => 'Une fête à explorer librement, entre rencontres, découvertes et activités.', 'public_price' => 'Entrée libre proposée.', 'access' => 'Périmètre illustratif ; capacités et accès à valider.']]);
            foreach ($fixture['logistics'] as $i => $log) {
                [$lng,$lat] = $offset(-35 + $i * 8, -35);
                $item = $writer->save($s, ['name' => $log['label'], 'category' => $log['category'], 'subcategory' => $log['subcategory'], 'shape' => 'rectangle', 'color' => config('event_planner.categories.'.$log['category'].'.color'),
                    'dimensions' => ['lat' => $lat, 'lng' => $lng, 'width' => 4, 'height' => 2], 'is_public' => false, 'is_essential' => true,
                    'four_ps' => ['product' => $log['notes'], 'price' => 'À chiffrer ; le montant indicatif éventuel ne constitue pas un devis.', 'place' => 'Ressource logistique pour le scénario.', 'promotion' => 'Consignes internes aux équipes.'],
                    'operations' => ['status' => 'needed', 'organization' => 'QAPAS', 'notes' => $log['notes']]]);
                $item->budgetLines()->update(['metadata' => ['source' => 'Échanges du projet, 29 septembre 2026', 'indicative_cents' => $log['indicative_cents'] ?? null],
                    'cost_class' => $log['key'] === 'cable' ? 'investment' : 'operating']);
            }
            foreach (config('event_planner.categories.workforce.items') as $i => $label) {
                [$lng,$lat] = $offset(48, count($s->elements()->where('category', 'workforce')->get()) * 3 - 20);
                $writer->save($s, ['name' => $label.' — besoin à confirmer', 'category' => 'workforce', 'subcategory' => $i, 'shape' => 'circle', 'color' => '#484ab7',
                    'dimensions' => ['lat' => $lat, 'lng' => $lng, 'radius' => .8], 'is_public' => false, 'is_essential' => true,
                    'four_ps' => ['product' => 'Couverture du poste '.$label.'.', 'price' => 'Rémunération, repas, déplacements, équipements et formation à établir.', 'place' => 'Créneau et organisation à confirmer.', 'promotion' => 'Briefing et consignes internes.'],
                    'operations' => ['organization' => 'QAPAS ou prestataire à confirmer', 'status' => 'needed', 'notes' => 'Dessiner ce besoin ne réserve personne et n’engage aucun service extérieur.']]);
            }
            $pool = EventCapacityPool::create(['scenario_id' => $s->id, 'name' => '12 emplacements indépendants — variantes alternatives', 'capacity' => 12]);
            foreach ($fixture['offers'] as $offer) {
                $capacity = isset($offer['pool']) ? $pool : (isset($offer['capacity']) ? EventCapacityPool::create(['scenario_id' => $s->id, 'name' => $offer['name'], 'capacity' => $offer['capacity']]) : null);
                EventOffer::create(['scenario_id' => $s->id, 'reference_key' => $offer['key'], 'name' => $offer['name'], 'audience' => $offer['audience'], 'price_cents' => $offer['price_cents'],
                    'net_price_cents' => null, 'delivery_cost_cents' => null, 'pool_id' => $capacity?->id, 'is_public' => true, 'content' => array_intersect_key($offer, array_flip(['description', 'included', 'conditions', 'target_quantity']))]);
            }
            foreach ($fixture['products'] as $product) {
                EventProduct::create(['scenario_id' => $s->id, 'element_id' => $first->id, 'sku' => $product['sku'], 'name' => $product['name'],
                    'price_cents' => $product['price_cents'], 'unit_cost_cents' => null, 'stock' => 0, 'is_public' => false, 'content' => ['description' => $product['description'], 'status' => 'to_define']]);
            }
            foreach ($settings['phase_copy'] as $phase => $copies) {
                EventCampaign::create(['event_id' => $e->id, 'name' => 'Affiche '.$phase, 'audience' => 'visitors', 'channel' => 'Affiche et visite en ligne', 'phase' => $phase,
                    'message' => $copies['fr']['message'], 'call_to_action' => $phase === 'teaser' ? 'Découvrir' : ($phase === 'applications' ? 'Proposer une participation' : 'Préparer ma visite'), 'state' => 'draft']);
            }
            foreach (['visitors' => 'La promesse donne envie de venir.', 'exhibitors' => 'L’opportunité commerciale justifie le prix proposé.', 'sponsors' => 'Les prestations justifient la valeur et leur répartition.', 'relays' => 'Le kit et l’accompagnement apportent une valeur suffisante.'] as $audience => $hypothesis) {
                FourPReview::create(['event_id' => $e->id, 'scenario_id' => $s->id, 'level' => 'event', 'audience' => $audience, 'name' => 'Validation '.$audience,
                    'four_ps' => ['product' => $hypothesis, 'price' => 'À confronter aux coûts et à l’acceptation.', 'place' => 'Parcours local et numérique à tester.', 'promotion' => 'Message à tester dans la phase de candidatures.'],
                    'hypothesis' => $hypothesis, 'evidence_kind' => 'hypothesis', 'next_action' => 'Conduire des entretiens et conserver propositions et motifs de refus.', 'decision' => 'improve']);
            }
            foreach (['Préparation et rémunérations complètes', 'Communication, impression et livraison des kits', 'Montage, démontage, nettoyage et aléas'] as $i => $label) {
                EventBudgetLine::create(['scenario_id' => $s->id, 'reference_key' => 'common:'.$i, 'label' => $label, 'kind' => 'cost', 'gross_cents' => null, 'net_cents' => null]);
            }
            $e->scenarios()->create(['name' => 'Format réduit — à concevoir', 'is_base' => false, 'center_lat' => $s->center_lat, 'center_lng' => $s->center_lng, 'prerequisites' => []]);
            app(PublicPlan::class)->publish($s);
        });
    }
}
