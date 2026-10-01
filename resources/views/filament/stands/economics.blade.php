@if($report)
<div class="space-y-3">
<p><strong>{{ $report['scenario'] }}</strong> · Prévisions des postes saisis pour ce stand. Les frais communs restent dans le budget général.</p>
<table class="w-full text-left"><thead><tr><th>Budget</th><th>Coûts</th><th>Recettes</th><th>Solde des postes saisis</th></tr></thead><tbody>
@foreach([['holder'=>'QAPAS · base économique (IVA selon chaque ligne)']+$report['qapas'], ...array_map(fn($p)=>['holder'=>$p['holder'].' · hors QAPAS, TTC']+$p,$report['external'])] as $row)
<tr><th class="py-2">{{ $row['holder'] }}</th>@foreach(['cost_cents','revenue_cents','balance_cents'] as $key)<td>{{ $row[$key]===null?'À compléter':\App\Domain\Finance\Money::format($row[$key]) }}</td>@endforeach</tr>
@if($row['cost_missing']+$row['revenue_missing'])<tr><td colspan="4">{{ $row['cost_missing']+$row['revenue_missing'] }} poste(s) non chiffré(s). Sous-totaux connus : {{ \App\Domain\Finance\Money::format($row['cost_known_cents']) }} de coûts et {{ \App\Domain\Finance\Money::format($row['revenue_known_cents']) }} de recettes.</td></tr>@endif
@endforeach
</tbody></table>
<p>Le solde QAPAS contribue aux frais communs ; il ne prouve pas la rentabilité de l’événement. Aucun total ne mélange les différents intervenants. Les sommes affectées, cautions et flux tiers ne sont pas des recettes libres ({{ $report['restricted_count'] }} ligne(s) séparée(s)).</p>
</div>
@else<p>Inclure le stand dans le scénario de lancement pour afficher sa synthèse. Les onglets budgétaires permettent de travailler les autres scénarios.</p>@endif
