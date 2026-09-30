<x-filament-panels::page>
 <div class="space-y-6">
 <x-filament::section heading="Grandir au rythme des engagements">
  <p>Une demande ne vaut ni vente ni encaissement. Comparez les formats, documentez les coûts, puis engagez le palier adapté.</p>
  <p>Les montants du simulateur sont saisis manuellement, en centimes TTC. Ils ne constituent pas une comptabilité ni un rapprochement bancaire.</p>
 </x-filament::section>
 @forelse($this->projects() as $project)
 <x-filament::section :heading="$project->name">
  <p>{{ $project->phase }} · {{ $project->interests_count }} demandes · {{ $project->teams_count }} équipes en préparation</p>
  <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3 mt-4">
  @foreach($project->scenarios as $scenario)
   @php($report=$scenario->report())
   <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 space-y-2">
    <h3 class="font-bold">{{ $scenario->name }}</h3>
    <p>{{ $scenario->team_target }} équipes · {{ $scenario->stand_target }} stands · {{ $scenario->months }} mois de travail</p>
    @if(!$report['complete'])
     <p class="font-semibold text-amber-700">Chiffrage incomplet — décision impossible</p>
     <ul class="list-disc ps-5">@foreach($report['missing'] as $missing)<li>{{ $missing }}</li>@endforeach</ul>
    @else
     <p class="font-semibold">{{ $report['target_secured'] ? 'Objectif couvert par les engagements saisis' : 'Objectif encore à couvrir' }}</p>
    @endif
    <dl class="space-y-2">
     <div><dt>Résultat prévu après rémunération complète</dt><dd class="font-bold">{{ \App\Domain\Finance\Money::format($report['forecast_margin_cents']) }}</dd></div>
     <div><dt>Engagements moins tous les coûts prévus</dt><dd>{{ \App\Domain\Finance\Money::format($report['secured_margin_cents']) }}</dd></div>
     <div><dt>Manque pour l’équilibre</dt><dd>{{ \App\Domain\Finance\Money::format($report['break_even_gap_cents']) }}</dd></div>
     <div><dt>Manque pour l’objectif QAPAS</dt><dd>{{ \App\Domain\Finance\Money::format($report['target_gap_cents']) }}</dd></div>
     <div><dt>Trésorerie indicative après IVA et coûts restants</dt><dd>{{ \App\Domain\Finance\Money::format($report['cash_after_reserves_cents']) }}</dd></div>
     <div><dt>Flux affectés / cautions / tiers exclus</dt><dd>{{ \App\Domain\Finance\Money::format($report['restricted_receipts_cents']) }}</dd></div>
    </dl>
   </div>
  @endforeach
  </div>
  <div class="mt-5 space-y-4">@foreach($project->scenarios->where('launch_model',true) as $launch)
  @php($lr=$launch->report())
  <x-filament::section :heading="$launch->name.' · contribution et préfinancement'">
   <p>La contribution d’un indépendant est le prix hors IVA dû à QAPAS moins les coûts directs de l’offre fournie. Les marchandises, salariés et recettes de l’exposant ne sont pas des coûts QAPAS. Aucune commission sur ses ventes.</p>
   <p class="mt-2">Les encaissements rapprochés ici sont vérifiés manuellement par un administrateur avec référence du justificatif. Aucun prestataire de paiement n’est connecté.</p>
   <dl class="grid gap-3 md:grid-cols-2 mt-3"><div><dt>Encaissements rapprochés hors IVA (hors bar / frites)</dt><dd>{{ \App\Domain\Finance\Money::format($lr['verified_net_cents']) }}</dd></div><div><dt>Avances personnelles restant à rembourser (déjà incluses aux coûts)</dt><dd>{{ \App\Domain\Finance\Money::format($lr['advance_due_cents']) }}</dd></div><div><dt>Résultat couvert après coûts, rémunération et imprévus</dt><dd>{{ \App\Domain\Finance\Money::format($lr['prepaid_margin_cents']) }}</dd></div><div><dt>Solde prudent après coûts TTC et réserves</dt><dd>{{ \App\Domain\Finance\Money::format($lr['prepaid_cash_cents']) }}</dd></div></dl>
   <p class="font-bold mt-3">{{ $lr['launch_ready']?'Socle préfinancé selon les justificatifs saisis':'Socle à compléter / financer' }} · {{ $lr['expansion_ready']?'Objectif QAPAS préservé : palier suivant à étudier':'Agrandissement non débloqué' }}</p>
   <div class="overflow-x-auto mt-3"><table class="w-full text-sm"><thead><tr><th class="text-left p-2">Unité stand</th><th>Contribution prévue</th><th>Contribution engagée</th><th>Contribution rapprochée</th></tr></thead><tbody>@foreach($lr['stands'] as $row)<tr><td class="p-2">{{ $row['name'] }} {{ $row['complete']?'':'· incomplet' }}</td><td>{{ \App\Domain\Finance\Money::format($row['forecast_cents']) }}</td><td>{{ \App\Domain\Finance\Money::format($row['committed_cents']) }}</td><td>{{ \App\Domain\Finance\Money::format($row['verified_cents']) }}</td></tr>@endforeach</tbody></table></div>
   <p class="mt-3">{{ $launch->event_days?:'?' }} jour(s) · au moins {{ $launch->minimum_daily_activities }} épreuves distinctes / jour. Le ratio de public par stand est une hypothèse ; la capacité réglementaire et les circulations se vérifient séparément.</p>
  </x-filament::section>@endforeach</div>
  <details class="mt-5"><summary class="cursor-pointer font-semibold">Conditions avant ouverture des ventes</summary><ul class="list-disc ps-5">@foreach(app(\App\Domain\Planning\Readiness::class)->blockers($project,'sales') as $blocker)<li>{{ $blocker }}</li>@endforeach</ul></details>
  <details class="mt-3"><summary class="cursor-pointer font-semibold">Conditions avant exploitation</summary><ul class="list-disc ps-5">@forelse(app(\App\Domain\Planning\Readiness::class)->blockers($project,'live') as $blocker)<li>{{ $blocker }}</li>@empty<li>Conditions internes documentées. La décision reste sous la responsabilité de l’organisateur.</li>@endforelse</ul></details>
 </x-filament::section>
 @empty <p>Créez une édition pour ouvrir son atelier de préparation.</p> @endforelse
 <p><a href="{{ route('home') }}">Voir la présentation publique →</a></p>
 </div>
</x-filament-panels::page>
