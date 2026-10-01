@props(['project','preview'=>false])
@if($preview||$project->sponsorship_visibility==='catalog')
<section class="event-section event-card"><h2>{{ __('sponsoring.title') }}</h2><p>{{ __('sponsoring.intro') }}</p><div class="sponsoring-choices"><a class="qapas-button" href="{{ route($preview?'sponsoring.preview':'sponsoring.index',['project'=>$project->slug,'target'=>'stand']) }}">{{ __('sponsoring.stand_choice') }} →</a><a class="qapas-button" href="{{ route($preview?'sponsoring.preview':'sponsoring.index',['project'=>$project->slug,'target'=>'event']) }}">{{ __('sponsoring.event_choice') }} →</a></div></section>
@endif
