<section class="planner-card"><h2>Les trois communications</h2><p>La phase courante du plan publié alimente la visite et l’affiche. Les campagnes ci-dessous conservent leurs propres versions et preuves de livraison.</p>
    @if($event->published_revision_id)<div class="planner-actions"><a class="planner-button" href="{{ route('events.poster',$event->slug) }}" target="_blank" rel="noopener">Afficher l’affiche courante</a><a class="planner-button secondary" href="{{ route('events.show',$event->slug) }}#tour">Voir la visite guidée</a></div>@endif
</section>
<div class="planner-two-columns">
    <section class="planner-card"><h2>Campagnes et prestations</h2>@foreach($campaigns as $c)<article class="planner-record"><h3>{{ $c->name }}</h3><p>{{ config('event_planner.phases.'.$c->phase) }} · {{ $c->audience }} · {{ $c->channel }}</p><p>{{ $c->message }}</p><small>{{ $money::display($c->budget_cents) }} · {{ $c->state }} · {{ $c->delivery_reference }}</small><button class="planner-button secondary" wire:click="editCampaign({{ $c->id }})">Ouvrir le dossier</button></article>@endforeach</section>
    <section class="planner-card"><h2>Préparer une campagne</h2><form wire:submit="saveCampaign">
        <x-planner-field model="campaign.name" label="Campagne" /><x-planner-field model="campaign.audience" label="Public" :options="$audiences" /><x-planner-field model="campaign.channel" label="Canal" />
        <x-planner-field model="campaign.phase" label="Phase" :options="config('event_planner.phases')" /><x-planner-field model="campaign.message" label="Message" type="textarea" /><x-planner-field model="campaign.call_to_action" label="Action proposée" />
        <x-planner-field model="campaign.budget" label="Budget en euros" inputmode="decimal" /><x-planner-field model="campaign.owner" label="Responsable" /><x-planner-field model="campaign.due_at" label="Échéance" type="datetime-local" />
        <x-planner-field model="campaign.state" label="État" :options="['draft'=>'Brouillon','approved'=>'Approuvé','delivered'=>'Livraison attestée']" /><x-planner-field model="campaign.delivery_reference" label="Preuve de livraison" /><button class="planner-button">Enregistrer la campagne</button>
    </form></section>
</div>
<div class="planner-two-columns">
    <section class="planner-card"><h2>Suivi commercial</h2><p>Qualifier, proposer, suivre l’accord, livrer et recueillir le retour. Un état de dossier ne remplace pas une signature ou un encaissement.</p><table class="planner-table"><thead><tr><th>Contact</th><th>Étape</th><th>Prochaine action</th><th></th></tr></thead><tbody>@foreach($leads as $l)<tr><td>{{ $l->name }}<small>{{ $l->email }} · {{ $l->audience }}</small></td><td>{{ $l->stage }}</td><td>{{ $l->next_action }}<small>{{ $l->owner }}</small></td><td><button wire:click="editLead({{ $l->id }})">Ouvrir</button></td></tr>@endforeach</tbody></table></section>
    <section class="planner-card"><h2>Dossier et prochaine action</h2><form wire:submit="saveLead">
        <x-planner-field model="lead.name" label="Nom" /><x-planner-field model="lead.email" label="Courriel" type="email" /><x-planner-field model="lead.audience" label="Public" :options="$audiences" /><x-planner-field model="lead.source" label="Origine du contact" />
        <x-planner-field model="lead.stage" label="Étape" :options="['contact'=>'Contact','qualified'=>'Qualifié','proposal'=>'Proposition','conditional'=>'Accord conditionnel à documenter','confirmed'=>'Accord du contact','delivered'=>'Prestation livrée','feedback'=>'Retour client','declined'=>'Refus']" />
        <x-planner-field model="lead.owner" label="Responsable" /><x-planner-field model="lead.due_at" label="Échéance" type="datetime-local" /><x-planner-field model="lead.next_action" label="Prochaine action" type="textarea" /><x-planner-field model="lead.notes" label="Notes privées, motif d’un refus et preuves" type="textarea" /><button class="planner-button">Enregistrer le dossier</button>
    </form></section>
</div>
@include('filament.pages.event-reviews')
<section class="planner-card"><h2>Textes de la visite et des trois affiches</h2><p>Les textes sont révisés par langue. La visite, la FAQ et l’affiche utilisent la même publication du plan.</p>
    <label class="planner-field"><span>Langue à réviser</span><select wire:model.live="editorial.locale" wire:change="loadEditorial">@foreach(config('qapas_application.locales') as $locale)<option value="{{ $locale }}">{{ strtoupper($locale) }}</option>@endforeach</select></label>
    <form wire:submit="saveEditorial"><x-planner-field model="editorial.description" label="Promesse et présentation de l’événement" type="textarea" />
        @foreach(config('event_planner.phases') as $phase=>$label)<details><summary>{{ $label }}</summary><x-planner-field :model="'editorial.phases.'.$phase.'.title'" label="Titre de l’affiche et de la visite" /><x-planner-field :model="'editorial.phases.'.$phase.'.message'" label="Message" type="textarea" /></details>@endforeach
        <h3>Questions et réponses</h3>@foreach($editorial['faq'] as $i=>$item)<details wire:key="faq-{{ $editorial['locale'] }}-{{ $i }}"><summary>{{ $item['question']?:'Nouvelle question' }}</summary><x-planner-field :model="'editorial.faq.'.$i.'.question'" label="Question" /><x-planner-field :model="'editorial.faq.'.$i.'.answer'" label="Réponse" type="textarea" /></details>@endforeach
        <div class="planner-actions"><button type="button" class="planner-button secondary" wire:click="addFaq">Ajouter une question</button><button class="planner-button">Enregistrer les textes</button></div>
    </form>
</section>
