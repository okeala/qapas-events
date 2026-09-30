@php
    $chrome = \App\Support\QapasChrome::manifest();
    $base = \App\Support\QapasChrome::platformUrl();
    $locale = app()->getLocale();
    $lens = \App\Support\QapasJourneys::selected(request());
    $copy = config('qapas_copy.'.$locale, config('qapas_copy.fr'));
    $events = $chrome['events'];
    $identityAvailable = class_exists(\App\Support\QapasIdentity::class);
    $identityEnabled = $identityAvailable && \App\Support\QapasIdentity::enabled();
    $member = $identityEnabled ? \App\Support\QapasIdentity::current(request()) : null;
    $identityCopy = config('qapas_identity.'.$locale, config('qapas_identity.fr'));
    $appTitle = config('qapas_application.title.'.$locale) ?: config('qapas_application.name');
    $exitCopy = [
        'fr' => ['title' => 'Quitter cette application ?', 'body_before' => 'Vous allez quitter « ', 'body_after' => ' » et rejoindre le site de l’entreprise QAPAS. Vous pourrez revenir ici à tout moment via l’icône avec les carrés dans la barre de navigation.', 'confirm' => 'OK, j’ai compris', 'cancel' => 'Non, aller à l’accueil de '],
        'en' => ['title' => 'Leave this application?', 'body_before' => 'You are about to leave “', 'body_after' => '” and visit the QAPAS company website. You can return here at any time using the squares icon in the navigation bar.', 'confirm' => 'OK, I understand', 'cancel' => 'No, go to the home page of '],
        'pt' => ['title' => 'Sair desta aplicação?', 'body_before' => 'Vai sair de « ', 'body_after' => ' » e visitar o site da empresa QAPAS. Pode voltar aqui a qualquer momento através do ícone com quadrados na barra de navegação.', 'confirm' => 'OK, compreendi', 'cancel' => 'Não, ir para o início de '],
        'nl' => ['title' => 'Deze toepassing verlaten?', 'body_before' => 'U verlaat ‘', 'body_after' => '’ en gaat naar de website van QAPAS. U kunt hier op elk moment terugkeren via het pictogram met vierkantjes in de navigatiebalk.', 'confirm' => 'OK, begrepen', 'cancel' => 'Nee, naar de startpagina van '],
        'es' => ['title' => '¿Salir de esta aplicación?', 'body_before' => 'Vas a salir de «', 'body_after' => '» y visitar el sitio de la empresa QAPAS. Puedes volver aquí en cualquier momento mediante el icono de cuadrados de la barra de navegación.', 'confirm' => 'De acuerdo, entendido', 'cancel' => 'No, ir al inicio de '],
        'de' => ['title' => 'Diese Anwendung verlassen?', 'body_before' => 'Sie verlassen „', 'body_after' => '“ und besuchen die Website des Unternehmens QAPAS. Über das Quadrate-Symbol in der Navigationsleiste können Sie jederzeit hierher zurückkehren.', 'confirm' => 'OK, verstanden', 'cancel' => 'Nein, zur Startseite von '],
    ][$locale] ?? null;
    $languages = ['fr' => 'Français', 'en' => 'English', 'pt' => 'Português', 'nl' => 'Nederlands', 'es' => 'Español', 'de' => 'Deutsch'];
@endphp
<div class="qapas-global" x-data="{}">
    <nav class="qapas-global__inner" aria-label="QAPAS">
        <a class="qapas-global__brand" href="{{ route('home') }}" @if ($base) x-on:click.prevent="$refs.qapasExit.showModal()" aria-haspopup="dialog" aria-controls="qapas-exit-dialog" @endif aria-label="QAPAS — {{ $copy['home'] }}">
            <span class="qapas-global__brand-icon" aria-hidden="true">Q</span><span>QAPAS</span>
        </a>
        @if ($base)
            <a class="qapas-global__icon" href="{{ route('home') }}" x-on:click.prevent="$refs.qapasExit.showModal()" aria-haspopup="dialog" aria-controls="qapas-exit-dialog" aria-label="{{ $copy['home'] }}" title="{{ $copy['home'] }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 10 9-7 9 7v10H3z"/><path d="M9 20v-7h6v7"/></svg>
            </a>
            <a class="qapas-global__icon" href="{{ $base }}/overview?lang={{ $locale }}{{ $lens ? '&profil='.$lens : '' }}" aria-label="{{ $copy['overview'] }}" title="{{ $copy['overview'] }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
            </a>
        @endif
        <details class="qapas-global__apps">
            <summary class="qapas-global__icon" aria-label="{{ $copy['apps'] }}" title="{{ $copy['apps'] }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 17.5h7M17.5 14v7"/></svg>
            </summary>
            <div class="qapas-global__menu">
                @foreach ($chrome['applications'] as $application)
                    @continue($application['id'] === 'platform')
                    @php
                        $destination = $application['status'] === 'available' && $application['url']
                            ? $application['url']
                            : ($base ? $base.'/applications/'.$application['id'] : null);
                        $label = $application['label'][$locale] ?? $application['label']['fr'];
                        $separator = str_contains($destination ?? '', '?') ? '&' : '?';
                    @endphp
                    @if ($destination)
                        <a href="{{ $destination.$separator.'lang='.$locale }}" @if($application['id'] === config('qapas_application.id')) aria-current="page" @endif>
                            {{ $label }}@if ($application['status'] !== 'available') <span>· {{ $copy['soon'] }}</span> @endif
                        </a>
                    @endif
                @endforeach
            </div>
        </details>
        @if ($events['index_url'])
            <details class="qapas-global__events">
                <summary class="qapas-global__icon qapas-global__event-trigger" aria-label="{{ $events['label'][$locale] }}" title="{{ $events['label'][$locale] }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4m10-4v4M3 10h18"/></svg><span>{{ $events['label'][$locale] }}</span>
                </summary>
                <div class="qapas-global__menu">
                    <a href="{{ $events['index_url'] }}?lang={{ $locale }}">{{ $events['all'][$locale] }} · {{ $events['year'] }}</a>
                    @forelse ($events['items'] as $event)
                        <a href="{{ $event['url'] }}?lang={{ $locale }}"><time datetime="{{ $event['date'] }}">{{ $event['date'] }}</time> · {{ $event['title'][$locale] }}</a>
                    @empty
                        <span class="qapas-global__empty">{{ $events['empty'][$locale] }}</span>
                    @endforelse
                    <a href="{{ $events['games_url'] }}?lang={{ $locale }}">{{ $events['games'][$locale] }}</a>
                </div>
            </details>
        @endif
        @if ($member && $base)
            <details class="qapas-global__account">
                <summary class="qapas-global__icon" aria-label="{{ $identityCopy['account'] }} · {{ $member->name }}" title="{{ $member->name }}">
                    @if (request()->session()->get('qapas_identity.avatar') === true)
                        <img src="{{ route('qapas.avatar') }}" alt="" class="qapas-global__avatar" width="34" height="34">
                    @else
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                    @endif
                </summary>
                <div class="qapas-global__menu">
                    <strong class="qapas-global__identity-name">{{ $member->name }}</strong>
                    <a href="{{ $base }}/account?lang={{ $locale }}">{{ $identityCopy['account'] }}</a>
                    <form method="post" action="{{ route('qapas.logout') }}">@csrf<button type="submit">{{ $identityCopy['sign_out'] }}</button></form>
                </div>
            </details>
        @elseif ($identityEnabled)
            <a class="qapas-global__icon" href="{{ route('qapas.connect') }}" aria-label="{{ $identityCopy['sign_in'] }}" title="{{ $identityCopy['sign_in'] }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
            </a>
        @elseif ($base)
            <a class="qapas-global__icon" href="{{ $base }}/account?lang={{ $locale }}" aria-label="{{ $copy['account'] }}" title="{{ $copy['account'] }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
            </a>
        @endif
        <form method="post" action="{{ route('language.set') }}" class="qapas-global__language">
            @csrf
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c-5 5-5 13 0 18M12 3c5 5 5 13 0 18"/></svg>
            <label class="sr-only" for="qapas-shared-language">{{ $copy['language'] }}</label>
            <select id="qapas-shared-language" name="locale" onchange="this.form.requestSubmit()">
                @foreach ($languages as $code => $language)
                    <option value="{{ $code }}" @selected($locale === $code)>{{ $language }}</option>
                @endforeach
            </select>
            <noscript><button type="submit">OK</button></noscript>
        </form>
    </nav>
    @if ($base)
        <dialog id="qapas-exit-dialog" x-ref="qapasExit" x-on:click.self="$refs.qapasExit.close()" class="qapas-exit-dialog" aria-labelledby="qapas-exit-title" aria-describedby="qapas-exit-description">
            <span class="qapas-exit-dialog__eyebrow">QAPAS</span>
            <h2 id="qapas-exit-title">{{ $exitCopy['title'] }}</h2>
            <p id="qapas-exit-description">{{ $exitCopy['body_before'] }}<strong>{{ $appTitle }}</strong>{{ $exitCopy['body_after'] }}</p>
            <div class="qapas-exit-dialog__actions">
                <a class="qapas-exit-dialog__confirm" href="{{ $base.'?lang='.$locale }}">{{ $exitCopy['confirm'] }}</a>
                <a class="qapas-exit-dialog__cancel" href="{{ route('home') }}" autofocus>{{ $exitCopy['cancel'] }}{{ $appTitle }}</a>
            </div>
        </dialog>
    @endif
</div>
