<div class="planner-two-columns">
    <section class="planner-card"><h2>Offres et capacités</h2><table class="planner-table"><thead><tr><th>Offre</th><th>Prix TTC</th><th>Disponibilité</th><th></th></tr></thead><tbody>@foreach($offers as $o)<tr><td>{{ $o->name }}<small>{{ $audiences[$o->audience]??$o->audience }} · version {{ $o->price_version }}</small></td><td>{{ $money::display($o->price_cents) }}</td><td>{{ $o->available()??'Sans plafond' }}</td><td><button wire:click="editOffer({{ $o->id }})">Modifier</button></td></tr>@endforeach</tbody></table></section>
    <section class="planner-card"><h2>Définir une offre</h2><form wire:submit="saveOffer">
        <x-planner-field model="offer.name" label="Nom" /><x-planner-field model="offer.audience" label="Public acheteur" :options="$audiences" />
        <x-planner-field model="offer.element_id" label="Implantation principale" :options="$elementOptions" />
        <x-planner-field model="offer.seller" label="Vendeur" :options="['organizer'=>'Organisateur','independent'=>'Exposant indépendant']" />
        <x-planner-field model="offer.price" label="Prix TTC en euros" inputmode="decimal" /><x-planner-field model="offer.net_price" label="Recette nette de gestion par unité, fiscalité vérifiée" inputmode="decimal" />
        <x-planner-field model="offer.delivery_cost" label="Coût additionnel net de livraison par unité en euros" inputmode="decimal" />
        <x-planner-field model="offer.delivery_gross" label="Coût additionnel TTC de livraison par unité en euros" inputmode="decimal" />
        <x-planner-field model="offer.delivery_reference" label="Devis de livraison ou justification d’absence de coût additionnel" />
        <p>Les coûts déjà inscrits sur l’élément restent dans son dossier. Saisir zéro et une justification lorsque la commande n’ajoute aucun coût ; un montant absent reste à chiffrer.</p>
        <details><summary>Les 4P de cette offre pour son public</summary>@foreach(['product'=>'Produit','price'=>'Prix et valeur','place'=>'Distribution et livraison','promotion'=>'Message et action'] as $key=>$label)<x-planner-field :model="'offer.four_ps.'.$key" :label="$label" type="textarea" />@endforeach</details>
        <x-planner-field model="offer.pool_id" label="Capacité partagée avec d’autres variantes" :options="[''=>'Créer une capacité ou laisser sans plafond']+$pools->pluck('name','id')->all()" />
        <x-planner-field model="offer.capacity" label="Nouvelle capacité, si nécessaire" type="number" min="1" />
        <x-planner-field model="offer.description" label="Promesse pour cet acheteur" type="textarea" /><x-planner-field model="offer.included" label="Contenu inclus et exclusions" type="textarea" /><x-planner-field model="offer.conditions" label="Conditions commerciales" type="textarea" />
        <x-planner-field model="offer.is_public" label="Présenter cette offre dans la prochaine publication" type="checkbox" /><button class="planner-button">Enregistrer l’offre</button>
    </form></section>
</div>
<section class="planner-card"><h2>Précommandes et remboursements</h2><p>Enregistrez uniquement une signature attestée. Vérifiez séparément l’encaissement bancaire. Les actions ci-dessous consignent des preuves ; elles n’effectuent aucun prélèvement ni virement.</p>
    <form wire:submit="signBooking" class="planner-form-grid">
        <x-planner-field model="booking.offer_id" label="Offre signée" :options="[''=>'Choisir']+$offers->where('seller','organizer')->pluck('name','id')->all()" />
        <x-planner-field model="booking.buyer_name" label="Signataire" /><x-planner-field model="booking.buyer_email" label="Courriel du client" type="email" />
        <x-planner-field model="booking.quantity" label="Quantité" type="number" min="1" /><x-planner-field model="booking.agreement_reference" label="Référence de la commande signée et de ses conditions" /><button class="planner-button">Enregistrer la signature</button>
    </form>
    <div class="planner-table-wrap"><table class="planner-table"><thead><tr><th>Client et offre</th><th>Montant TTC</th><th>Échéance</th><th>État et preuve</th></tr></thead><tbody>
        @foreach($bookings as $b)<tr><td>{{ $b->buyer_name }}<br>{{ $b->offer_snapshot['name'] }}</td><td>{{ $money::display($b->gross_cents) }}</td><td>{{ $b->deadline->format('d/m/Y H:i') }}</td><td>{{ $b->state }}
            @if($b->state==='signed')<div x-data="{reference:''}"><input x-model="reference" aria-label="Référence bancaire" placeholder="Référence bancaire vérifiée"><button @click="$wire.recordPayment({{ $b->id }},reference)">Consigner l’encaissement</button></div>@endif
            @if($b->refund && $b->refund->state!=='completed')<div x-data="{reference:''}"><input x-model="reference" aria-label="Preuve de remboursement" placeholder="Référence de restitution"><button @click="$wire.recordRefund({{ $b->refund->id }},reference,false)">Ordre transmis</button><button @click="$wire.recordRefund({{ $b->refund->id }},reference,true)">Restitution vérifiée</button></div>@endif
        </td></tr>@endforeach
    </tbody></table></div>
</section>
