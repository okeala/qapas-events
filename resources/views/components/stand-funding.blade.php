@props(['project','stand'=>null,'preview'=>false])
<section class="event-section event-card" id="financement-stand">
<h2>{{ __('sponsoring.funding_title') }}</h2>
@if($stand?->kind==='sponsor')
<p>{{ __('sponsoring.structural_funding') }}</p><p>{{ __('sponsoring.own_stand_body') }}</p>
@elseif($stand?->kind==='independent')
<p>{{ __('sponsoring.independent_funding') }}</p>
@else
<p>{{ __('sponsoring.funding_intro') }}</p>
<div class="funding-sources">@foreach(__('sponsoring.sources') as [$source,$use])<article><h3>{{ $source }}</h3><p>{{ $use }}</p></article>@endforeach</div>
@php($ticketPlan=\App\Models\PresalePlan::where('event_project_id',$project->id)->first())
@if($ticketPlan)
@php($ticketQuote=$ticketPlan->quote())
<p class="notice">{{ __('sponsoring.tickets_split',['ticket'=>\App\Domain\Finance\Money::format($ticketQuote['price_cents']),'stand'=>\App\Domain\Finance\Money::format(intdiv($ticketQuote['price_cents']*$ticketPlan->village_basis_points,10000)),'junta'=>\App\Domain\Finance\Money::format(intdiv($ticketQuote['price_cents']*$ticketPlan->junta_basis_points,10000))]) }}</p>
@endif
<p><strong>{{ __('sponsoring.no_team_debt') }}</strong></p>
@endif
</section>
