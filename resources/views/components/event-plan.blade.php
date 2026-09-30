@props(['plan'])
@if($plan)
 @once @vite('resources/js/event-map.js') @endonce
 <section class="event-section"><h2>{{ __('events.plan') }}</h2><p>{{ __('events.plan_note') }}</p>
 <div data-event-plan><script type="application/json" data-plan-json>@json($plan)</script><div data-plan-canvas class="event-map" aria-label="{{ __('events.plan') }}"></div></div>
 <ul class="plan-legend">@foreach($plan['terraces'] as $terrace)<li>{{ $terrace['name'] }} · {{ __('events.access_'.$terrace['access']) }}</li>@endforeach</ul>
 </section>
@endif
