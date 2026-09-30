<x-layout :title="(app()->getLocale()==='pt'&&$press->title_pt?$press->title_pt:$press->name).' · '.$project->name">
 <section class="event-hero event-hero--small"><div><p class="eyebrow">{{ __('events.press') }} · {{ $press->published_at->timezone('Europe/Lisbon')->format('d/m/Y') }}</p><h1>{{ app()->getLocale()==='pt'&&$press->title_pt?$press->title_pt:$press->name }}</h1><a class="text-link" href="{{ route('event.show',['project'=>$project->slug]) }}">← {{ $project->name }}</a></div></section>
 <article class="event-content"><p class="preserve-lines">{{ app()->getLocale()==='pt'&&$press->body_pt?$press->body_pt:$press->body }}</p></article>
</x-layout>
