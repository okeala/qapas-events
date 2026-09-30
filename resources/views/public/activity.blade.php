<x-layout :title="$activity->name.' · '.$project->name">
 <section class="event-hero event-hero--small"><div><p class="eyebrow">{{ __('events.track_'.$activity->track) }} · {{ __('events.status_'.$activity->status) }}</p><h1>{{ $activity->name }}</h1><p class="lead">{{ $activity->summary }}</p><a class="text-link" href="{{ route('event.show',['project'=>$project->slug]) }}">← {{ $project->name }}</a></div></section>
 <div class="event-content">
 <p class="notice">{{ __('events.activity_notice') }}</p>
 <div class="event-grid"><section class="event-card"><h2>{{ __('events.rules') }}</h2><p class="preserve-lines">{{ $activity->rules }}</p></section>
 <section class="event-card"><h2>{{ __('events.scoring') }}</h2><p class="preserve-lines">{{ $activity->scoring ?: __('events.not_confirmed') }}</p><h3>{{ __('events.who_plays') }}</h3><p>{{ __('events.access_'.$activity->access) }}</p><p class="preserve-lines">{{ $activity->operator_requirements }}</p>@if($activity->broadcast_planned)<p>{{ __('events.broadcast') }}</p>@endif</section></div>
 <x-event-plan :plan="$plan" />
 <section class="event-section"><h2>{{ __('events.join_activity') }}</h2><p>{{ __('events.propose_activity') }}</p><a class="qapas-button" href="{{ route('event.show',['project'=>$project->slug]) }}#interest">{{ __('events.interest') }} →</a></section>
 </div>
</x-layout>
