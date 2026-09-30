<x-filament-panels::page>
 @vite('resources/js/event-map.js')
 <div class="space-y-5">
  <label class="block">Édition <select wire:model.live="projectId" class="rounded-lg border p-2 dark:bg-gray-900">@foreach(\App\Models\EventProject::orderBy('name')->get() as $edition)<option value="{{ $edition->public_id }}">{{ $edition->name }}</option>@endforeach</select></label>
  @if($errors->any())<div role="alert" class="rounded-lg bg-red-50 text-red-900 p-4">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
  @if($project=$this->project())
   <x-filament::section heading="Fond de plan de la Quinta">
    <p>Importez un plan dont vous avez le droit d’usage, en PNG, JPEG ou WebP. Les positions sont relatives à cette image, sans valeur de bornage. Le plan reste privé jusqu’à sa publication dans l’édition.</p>
    @if(!$project->terraces()->exists())<form wire:submit="uploadPlan" class="mt-3 flex flex-wrap gap-3"><input type="file" wire:model="planUpload" accept="image/png,image/jpeg,image/webp"><x-filament::button type="submit" wire:loading.attr="disabled">Enregistrer le plan</x-filament::button></form>@endif
   </x-filament::section>
   @if($plan=app(\App\Domain\Planning\SitePlan::class)->data($project,true))
    <section data-plan-editor wire:key="plan-{{ $project->public_id }}-{{ $revision }}" class="space-y-3" x-data x-init="$nextTick(() => window.dispatchEvent(new Event('qapas-map-mount')))">
     <div class="flex flex-wrap gap-3 items-end">
      <label>Terrasse<select data-terrace class="block rounded border p-2 dark:bg-gray-900"><option value="">Nouvelle terrasse</option>@foreach($project->terraces as $t)<option value="{{ $t->public_id }}">{{ $t->name }}</option>@endforeach</select></label>
      <label>Nom<input data-terrace-name maxlength="120" class="block rounded border p-2 dark:bg-gray-900"></label>
      <x-filament::button type="button" data-draw>Tracer la terrasse</x-filament::button>
      <x-filament::button type="button" data-undo color="gray">Annuler le dernier point</x-filament::button>
      <x-filament::button type="button" data-save>Enregistrer le tracé</x-filament::button>
     </div>
     <div class="flex flex-wrap gap-3 items-end"><label>Épreuve<select data-activity class="block rounded border p-2 dark:bg-gray-900"><option value="">Choisir une épreuve</option>@foreach($project->activities()->where('status','!=','archived')->orderBy('sort_order')->get() as $a)<option value="{{ $a->public_id }}">{{ $a->name }}</option>@endforeach</select></label><x-filament::button type="button" data-place>Placer dans la terrasse choisie</x-filament::button></div>
     <p data-plan-status role="status">Tracez les sommets par clics. Pour placer une épreuve, choisissez sa terrasse, cliquez « Placer », puis cliquez dans cette terrasse.</p>
     <p class="text-sm">Une modification de placement remet la revue de sécurité de l’épreuve à vérifier. Visibilité et accès des terrasses : menu « Terrasses ».</p>
     <div data-event-plan wire:ignore><script type="application/json" data-plan-json>@json($plan)</script><div data-plan-canvas style="height:540px;max-height:70vh;border-radius:12px;z-index:0" aria-label="Plan interactif des terrasses"></div></div>
    </section>
   @else<p>Aucun fond de plan importé. Aucune position réelle n’est inventée.</p>@endif
  @else<p>Créez d’abord une édition.</p>@endif
 </div>
</x-filament-panels::page>
