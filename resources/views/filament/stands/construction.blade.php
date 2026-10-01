@if($costing)
<div class="space-y-2"><p><strong>{{ $costing['tube_count'] }} tubes · {{ number_format($costing['tube_length_mm']/1000,2,',',' ') }} m utiles</strong>, hors chutes et traits de coupe.</p>
<table class="w-full text-left"><thead><tr><th>Groupe</th><th>Référence neuf connue</th><th>Prévision connue</th></tr></thead><tbody>
@foreach($costing['groups'] as $key=>$group)<tr><th class="py-1">{{ \App\Domain\Stands\ConstructionCosting::GROUPS[$key] }}</th><td>{{ \App\Domain\Finance\Money::format($group['reference']) }}</td><td>{{ \App\Domain\Finance\Money::format($group['planned']) }}</td></tr>@endforeach
</tbody></table>
<p>Référence partielle : <strong>{{ \App\Domain\Finance\Money::format($costing['reference_known_cents']) }}</strong>, {{ $costing['reference_missing'] }} poste(s) sans référence. Les prix indicatifs initiaux n’ont pas de base IVA confirmée.</p>
<p>Coût prévu : <strong>{{ $costing['planned_cents']===null?'À compléter':\App\Domain\Finance\Money::format($costing['planned_cents']) }}</strong> ; {{ $costing['missing'] }} poste(s) restant à chiffrer. La casse, les dons et les prêts ne sont pas présumés gratuits. Quantité zéro = poste explicitement non retenu ici.</p></div>
@else<p>Compléter les champs pour calculer la nomenclature.</p>@endif
