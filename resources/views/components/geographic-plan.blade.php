@props(['plan'])
@if($plan && count($plan['geojson']['features']))
 @once @vite('resources/js/geographic-site.js') @endonce
 <section class="event-section"><h2>{{ __('events.geographic_plan') }}</h2><p>{{ __('events.plan_note') }}</p><div data-geographic-plan><script type="application/json" data-geo-json>@json($plan)</script><div data-geo-canvas class="event-map"></div><p data-map-status role="status"></p></div></section>
@endif
