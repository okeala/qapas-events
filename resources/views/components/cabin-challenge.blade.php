@props(['project','preview'=>false])
@if($project->cabin_policy_version && ($preview || in_array($project->cabin_visibility,['teaser','details'],true)))
<section class="event-section event-card"><p class="eyebrow">Os Jogos do Agricultor</p><h2>{{ __('cabins.title') }}</h2>
<p>{{ __('cabins.teaser') }}</p><a class="qapas-button" href="{{ route($preview?'cabins.preview':'cabins.index',['project'=>$project->slug]) }}">{{ __('cabins.discover') }} →</a></section>
@endif
