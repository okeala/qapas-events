<x-filament-panels::page>
 @php($scenario = $this->scenario())
 @php($costing = $this->costing())
 <x-filament::section>
  <label for="costing-scenario" class="font-semibold">Scénario à chiffrer</label>
  <select id="costing-scenario" wire:model.live="scenarioId" class="mt-2 w-full rounded-lg border border-gray-300 bg-white p-3 text-gray-900 dark:bg-gray-900 dark:text-white">
   <option value="">Choisir un scénario</option>
   @foreach($this->scenarios() as $option)
    <option value="{{ $option->public_id }}">{{ $option->eventProject->name }} · {{ $option->name }}</option>
   @endforeach
  </select>
  <p class="mt-3 text-sm">Un scénario à la fois. Les quantités × prix unitaires alimentent les sous-totaux. Une option à quantité zéro reste inactive. Les cautions et fonds affectés ne sont pas des recettes libres.</p>
 </x-filament::section>
 @if($scenario && $costing)
  @php($report = $costing['report'])
  <x-filament::section heading="Prévision de travail">
   <div class="grid gap-4 md:grid-cols-3">
    <div><p>Recettes prévues TTC</p><strong>{{ \App\Domain\Finance\Money::format($costing['gross_revenue_cents']) }}</strong><p class="text-sm">Hors engagements et encaissements réels.</p></div>
    <div><p>Coûts chiffrés TTC</p><strong>{{ \App\Domain\Finance\Money::format($costing['gross_cost_cents']) }}</strong><p class="text-sm">Dont investissements : {{ \App\Domain\Finance\Money::format($costing['investment_cents']) }}.</p></div>
    <div><p>Solde prévisionnel après IVA et imprévus</p><strong>{{ \App\Domain\Finance\Money::format($report['forecast_margin_cents']) }}</strong><p class="text-sm">{{ $report['complete'] ? 'Périmètre financier renseigné.' : 'Partiel : les inconnues restent à ajouter.' }}</p></div>
   </div>
   <p class="mt-4">Décaissements et réserves déjà chiffrés : <strong>{{ \App\Domain\Finance\Money::format($costing['known_outlay_cents']) }}</strong>. Inclut le coût complet de rémunération seulement s’il est renseigné. La réutilisation future du mobilier ne réduit pas ce besoin initial.</p>
   <p class="mt-2">Coût mensuel de rémunération avec charges : <strong>{{ $scenario->organizer_full_monthly_cents === null ? 'À chiffrer' : \App\Domain\Finance\Money::format($scenario->organizer_full_monthly_cents) }}</strong> · objectif QAPAS distinct : {{ \App\Domain\Finance\Money::format($report['target_cents']) }}.</p>
   <p class="mt-2">Préfinancement rapproché hors ventes sur place, net d’IVA : <strong>{{ \App\Domain\Finance\Money::format($report['verified_net_cents']) }}</strong>. {{ $report['launch_ready'] ? 'Financement de lancement validé.' : 'Lancement non débloqué.' }}</p>
   <a class="mt-4 inline-block font-semibold text-primary-600" href="{{ \App\Filament\Resources\ScenarioResource::getUrl('edit', ['record'=>$scenario]) }}">Modifier le scénario et ses tableaux Coûts / Recettes →</a>
   <details class="mt-4"><summary>Hypothèses du scénario</summary><p class="mt-2 whitespace-pre-line">{{ $scenario->assumptions }}</p></details>
  </x-filament::section>
  <x-filament::section heading="Stands et services : contribution après coûts directs">
   <p class="mb-4 text-sm">Contribution = recettes prévues hors IVA − coûts retenus. La TVA déductible n’est retranchée des coûts que lorsqu’elle est confirmée. Les frais communs, investissements et épreuves restent à financer après la contribution des stands.</p>
   <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr class="border-b"><th class="p-3">Unité</th><th class="p-3">Recettes TTC</th><th class="p-3">Recettes hors IVA</th><th class="p-3">Coûts retenus</th><th class="p-3">Contribution</th><th class="p-3">Fiabilité</th></tr></thead><tbody>
    @foreach($costing['groups'] as $group)
     <tr class="border-b"><td class="p-3">@if($group['stand'])<a class="text-primary-600" href="{{ \App\Filament\Resources\StandResource::getUrl('edit',['record'=>$group['stand']]) }}">{{ $group['name'] }} →</a>@else{{ $group['name'] }}@endif</td><td class="p-3">{{ \App\Domain\Finance\Money::format($group['gross_revenue']) }}</td><td class="p-3">{{ \App\Domain\Finance\Money::format($group['revenue']) }}</td><td class="p-3">{{ \App\Domain\Finance\Money::format($group['cost']) }}</td><td class="p-3 font-semibold">{{ \App\Domain\Finance\Money::format($group['revenue']-$group['cost']) }}</td><td class="p-3">{{ $group['uncertain'] ? 'À confirmer / incomplet' : 'Prix renseignés' }}</td></tr>
    @endforeach
   </tbody></table></div>
  </x-filament::section>
  <x-filament::section heading="Épreuves : nomenclatures comptées une seule fois">
   <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr class="border-b"><th class="p-3">Épreuve</th><th class="p-3">Passages</th><th class="p-3">Coût TTC</th><th class="p-3">État</th></tr></thead><tbody>
    @foreach($costing['activities'] as $item)
     <tr class="border-b"><td class="p-3"><a class="text-primary-600" href="{{ \App\Filament\Resources\ActivityResource::getUrl('edit',['record'=>$item['activity']]) }}">{{ $item['activity']->name }} →</a></td><td class="p-3">{{ $item['activity']->planned_runs }}</td><td class="p-3">{{ \App\Domain\Finance\Money::format($item['cost']['gross_cents']) }}</td><td class="p-3">{{ $item['cost']['complete'] ? 'Renseigné' : 'Inventaire / prix à confirmer' }}</td></tr>
    @endforeach
   </tbody></table></div>
   <p class="mt-3 text-sm">La captation mutualisée figure dans les frais communs. Les quantités « par passage » se multiplient par les passages, les quantités fixes restent pour l’épreuve entière. Durée de location et nombre de passages sont à revoir si le programme change.</p>
   <p class="mt-2">Besoins des équipements du plan inclus dans ce scénario : {{ \App\Domain\Finance\Money::format($report['site_gross_cents']) }} TTC.</p>
  </x-filament::section>
  <x-filament::section heading="Ce qui manque pour décider">
   <p>Les postes sans montant ne valent pas zéro. Les hypothèses, références publiques et devis non validés empêchent une décision de lancement même si le solde prévisionnel est positif.</p>
   <details class="mt-3" open><summary>Montants encore inconnus ({{ count($costing['missing_prices']) }})</summary><ul class="mt-2 list-disc pl-6">
    @foreach($costing['missing_prices'] as $missing)
     <li>{{ $missing }}</li>
    @endforeach
   </ul></details>
   <details class="mt-3"><summary>Validations et compléments ({{ count($report['missing']) }})</summary><ul class="mt-2 list-disc pl-6">
    @foreach(array_unique($report['missing']) as $missing)
     <li>{{ $missing }}</li>
    @endforeach
   </ul></details>
  </x-filament::section>
 @endif
</x-filament-panels::page>
