@props(['project'])
<nav class="mobilization-nav" aria-label="{{ __('mobilization.nav') }}">@foreach(['communes','teams','relays','sponsors','growth'] as $part)<a href="{{ route('mobilization.'.$part,['project'=>$project->slug]) }}">{{ __('mobilization.links.'.$part) }}</a>@endforeach</nav>
