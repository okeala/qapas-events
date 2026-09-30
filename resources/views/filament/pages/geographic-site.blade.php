<x-filament-panels::page>
 @vite('resources/js/geographic-site.js')
 <label>Édition <select wire:model.live="projectId" class="rounded border p-2 dark:bg-gray-900">@foreach(\App\Models\EventProject::orderBy('name')->get() as $p)<option value="{{ $p->public_id }}">{{ $p->name }}</option>@endforeach</select></label>
 @if($errors->any())<div role="alert" class="bg-red-50 text-red-900 rounded p-4">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
 @if($project=$this->project())
 <x-filament::section heading="Importer ou tracer l’implantation">
  <p>Fond OSM et photographie aérienne DGT. Les mesures et accès doivent être vérifiés sur place. Les anciens plans sur image restent disponibles dans Préparer ; ils ne sont pas géoréférencés automatiquement.</p>
  <form wire:submit="previewImport" class="flex flex-wrap gap-3 mt-3"><input type="file" wire:model="importFile" accept=".geojson,.json,.kml"><x-filament::button type="submit">Prévisualiser l’import</x-filament::button><x-filament::button type="button" color="gray" wire:click="exportGeojson">Exporter GeoJSON</x-filament::button></form>
  @if($previewFeatures)<div class="mt-4"><p>{{ count($previewFeatures) }} objets à importer, privés par défaut :</p><ul>@foreach($previewFeatures as $f)<li>{{ $f['name'] }} · {{ $f['geometry']['type'] }}</li>@endforeach</ul><label>Calque <select wire:model="importCategory" class="border p-2 dark:bg-gray-900">@foreach(\App\Models\SiteFeature::CATEGORIES as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label><x-filament::button wire:click="commitImport">Confirmer l’import</x-filament::button></div>@endif
 </x-filament::section>
 <section data-geo-editor wire:key="geo-{{ $project->public_id }}-{{ $revision }}" x-data x-init="$nextTick(() => window.dispatchEvent(new Event('qapas-geo-mount')))">
  <div class="flex flex-wrap gap-3 mb-3">
   <label>Objet<select data-feature class="block border rounded p-2 dark:bg-gray-900"><option value="">Nouvel objet</option>@foreach($project->siteFeatures()->orderBy('name')->get() as $f)<option value="{{ $f->public_id }}">{{ $f->name }}</option>@endforeach</select></label>
   <label>Nom<input data-feature-name maxlength="120" class="block border rounded p-2 dark:bg-gray-900"></label>
   <label>Calque<select data-category class="block border rounded p-2 dark:bg-gray-900">@foreach(\App\Models\SiteFeature::CATEGORIES as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label>
   <label>Forme<select data-shape class="block border rounded p-2 dark:bg-gray-900"><option value="Polygon">Polygone</option><option value="LineString">Ligne / accès</option><option value="Point">Point</option></select></label>
  </div>
  <div class="flex flex-wrap gap-3 mb-3"><x-filament::button type="button" data-start>Tracer / remplacer le contour</x-filament::button><x-filament::button type="button" data-undo color="gray">Annuler le point</x-filament::button><x-filament::button type="button" data-save>Enregistrer l’objet</x-filament::button></div>
  <p data-geo-status role="status">Choisissez une forme, puis cliquez sur ses sommets. Le nouveau tracé remplace la géométrie de l’objet sélectionné.</p>
  <div data-geographic-plan wire:ignore><script type="application/json" data-geo-json>@json(app(\App\Domain\Planning\GeographicPlan::class)->data($project,true))</script><div data-geo-canvas style="height:580px;max-height:75vh;z-index:0"></div></div>
 </section>
 <p>Complétez les besoins et la visibilité dans « Éléments du site ». Affectez les épreuves à un ou plusieurs quartéis dans « Implantations des épreuves ». Les calques publics exigent aussi la publication du plan géographique dans l’édition.</p>
 @else<p>Créez une édition pour dessiner le site.</p>@endif
</x-filament-panels::page>
