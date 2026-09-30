<x-layout :title="__('cabins.title')">
@if($preview)<x-preview-notice />@endif
<section class="event-hero event-hero--small"><div><p class="eyebrow">{{ $project->name }}</p><h1>{{ __('cabins.title') }}</h1><p class="lead">{{ __($details?'cabins.intro':'cabins.teaser') }}</p></div></section>
<div class="event-content">
@if($details)
<section class="event-card"><h2>{{ __('cabins.rules') }}</h2><p>{{ __('cabins.connector') }}</p><ul class="offer-benefits">@foreach(['envelope','wattle','roof','recovery','harvest'] as $key)<li>{{ __('cabins.'.$key) }}</li>@endforeach</ul></section>
<section class="event-section event-card"><h2>{{ __('cabins.demo_title') }}</h2>
@if($demo)<a class="qapas-button" href="{{ route($preview?'blog.article-preview':'blog.show',['project'=>$project->slug,'post'=>$demo->public_id]) }}">{{ __($preview?'cabins.demo_draft':'cabins.demo_link') }} →</a>
@else<p>{{ __('cabins.demo_pending') }}</p>@endif</section>
<div class="event-grid event-section">@foreach(['team','rental','vote'] as $key)<section class="event-card"><h2>{{ __('cabins.'.$key.'_title') }}</h2><p>{{ __('cabins.'.$key.'_body') }}</p></section>@endforeach</div>
<section class="event-section event-card"><h2>{{ __('cabins.partner_title') }}</h2><p>{{ __('cabins.partner_body') }}</p><a class="qapas-button" href="{{ route($preview?'event.preview':'event.show',['project'=>$project->slug]) }}#interest">{{ __('cabins.partner_cta') }} →</a></section>
<section class="event-section"><h2>{{ __('cabins.gallery') }}</h2><div class="event-grid">
@forelse($cabins as $cabin)
<article class="event-card" id="cabane-{{ $cabin->public_id }}"><p class="eyebrow">{{ __($cabin->supply_mode==='team_build'?'cabins.team':'cabins.rental') }}</p><h3>{{ $cabin->name }}</h3><p>{{ $cabin->publicSummary() }}</p>
<p>{{ __('cabins.'.($cabin->status==='received'&&!$cabin->received()?'review':$cabin->status)) }}</p>
<a href="{{ route($preview?'stand.preview':'stand.show',['project'=>$project->slug,'stand'=>$cabin->stand->public_id]) }}">{{ __('cabins.stand') }} →</a></article>
@empty<p>{{ __('cabins.empty') }}</p>@endforelse
</div></section>
<section class="event-section event-card"><p>{{ __('cabins.reception') }}</p><p>{{ __('cabins.care') }}</p><p>{{ __('cabins.after') }}</p></section>
@endif
<p><a href="{{ route($preview?'event.preview':'event.show',['project'=>$project->slug]) }}">← {{ $project->name }}</a></p>
</div></x-layout>
