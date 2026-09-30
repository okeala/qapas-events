<!doctype html>
<html lang="{{ app()->getLocale() }}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="description" content="Os Jogos do Agricultor — fêtes locales, équipes de freguesias et savoir-faire agricoles."><title>{{ $title ?? 'Os Jogos do Agricultor' }}</title>@vite('resources/css/app.css')@livewireStyles</head>
<body><a class="skip" href="#main">{{ __('events.details') }}</a>
 @include('shared::components.qapas-navigation')
 <nav class="event-local" aria-label="Os Jogos do Agricultor"><a class="brand" href="{{ route('home') }}">OS JOGOS DO AGRICULTOR <span>by QAPAS</span></a><a href="{{ route('privacy') }}">{{ __('events.privacy') }}</a><a href="/admin">{{ __('events.workspace') }} ↗</a></nav>
 @if(!in_array(app()->getLocale(),['fr','pt']))<p class="notice">{{ __('events.fallback') }}</p>@endif
 <main id="main">{{ $slot }}</main>
 <footer class="event-footer"><strong>Os Jogos do Agricultor</strong><p>Freguesias · Agricultura · Encontros</p><a href="{{ route('privacy') }}">{{ __('events.privacy') }}</a></footer>
 @livewireScripts
</body></html>
