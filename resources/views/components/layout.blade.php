<!doctype html>
<html lang="{{ app()->getLocale() }}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="description" content="QAPAS Events — fêtes locales, équipes de freguesias et savoir-faire agricoles."><title>{{ $title ?? 'QAPAS Events' }}</title>@vite('resources/css/app.css')@livewireStyles</head>
<body><a class="skip" href="#main">{{ __('events.details') }}</a>
 @include('shared::components.qapas-navigation')
 <nav class="event-local" aria-label="QAPAS Events"><a class="brand" href="{{ route('home') }}">EVENTS <span>by QAPAS</span></a><a href="{{ route('privacy') }}">{{ __('events.privacy') }}</a><a href="/admin">{{ __('events.workspace') }} ↗</a></nav>
 @if(!in_array(app()->getLocale(),['fr','pt']))<p class="notice">{{ __('events.fallback') }}</p>@endif
 <main id="main">{{ $slot }}</main>
 <footer class="event-footer"><strong>QAPAS Events</strong><p>Freguesias · Agricultura · Encontros</p><a href="{{ route('privacy') }}">{{ __('events.privacy') }}</a></footer>
 @livewireScripts
</body></html>
