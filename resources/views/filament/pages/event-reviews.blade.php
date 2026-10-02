<div class="planner-two-columns">
    <section class="planner-card"><h2>4P, preuves et décisions</h2><table class="planner-table"><thead><tr><th>Niveau et public</th><th>Hypothèse et mesure</th><th>Preuve</th><th>Prochaine action</th></tr></thead><tbody>@foreach($reviews as $r)<tr><td>{{ $r->name }}<small>{{ $r->level }} · {{ $audiences[$r->audience]??$r->audience }}</small></td><td>{{ $r->hypothesis }}<br>{{ $r->indicator }} : {{ $r->observed??'Inconnu' }} / {{ $r->target??'Cible à définir' }} {{ $r->unit }}</td><td>{{ $r->evidence_kind }}<br>{{ $r->evidence_reference }}</td><td>{{ $r->next_action }}<small>{{ $r->owner }} · {{ $r->decision }}</small></td></tr>@endforeach</tbody></table></section>
    <section class="planner-card"><h2>Documenter une validation</h2><form wire:submit="saveReview">
        <x-planner-field model="review.name" label="Objet de la validation" /><x-planner-field model="review.level" label="Niveau" :options="['organization'=>'Organisation','event'=>'Événement','scenario'=>'Scénario','zone'=>'Zone','element'=>'Élément','offer'=>'Offre','channel'=>'Canal']" />
        <x-planner-field model="review.audience" label="Public" :options="$audiences" />
        <x-planner-field model="review.element_id" label="Lieu évalué, si concerné" :options="$elementOptions" /><x-planner-field model="review.offer_id" label="Offre évaluée, si concernée" :options="[''=>'Choisir']+$offers->pluck('name','id')->all()" />
        @foreach(['product'=>'Produit','price'=>'Prix','place'=>'Distribution','promotion'=>'Promotion'] as $key=>$label)<x-planner-field :model="'review.'.$key" :label="$label" type="textarea" />@endforeach
        <x-planner-field model="review.hypothesis" label="Hypothèse à vérifier" type="textarea" /><x-planner-field model="review.indicator" label="Indicateur et méthode" /><x-planner-field model="review.unit" label="Unité" />
        <x-planner-field model="review.target" label="Cible" /><x-planner-field model="review.observed" label="Résultat, laisser vide si inconnu" />
        <x-planner-field model="review.evidence_kind" label="Nature de l’information" :options="['hypothesis'=>'Hypothèse','observed'=>'Constat documenté','simulated'=>'Simulation']" />
        <x-planner-field model="review.evidence_reference" label="Source datée et portée de la preuve" type="textarea" />
        <x-planner-field model="review.owner" label="Responsable" /><x-planner-field model="review.due_at" label="Échéance" type="datetime-local" /><x-planner-field model="review.next_action" label="Prochaine action" type="textarea" />
        <x-planner-field model="review.decision" label="Décision" :options="['keep'=>'Conserver','improve'=>'Améliorer','move'=>'Déplacer','expand'=>'Développer','remove'=>'Retirer']" /><button class="planner-button">Enregistrer la validation</button>
    </form></section>
</div>
