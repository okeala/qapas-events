@php
    $manifest = \App\Support\QapasJourneys::manifest();
    $locale = app()->getLocale();
    $selected = ($interactive ?? false) ? ($selectedJourney ?? null) : \App\Support\QapasJourneys::selected(request());
    $base = \App\Support\QapasChrome::platformUrl();
    $journeyRoute = \Illuminate\Support\Facades\Route::has('where-to-start') ? 'where-to-start' : 'home';
@endphp
<section class="qapas-journeys" id="prismes" aria-labelledby="qapas-journeys-heading">
    @if ($manifest)
        @if ($pageTitle ?? false)
            <h1 id="qapas-journeys-heading">{{ $manifest['heading'][$locale] }}</h1>
        @else
            <h2 id="qapas-journeys-heading">{{ $manifest['heading'][$locale] }}</h2>
        @endif
        <p>{{ $manifest['lead'][$locale] }}</p>
        @if (! (($interactive ?? false) && ($collapseOnSelect ?? false) && $selected))
        <div class="qapas-journeys__choices">
            @foreach ($manifest['journeys'] as $journey)
                <a href="{{ route($journeyRoute, ['profil' => $journey['id']]) }}#{{ ($interactive ?? false) ? $journey['id'] : 'prismes-reponse' }}"
                   @if ($interactive ?? false) wire:click.prevent="choose('{{ $journey['id'] }}')" wire:key="qapas-prism-{{ $journey['id'] }}" @endif
                   @if ($selected === $journey['id']) aria-current="true" @endif
                   class="qapas-journeys__choice">
                    <flux:badge color="emerald" size="sm">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</flux:badge>
                    <strong>{{ $journey['title'][$locale] }}</strong>
                    <span>{{ $journey['description'][$locale] }}</span>
                </a>
            @endforeach
        </div>
        @endif
        @if ($selected && ! ($interactive ?? false))
            @php
                $journey = collect($manifest['journeys'])->firstWhere('id', $selected);
                $localDestination = $journey['local_url'] ?? \App\Support\CommonFeatures::journeyDestination($selected);
                $copy = config('qapas_journey_copy.'.$locale);
            @endphp
            <div class="qapas-journeys__answer" id="prismes-reponse">
                <p>{{ $manifest['change'][$locale] }} : <strong>{{ $journey['title'][$locale] }}</strong></p>
                <div class="qapas-journeys__actions">
                    @if ($localDestination)
                        <a href="{{ $localDestination }}">{{ $copy['local'] }} →</a>
                    @endif
                    @if ($journey['overview_url'])
                        <a href="{{ $journey['overview_url'] }}&lang={{ $locale }}">{{ $copy['more'] }} →</a>
                    @endif
                    <a href="{{ route($journeyRoute) }}#prismes">{{ $manifest['change'][$locale] }}</a>
                </div>
            </div>
        @endif
    @elseif ($base)
        <h2 id="qapas-journeys-heading">QAPAS</h2>
        <a href="{{ $base }}/overview?lang={{ $locale }}">{{ config('qapas_copy.'.$locale.'.overview') }} →</a>
    @endif
</section>

