<x-layout :title="$activity->name.' · '.$project->name">
 <section class="event-hero event-hero--small"><div><p class="eyebrow">{{ __('events.track_'.$activity->track) }} · {{ __('events.reveal_'.app(\App\Domain\Planning\Revelation::class)->label($activity)) }}</p><h1>{{ $activity->name }}</h1><p class="lead">{{ $activity->summary }}</p><a class="text-link" href="{{ route('event.show',['project'=>$project->slug]) }}">← {{ $project->name }}</a></div></section>
 <div class="event-content">
 <p class="notice">{{ __('events.activity_notice') }}</p>
 <div class="event-grid"><section class="event-card"><h2>{{ __('events.rules') }}</h2>@if($activity->revealed())<p class="preserve-lines">{{ $activity->rules }}</p>@else<p>{{ __('events.teaser_notice') }}</p>@endif</section>
 <section class="event-card"><h2>{{ __('events.scoring') }}</h2>@if($activity->revealed())<p class="preserve-lines">{{ $activity->scoring ?: __('events.not_confirmed') }}</p>@else<p>{{ __('events.teaser_notice') }}</p>@endif<h3>{{ __('events.who_plays') }}</h3><p>{{ __('events.access_'.$activity->access) }}</p><p class="preserve-lines">{{ $activity->operator_requirements }}</p>@if($activity->broadcast_planned)<p>{{ __('events.broadcast') }}</p>@endif</section></div>
 @if($activity->youtube_id && preg_match('/^[A-Za-z0-9_-]{11}$/',$activity->youtube_id))<p><a class="qapas-button" href="https://www.youtube.com/watch?v={{ $activity->youtube_id }}" target="_blank" rel="noopener noreferrer">{{ __('events.watch_clip') }} ↗</a></p>@endif
 <x-geographic-plan :plan="$geoPlan" />
 <x-event-plan :plan="$plan" />
 <section class="event-section"><h2>{{ __('events.join_activity') }}</h2><p>{{ __('events.propose_activity') }}</p><a class="qapas-button" href="{{ route('event.show',['project'=>$project->slug]) }}#interest">{{ __('events.interest') }} →</a></section>
 </div>
</x-layout>
