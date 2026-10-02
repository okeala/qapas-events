<x-filament-panels::page>
    @vite(['resources/css/event-planner.css', 'resources/js/planner-map.js'])
    @php
        $money=\App\Services\Events\Money::class;
        $categories=config('event_planner.categories');
        $audiences=config('event_planner.audiences');
        $elementOptions=[''=>'Commun à ce scénario']+$elements->pluck('name','id')->all();
    @endphp
    <div class="planner-toolbar">
        <label>Événement <select wire:model.live="eventId" wire:change="selectEvent"><option value="">Choisir</option>@foreach($events as $e)<option value="{{ $e->id }}">{{ $e->name }}{{ $e->is_demo?' · Démonstration':'' }}</option>@endforeach</select></label>
        <label>Scénario <select wire:model.live="scenarioId" wire:change="selectScenario">@foreach($scenarios as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></label>
        @if($event)
            <span class="planner-badge">{{ config('event_planner.phases.'.$event->phase) }} · {{ $event->publicDecision() }}</span>
            @if($event->decision_deadline)<span>Décision avant {{ $event->decision_deadline->timezone($event->timezone)->format('d/m/Y H:i') }}</span>@endif
            @if($event->published_revision_id)<a class="planner-button secondary" href="{{ route('events.show',$event->slug) }}" target="_blank" rel="noopener">Voir la visite publiée</a>@endif
        @endif
    </div>
    @if($event?->is_demo)<p class="planner-notice">Projet de démonstration : implantation illustrative, tarifs de référence et besoins à vérifier. Aucun événement, paiement ou fournisseur n’est confirmé par ce jeu.</p>@endif
    @if($errors->any())<div role="alert" class="planner-error">@foreach($errors->all() as $message)<p>{{ $message }}</p>@endforeach</div>@endif

    <details class="planner-card" @if(!$event) open @endif>
        <summary>Créer un nouvel événement</summary>
        <form wire:submit="createEvent" class="planner-form-grid">
            <x-planner-field model="eventForm.name" label="Nom" />
            <x-planner-field model="eventForm.slug" label="Adresse courte" />
            <x-planner-field model="eventForm.venue" label="Lieu proposé" />
            <x-planner-field model="eventForm.lat" label="Latitude du site" type="number" step="any" />
            <x-planner-field model="eventForm.lng" label="Longitude du site" type="number" step="any" />
            <x-planner-field model="eventForm.starts_at" label="Début proposé" type="datetime-local" />
            <x-planner-field model="eventForm.ends_at" label="Fin proposée" type="datetime-local" />
            <x-planner-field model="eventForm.decision_days" label="Délai de décision en jours, 21 maximum" type="number" min="1" max="21" />
            <x-planner-field model="eventForm.description" label="Promesse de l’événement" type="textarea" />
            <x-planner-field model="eventForm.entry_free" label="Entrée libre" type="checkbox" />
            <button class="planner-button">Créer l’événement et son format de base</button>
        </form>
    </details>

    @if($scenario)
    <nav class="planner-tabs" aria-label="Modules de l’événement">
        @foreach(['EventPlan'=>'Plan','EventCosts'=>'Coûts','EventRevenues'=>'Recettes','EventBalance'=>'Bilan','EventCommunications'=>'Communication','EventProducts'=>'Produits'] as $page=>$label)
            <a href="{{ ('App\\Filament\\Pages\\'.$page)::getUrl(['event'=>$eventId,'scenario'=>$scenarioId]) }}">{{ $label }}</a>
        @endforeach
    </nav>

    @if($module === 'plan')
        <div class="planner-studio" wire:key="studio-{{ $scenarioId }}">
            <aside class="planner-card planner-library">
                <h2>Bibliothèque</h2>
                <p>Choisissez un équipement ou un espace, puis placez-le sur le plan.</p>
                <button class="planner-button secondary" wire:click="newElement">Nouvel élément</button>
                @foreach($categories as $key=>$category)
                    <button type="button" class="planner-library-item" wire:click="chooseCategory('{{ $key }}')"><i style="background:{{ $category['color'] }}"></i>{{ $category['label'] }}</button>
                @endforeach
                <h3>Éléments du plan</h3>
                <div class="planner-item-list">@foreach($elements as $item)<button wire:click="selectElement({{ $item->id }})" @class(['selected'=>$elementId===$item->id])>{{ $item->name }}</button>@endforeach</div>
            </aside>
            <div class="planner-map-panel">
                <div wire:ignore data-planner-map data-admin="true" data-scenario="{{ $scenarioId }}" data-api="{{ route('planner.plan.data',$scenario) }}" data-center="{{ json_encode([(float)$scenario->center_lat,(float)$scenario->center_lng]) }}">
                    <div class="planner-map" aria-label="Plan interactif de l’événement"></div>
                </div>
                <p class="planner-map-hint">Un clic sur le terrain place le gabarit. Les espaces et axes se dessinent avec les outils du plan. Les équipements gardent leur forme ; leur position, dimensions et orientation se règlent dans la fiche.</p>
                <div class="planner-actions"><button class="planner-button" wire:click="publishPlan">Publier ce plan et la visite</button><button class="planner-button secondary" wire:click="withdrawPlan">Retirer la publication</button><a href="{{ route('planner.export',$scenario) }}" class="planner-button secondary">Exporter l’inventaire CSV</a></div>
                <details class="planner-card"><summary>Créer une variante vide</summary><div x-data="{ name:'' }"><input x-model="name" placeholder="Nom du scénario"><button class="planner-button" @click="$wire.createScenario(name)">Créer</button></div></details>
                <details class="planner-card"><summary>Créer une variante depuis ce plan</summary><div x-data="{ name:'' }"><input x-model="name" placeholder="Nom de la variante"><button class="planner-button" @click="$wire.copyScenario(name)">Copier le scénario</button></div><p>Le plan et les coûts deviennent une hypothèse de comparaison. Les engagements, personnes et stocks restent dans leur dossier d’origine.</p></details>
            </div>
            <aside class="planner-card planner-inspector">
                <h2>{{ $elementId ? 'Fiche de l’élément' : 'Nouvel élément' }}</h2>
                <form wire:submit="savePlan">
                    <x-planner-field model="plan.name" label="Nom de l’élément" />
                    <x-planner-field model="plan.category" label="Catégorie" :options="array_map(fn($c)=>$c['label'],$categories)" />
                    <x-planner-field model="plan.subcategory" label="Sous-catégorie" :options="$categories[$plan['category']]['items']??[]" />
                    <x-planner-field model="plan.shape" label="Forme" :options="\App\Services\Events\PlanGeometry::shapesFor($plan['category'])" />
                    <x-planner-field model="plan.color" label="Couleur" type="color" />
                    @if(!in_array($plan['shape'],['free_polygon','line']))
                        <div class="planner-form-grid">
                            <x-planner-field model="plan.dimensions.lat" label="Latitude" type="number" step="any" />
                            <x-planner-field model="plan.dimensions.lng" label="Longitude" type="number" step="any" />
                            <x-planner-field model="plan.dimensions.width" label="Largeur en mètres" type="number" min="0.1" step="0.1" />
                            @if($plan['shape']==='rectangle')<x-planner-field model="plan.dimensions.height" label="Longueur en mètres" type="number" min="0.1" step="0.1" />@endif
                            @if(in_array($plan['shape'],['circle','regular_polygon']))<x-planner-field model="plan.dimensions.radius" label="Rayon en mètres" type="number" min="0.1" step="0.1" />@endif
                            @if($plan['shape']==='regular_polygon')<x-planner-field model="plan.dimensions.sides" label="Nombre de côtés" type="number" min="3" max="32" />@endif
                            <x-planner-field model="plan.dimensions.angle" label="Orientation en degrés" type="number" step="1" />
                        </div>
                    @else<p>Dessinez ou modifiez les sommets sur le plan, puis enregistrez cette fiche.</p>@endif
                    <details open><summary>Les 4P et la réussite</summary>
                        <x-planner-field model="plan.four_ps.product" label="Produit : fonction, besoin et promesse" type="textarea" />
                        <x-planner-field model="plan.four_ps.price" label="Prix : gratuité, tarifs, coûts et valeur" type="textarea" />
                        <x-planner-field model="plan.four_ps.place" label="Distribution : lieu, accès, horaires et parcours" type="textarea" />
                        <x-planner-field model="plan.four_ps.promotion" label="Promotion : message, public et action" type="textarea" />
                        <x-planner-field model="plan.success.objective" label="Contribution à la réussite" type="textarea" />
                        <x-planner-field model="plan.success.target" label="Cible et méthode de mesure" />
                        <x-planner-field model="plan.success.observed" label="Résultat constaté, si mesuré" />
                        <x-planner-field model="plan.success.evidence" label="Preuve du constat" />
                        <x-planner-field model="plan.is_essential" label="Indispensable au format de base" type="checkbox" />
                    </details>
                    <details><summary>Visite et fiche publique</summary>
                        <x-planner-field model="plan.is_public" label="Visible dans la prochaine publication" type="checkbox" />
                        <x-planner-field model="plan.public_content.description" label="Ce que l’on découvrira ici" type="textarea" />
                        <x-planner-field model="plan.public_content.story" label="Pourquoi venir découvrir cet élément" type="textarea" />
                        <x-planner-field model="plan.public_content.public_price" label="Prix ou gratuité à annoncer au visiteur" />
                        <x-planner-field model="plan.public_content.hours" label="Horaires publics" />
                        <x-planner-field model="plan.public_content.access" label="Accès et informations pratiques" type="textarea" />
                        <x-planner-field model="plan.tour_order" label="Ordre dans la visite" type="number" min="0" />
                    </details>
                    <details><summary>Ressources et workforce</summary>
                        <x-planner-field model="plan.operations.organization" label="QAPAS ou organisation prestataire" />
                        <x-planner-field model="plan.operations.responsible" label="Responsable du besoin" />
                        <x-planner-field model="plan.operations.resource_reference" label="Référence de la ressource partagée" />
                        <x-planner-field model="plan.operations.person_reference" label="Référence privée de la personne affectée" />
                        <x-planner-field model="plan.operations.shift_start" label="Début du poste" type="datetime-local" />
                        <x-planner-field model="plan.operations.shift_end" label="Fin du poste" type="datetime-local" />
                        <x-planner-field model="plan.operations.status" label="État" :options="['needed'=>'Besoin prévu','reserved'=>'Ressource réservée','installed'=>'Installation réalisée']" />
                        <x-planner-field model="plan.operations.notes" label="Besoins et informations techniques" type="textarea" />
                    </details>
                    <div class="planner-actions"><button class="planner-button">Enregistrer</button>@if($elementId)<button type="button" class="planner-button secondary" wire:click="duplicateElement">Dupliquer</button><button type="button" class="planner-button secondary" wire:click="archiveElement">Retirer du brouillon</button>@endif</div>
                </form>
            </aside>
        </div>
    @elseif(in_array($module,['costs','revenues']))
        <div class="planner-two-columns">
            <section class="planner-card"><h2>{{ $module==='costs'?'Coûts du scénario':'Prévisions et recettes acquises' }}</h2>
                <p>Les montants manquants restent à chiffrer. Une prévision n’est pas un encaissement.</p>
                <div class="planner-table-wrap"><table class="planner-table"><thead><tr><th>Objet et ligne</th><th>Montant TTC</th><th>Montant net de gestion</th><th>État</th><th></th></tr></thead><tbody>
                @foreach($lines->where('kind',$module==='costs'?'cost':'revenue') as $line)<tr><td>{{ $line->element?->name??'Commun' }}<br>{{ $line->label }}<small>{{ $line->source_reference??'Source à documenter' }}</small></td><td>{{ $money::display($line->gross_cents) }}</td><td>{{ $money::display($line->net_cents) }}</td><td>{{ $line->status }}<br>{{ $line->cost_class }}</td><td><button wire:click="editCost({{ $line->id }})">Modifier</button></td></tr>@endforeach
                </tbody></table></div>
            </section>
            <section class="planner-card"><h2>Renseigner une ligne</h2><form wire:submit="saveCost">
                <x-planner-field model="cost.label" label="Libellé" />
                <x-planner-field model="cost.element_id" label="Élément du plan" :options="$elementOptions" />
                <x-planner-field model="cost.kind" label="Nature" :options="['cost'=>'Coût','revenue'=>'Recette']" />
                <x-planner-field model="cost.cost_class" label="Traitement du coût" :options="['operating'=>'Charge de l’événement','investment'=>'Investissement durable','inventory'=>'Approvisionnement du stock']" />
                <x-planner-field model="cost.gross" label="Montant total TTC en euros, vide si inconnu" inputmode="decimal" />
                <x-planner-field model="cost.net" label="Montant net de gestion en euros, fiscalité vérifiée" inputmode="decimal" />
                <x-planner-field model="cost.status" label="État" :options="['forecast'=>'Prévision','committed'=>'Engagement','invoiced'=>'Facturé','paid'=>'Réglé ou encaissé et vérifié','cancelled'=>'Prévision abandonnée']" />
                <x-planner-field model="cost.source_reference" label="Devis, facture ou preuve du montant" />
                <x-planner-field model="cost.essential" label="Indispensable au format de base" type="checkbox" />
                @if($module==='revenues')<x-planner-field model="cost.earmarked" label="Fonds affectés à un usage spécifique" type="checkbox" />@endif
                <button class="planner-button">Enregistrer la ligne</button>
            </form></section>
        </div>
        @if($module==='revenues')@include('filament.pages.event-offers')@endif
    @elseif($module==='balance')
        <section class="planner-card"><h2>Comparer les formats</h2><table class="planner-table"><thead><tr><th>Scénario</th><th>Charges chiffrées</th><th>Recettes acquises</th><th>Coûts à vérifier</th></tr></thead><tbody>@foreach($comparison as $variant)<tr><td>{{ $variant['scenario']->name }}{{ $variant['scenario']->is_base?' · Base':'' }}</td><td>{{ $money::display($variant['finance']['operating_cents']) }}</td><td>{{ $money::display($variant['finance']['acquired_revenue_cents']) }}</td><td>{{ $variant['finance']['unknown_costs'] }}</td></tr>@endforeach</tbody></table>@if(!$scenario->is_base&&$event->decision==='pending')<button class="planner-button" wire:click="setBaseScenario">Retenir ce scénario comme format de base</button>@endif</section>
        <div class="planner-metrics">
            @foreach(['operating_cents'=>'Charges chiffrées','forecast_revenue_cents'=>'Recettes prévues connues','acquired_revenue_cents'=>'Recettes acquises affectables','base_result_cents'=>'Résultat sur recettes acquises','investment_cents'=>'Investissements durables','reserved_cash_cents'=>'Fonds réservés aux clients','available_cash_cents'=>'Trésorerie mobilisable calculée','target_profit_cents'=>'Bénéfice cible'] as $key=>$label)<div class="planner-card"><span>{{ $label }}</span><strong>{{ $money::display($finance[$key]) }}</strong></div>@endforeach
        </div>
        <p class="planner-notice">Ces totaux portent uniquement sur {{ $scenario->name }}. Les montants inconnus restent exclus des sommes connues et bloquent la confirmation lorsqu’ils sont nécessaires. L’apport QAPAS finance la trésorerie ; il ne devient pas une recette.</p>
        <div class="planner-two-columns">
            <section class="planner-card"><h2>Dossier de confirmation</h2><form wire:submit="saveReadiness">
                @foreach(config('event_planner.prerequisites') as $key=>$label)<x-planner-field :model="'readiness.prerequisites.'.$key" :label="$label" type="checkbox" />@endforeach
                <x-planner-field model="readiness.reference" label="Référence du dossier et des preuves" />
                <x-planner-field model="readiness.contingency" label="Réserve d’aléas en euros" inputmode="decimal" />
                <x-planner-field model="readiness.target_profit" label="Bénéfice cible en euros" inputmode="decimal" />
                <x-planner-field model="readiness.own_cash" label="Financement propre QAPAS vérifié en euros" inputmode="decimal" />
                <x-planner-field model="readiness.profit_required" label="Le bénéfice cible est aussi une condition de lancement" type="checkbox" />
                <button class="planner-button">Enregistrer le dossier</button>
            </form></section>
            <section class="planner-card"><h2>Décision et communication</h2>
                @if($finance['confirmation_blockers'])<ul>@foreach($finance['confirmation_blockers'] as $blocker)<li>{{ $blocker }}</li>@endforeach</ul>@else<p>Les contrôles du dossier permettent de décider. La décision de l’organisateur reste explicite.</p>@endif
                <div class="planner-actions"><button wire:click="confirmEvent" class="planner-button" @disabled(!$finance['can_confirm']||$event->decision!=='pending')>Confirmer le format de base</button><button wire:click="rejectEvent" wire:confirm="Enregistrer la non-confirmation et préparer les remboursements ?" class="planner-button secondary" @disabled($event->decision!=='pending')>Ne pas confirmer</button></div>
                <p>La signature de la première précommande fixe l’échéance commune. Les commandes suivantes gardent cette même date.</p>
                <div class="planner-actions"><button wire:click="changePhase('teaser')" class="planner-button secondary">Préparer le teaser</button><button wire:click="changePhase('applications')" class="planner-button secondary">Ouvrir les candidatures</button><button wire:click="publishPlan" class="planner-button">Publier le plan et les supports</button></div>
            </section>
        </div>
        @include('filament.pages.event-reviews')
    @elseif($module==='communications')
        @include('filament.pages.event-communications')
    @elseif($module==='products')
        @include('filament.pages.event-products')
    @endif
    @endif
</x-filament-panels::page>
