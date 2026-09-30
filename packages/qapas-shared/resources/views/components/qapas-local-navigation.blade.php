@php
    $nav = \App\Support\PublicPages::copy()['nav'];
    $lens = \App\Support\QapasJourneys::selected(request());
    $menu = \App\Support\CommonFeatures::settings()['menu'];
    $journeys = $lens ? \App\Support\QapasJourneys::manifest() : null;
    $activeJourney = $journeys ? collect($journeys['journeys'])->firstWhere('id', $lens) : null;
    $sections = [
        'discover' => ['faq', 'updates'],
        'participate' => ['team', 'contact'],
        'information' => ['support', 'legal'],
    ];
    $visibleSections = [];
    foreach ($sections as $section => $keys) {
        $visible = array_values(array_filter($keys, fn ($key) => ($menu[$key] ?? false) && \Illuminate\Support\Facades\Route::has($key)));
        if ($visible !== []) {
            $visibleSections[$section] = $visible;
        }
    }
    $localLinks = [];
    $primaryRoutes = config('qapas_application.primary_navigation');
    $journeyRoute = \Illuminate\Support\Facades\Route::has('where-to-start') ? 'where-to-start' : 'home';
    foreach (config('qapas_application.local_navigation', []) as $item) {
        $name = is_array($item) ? ($item['route'] ?? null) : null;
        $route = is_string($name) ? \Illuminate\Support\Facades\Route::getRoutes()->getByName($name) : null;
        $label = is_array($item['label'] ?? null) ? ($item['label'][app()->getLocale()] ?? $item['label']['fr'] ?? null) : null;
        if ($name === 'where-to-start') {
            $label = config('qapas_journey_copy.'.app()->getLocale().'.nav');
        }
        if ($route && $route->getDomain() === null && $route->parameterNames() === []
            && is_string($label) && trim($label) !== '') {
            $localLinks[] = ['route' => $name, 'label' => $label];
        }
    }
    $primaryRoutes = is_array($primaryRoutes) ? $primaryRoutes : array_column($localLinks, 'route');
    $primaryLinks = array_values(array_filter($localLinks, fn ($link) => in_array($link['route'], $primaryRoutes, true)));
    $secondaryLinks = array_values(array_filter($localLinks, fn ($link) => ! in_array($link['route'], $primaryRoutes, true)));
@endphp
<nav class="qapas-local" aria-label="{{ config('qapas_application.name') }}">
    <div class="qapas-local__inner">
        <a class="qapas-local__title" href="{{ route('home') }}">{{ config('qapas_application.name') }}</a>
        <div class="qapas-local__links">
            <div class="qapas-local__shortcuts">
                @if (! \Illuminate\Support\Facades\Route::has('where-to-start'))
                    <a href="{{ route($journeyRoute, $lens ? ['profil' => $lens] : []) }}#prismes">{{ config('qapas_journey_copy.'.app()->getLocale().'.nav') }}</a>
                @endif
                @foreach ($primaryLinks as $item)
                    <a href="{{ route($item['route']) }}" @if(request()->routeIs($item['route'])) aria-current="page" @endif>{{ $item['label'] }}</a>
                @endforeach
            </div>
            <details class="qapas-local__context">
                <summary aria-label="{{ $nav['menu'] }} — {{ config('qapas_application.name') }}">{{ $nav['menu'] }}</summary>
                <div class="qapas-local__menu">
                    <div class="qapas-local__mobile-links">
                        <a href="{{ route('home') }}" @if(request()->routeIs('home')) aria-current="page" @endif>{{ $nav['home'] }}</a>
                        @if (! \Illuminate\Support\Facades\Route::has('where-to-start'))
                            <a href="{{ route($journeyRoute, $lens ? ['profil' => $lens] : []) }}#prismes">{{ config('qapas_journey_copy.'.app()->getLocale().'.nav') }}</a>
                        @endif
                        @foreach ($primaryLinks as $item)
                            <a href="{{ route($item['route']) }}" @if(request()->routeIs($item['route'])) aria-current="page" @endif>{{ $item['label'] }}</a>
                        @endforeach
                    </div>
                    @if ($secondaryLinks !== [])
                        <div class="qapas-local__menu-group qapas-local__secondary-links">
                            @foreach ($secondaryLinks as $item)
                                <a href="{{ route($item['route']) }}" @if(request()->routeIs($item['route'])) aria-current="page" @endif>{{ $item['label'] }}</a>
                            @endforeach
                        </div>
                    @endif
                    @foreach ($visibleSections as $section => $keys)
                        <div class="qapas-local__menu-group">
                            <span class="qapas-local__menu-heading">{{ $nav[$section] }}</span>
                            @foreach ($keys as $key)
                                <a href="{{ route($key, $lens ? ['profil' => $lens] : []) }}" @if(request()->routeIs($key)) aria-current="page" @endif>{{ $nav[$key] }}</a>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </details>
        </div>
    </div>
</nav>
@if ($activeJourney)
    <div class="qapas-lens-bar" role="status">
        <div class="qapas-lens-bar__inner">
            <span>{{ $activeJourney['title'][app()->getLocale()] }}</span>
            <a href="{{ route($journeyRoute, ['profil' => $lens]) }}#prismes">{{ $journeys['change'][app()->getLocale()] }}</a>
            <a href="{{ route($journeyRoute) }}#prismes">{{ config('qapas_journey_copy.'.app()->getLocale().'.neutral') }}</a>
        </div>
    </div>
@endif

