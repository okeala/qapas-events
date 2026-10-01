<x-filament-panels::page>
<label class="block font-semibold">Scénario étudié<select class="block rounded-lg border p-3 bg-white text-gray-900 dark:bg-gray-900 dark:text-white" wire:model.live="scenarioId">@foreach($this->scenarios() as $scenario)<option value="{{ $scenario->public_id }}">{{ $scenario->eventProject->name }} · {{ $scenario->name }}</option>@endforeach</select></label>
@if($this->plan())
@livewire('financial-overview',['scenarioId'=>$scenarioId],key('finance-'.$scenarioId.'-'.$revision))
<x-filament::section heading="Saisir les prix au même endroit">
<p>Filtrez « Fixe » pour les frais de structure. Les quantités variables viennent des postes ajoutés, des passages d’épreuve, des jours ou du multiplicateur choisi. Le prix est celui de la fiche source : aucune copie de coût. Les graphiques portent toujours sur tout le scénario.</p>
<p><a href="{{ \App\Filament\Resources\ScenarioResource::getUrl('index',['tableSearch'=>$this->plan()->scenario->name]) }}">Coût du porteur, jours, réserves et unités incluses →</a> · <a href="{{ \App\Filament\Resources\CommercialPlanResource::getUrl() }}">Tarifs des offres collectives et indépendantes →</a> · <a href="{{ \App\Filament\Resources\BudgetLineResource::getUrl() }}">Ajouter un poste budgétaire →</a></p>
</x-filament::section>
{{ $this->table }}
@else<p>Créer un scénario actif pour commencer le chiffrage.</p>@endif
</x-filament-panels::page>
