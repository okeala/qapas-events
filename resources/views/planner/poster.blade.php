<x-layout :title="'Affiche · '.$plan['event']['name']">
    @vite('resources/css/event-planner.css')
    @php($copy=$plan['event']['phase_copy'][$plan['event']['phase']][app()->getLocale()]??$plan['event']['phase_copy'][$plan['event']['phase']]['fr']??[])
    <article class="planner-poster">
        <p>QAPAS · {{ $plan['event']['is_demo']?'Démonstration':'' }}</p>
        <span class="planner-badge">{{ in_array($plan['event']['decision'],['not_confirmed','cancelled'])?'Édition non confirmée':($copy['title']??config('event_planner.phases.'.$plan['event']['phase'])) }}</span>
        <h1>{{ $plan['event']['name'] }}</h1>@if(!in_array($plan['event']['decision'],['not_confirmed','cancelled']))<p>{{ $copy['message']??'' }}</p>@endif
        @if(in_array($plan['event']['decision'],['not_confirmed','cancelled']))<p>Cette édition n’est pas confirmée. Les offres sont fermées.</p>@endif
        @if($plan['event']['status_changed'])<p>Ce support reflète une publication antérieure ; consulter l’état courant en ligne.</p>@endif
        <p>{{ $plan['event']['venue'] }}@if($plan['event']['starts_at'])<br>{{ \Carbon\CarbonImmutable::parse($plan['event']['starts_at'])->format('d/m/Y') }}@if($plan['event']['decision']!=='confirmed') · Date proposée @endif @endif</p>
        @if($plan['event']['decision_deadline'])<p>Échéance de décision : {{ \Carbon\CarbonImmutable::parse($plan['event']['decision_deadline'])->timezone($plan['event']['timezone'])->format('d/m/Y H:i') }}.</p>@endif
        <p>Découvrez le plan, les activités, les offres et les réponses à vos questions.</p><img src="{{ $qr }}" alt="QR code vers la visite et le statut courant" width="160" height="160"><p><a href="{{ route('events.show',$event->slug) }}">{{ route('events.show',$event->slug) }}</a></p>
        <small>Publication {{ $plan['publication']['number'] }} · {{ \Carbon\CarbonImmutable::parse($plan['publication']['published_at'])->format('d/m/Y') }}. Consulter l’état courant avant de s’engager ou de se déplacer.</small>
        <div class="planner-actions"><button class="planner-button" onclick="window.print()">Imprimer l’affiche</button></div>
    </article>
</x-layout>
