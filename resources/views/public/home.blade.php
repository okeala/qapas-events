<x-layout>
 <section class="event-hero"><div class="hero-copy"><p class="eyebrow">{{ __('events.eyebrow') }}</p><h1>{{ __('events.headline') }}</h1><p class="lead">{{ __('events.lead') }}</p><a class="qapas-button" href="#projects">{{ __('events.discover') }} <span>↘</span></a></div><div class="hero-seal" aria-hidden="true"><span>Q</span><small>FREGUESIAS<br>EM FESTA</small></div></section>
 <section class="event-content" id="projects"><p class="eyebrow">QAPAS EVENTS / 01</p><h2>{{ __('events.projects') }}</h2><div class="event-grid">
 @forelse($projects as $project)<article class="event-card"><p class="badge">{{ __('events.draft') }}</p><h3>{{ $project->name }}</h3><p>{{ __('events.lead') }}</p><a class="text-link" href="{{ route('event.show',['project'=>$project->slug]) }}">{{ __('events.discover') }} →</a></article>@empty<p>{{ __('events.no_projects') }}</p>@endforelse
 </div><div class="principles"><p>{{ __('events.free') }}</p><p>{{ __('events.election') }}</p><p>{{ __('events.official') }}</p></div></section>
</x-layout>
