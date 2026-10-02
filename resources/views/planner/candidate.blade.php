<x-layout :title="'Proposition · '.$public['name']">
    @vite('resources/css/event-planner.css')
    <div class="planner-public"><section class="planner-card"><h1>{{ $public['name'] }}</h1><p>{{ $public['description'] }}</p><p>Cette démarche transmet une proposition à l’organisation. Elle ne signe aucune précommande, ne réserve aucune place et ne déclenche aucun paiement.</p>
        <p>Le parcours de candidature attend l’activation d’une identité vérifiée sur cette installation. La visite reste disponible.</p>
        <a href="{{ route('events.show',$event->slug) }}">Revenir à la visite</a>
    </section></div>
</x-layout>
