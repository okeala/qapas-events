@props(['offer','project','preview'=>false])
@php($ticketPlan=$offer->template_key==='presale-support'?\App\Models\PresalePlan::where('event_project_id',$project->id)->where('unified_benefits',true)->first():null)
@php($ticketQuote=$ticketPlan?->quote())
@php($displayPrice=$ticketQuote['price_cents']??$offer->price_gross_cents)
<article class="event-card pricing-card">
@if($offer->image_key)<img src="{{ asset('images/festival-concept.webp') }}" alt="{{ app()->getLocale()==='pt'?'Ilustração de ambiente, não contratual':'Illustration d’ambiance, non contractuelle' }}" loading="lazy" class="offer-art offer-art--{{ $offer->image_key }}">@endif
<p class="eyebrow">{{ $offer->kind==='village' ? (app()->getLocale()==='pt'?'Parceiro principal de um stand':'Parrain principal d’un stand') : ($offer->is_founder?__('events.founder'):'QAPAS') }}</p>
<h3>{{ $offer->name }}</h3><p>{{ $offer->summary }}</p>
@if($ticketPlan&&$ticketPlan->sales_strategy==='presale_discount'&&$ticketQuote['phase']==='early'&&$ticketPlan->full_price_cents>$displayPrice&&filled($ticketPlan->pricing_evidence))<p>{{ __('tickets.full_price') }} : {{ \App\Domain\Finance\Money::format($ticketPlan->full_price_cents) }} · {{ $ticketPlan->early_until?->timezone('Europe/Lisbon')->format('d/m/Y H:i') }}</p>@elseif(!$ticketPlan&&$offer->discountVisible())<p><s>{{ \App\Domain\Finance\Money::format($offer->regular_price_gross_cents) }}</s> · {{ __('events.founder') }}</p>@endif
<p class="price">{{ $displayPrice===null ? __('events.quote') : \App\Domain\Finance\Money::format($displayPrice) }} @if($displayPrice!==null)<small>TTC / IVA incluído</small>@endif</p>
@if($ticketPlan&&count($ticketQuote['benefits']))<ul class="offer-benefits"><li>{{ __('tickets.entry_included') }}</li>@foreach($ticketQuote['benefits'] as $benefit)<li>{{ $benefit['quantity'] }} × {{ $benefit['label_'.app()->getLocale()]??$benefit['label_fr'] }}</li>@endforeach</ul>@endif
<ul class="offer-benefits">@forelse($offer->benefits??[] as $benefit)<li>@if(filled($benefit['url']??null))<a href="{{ $benefit['url'] }}">{{ $benefit['label'] }}</a>@else{{ $benefit['label'] }}@endif</li>@empty<li>{{ $offer->includes }}</li>@endforelse</ul>
<a class="qapas-button" href="{{ route($preview?'offer.preview':'offer.show',['project'=>$project->slug,'offer'=>$offer->public_id]) }}">{{ app()->getLocale()==='pt'?'Ver a proposta':'Voir l’offre' }} →</a>
</article>