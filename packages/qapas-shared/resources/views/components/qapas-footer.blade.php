@php
    $footer = \App\Support\QapasChrome::manifest()['footer'];
    $locale = app()->getLocale();
    $copy = config('qapas_copy.'.$locale, config('qapas_copy.fr'));
@endphp
<footer class="qapas-footer">
    <div class="qapas-footer__inner">
        <div><strong>QAPAS</strong><p>{{ $copy['footer'] }}</p></div>
        <nav aria-label="QAPAS">
            @foreach ($footer['links'] as $link)
                @php
                    $url = str_contains($link['url'], '#')
                        ? str_replace('#', '?lang='.$locale.'#', $link['url'])
                        : $link['url'].'?lang='.$locale;
                @endphp
                <a href="{{ $url }}">{{ $link['label'][$locale] ?? $link['label']['fr'] }}</a>
            @endforeach
        </nav>
        @if (\App\Support\CookieConsent::enabled())
            @php $cookieLabel = ['fr' => 'Choix des cookies', 'en' => 'Cookie choices', 'pt' => 'Escolhas de cookies', 'nl' => 'Cookiekeuze', 'es' => 'Opciones de cookies', 'de' => 'Cookie-Auswahl'][$locale]; @endphp
            <a class="qapas-footer__cookies" href="{{ route('cookies') }}">{{ $cookieLabel }}</a>
        @endif
        <small>{{ $footer['note'][$locale] ?? $footer['note']['fr'] }}</small>
    </div>
</footer>
