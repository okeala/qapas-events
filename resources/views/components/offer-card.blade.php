@props(['offer','project','preview'=>false])
<article class="event-card pricing-card">
@if($offer->image_key)<img src="{{ asset('images/festival-concept.webp') }}" alt="{{ app()->getLocale()==='pt'?'Ilustração de ambiente, não contratual':'Illustration d’ambiance, non contractuelle' }}" loading="lazy" class="offer-art offer-art--{{ $offer->image_key }}">@endif
<p class="eyebrow">{{ $offer->kind==='village' ? (app()->getLocale()==='pt'?'Parceiro principal de um stand':'Parrain principal d’un stand') : ($offer->is_founder?__('events.founder'):'QAPAS') }}</p>
<h3>{{ $offer->name }}</h3><p>{{ $offer->summary }}</p>
@if($offer->discountVisible())<p><s>{{ \App\Domain\Finance\Money::format($offer->regular_price_gross_cents) }}</s> · {{ __('events.founder') }}</p>@endif
<p class="price">{{ $offer->price_gross_cents===null ? __('events.quote') : \App\Domain\Finance\Money::format($offer->price_gross_cents) }} @if($offer->price_gross_cents!==null)<small>TTC / IVA incluído</small>@endif</p>
<ul class="offer-benefits">@forelse($offer->benefits??[] as $benefit)<li>@if(filled($benefit['url']??null))<a href="{{ $benefit['url'] }}">{{ $benefit['label'] }}</a>@else{{ $benefit['label'] }}@endif</li>@empty<li>{{ $offer->includes }}</li>@endforelse</ul>
<a class="qapas-button" href="{{ route($preview?'offer.preview':'offer.show',['project'=>$project->slug,'offer'=>$offer->public_id]) }}">{{ app()->getLocale()==='pt'?'Ver a proposta':'Voir l’offre' }} →</a>
</article>