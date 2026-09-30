<x-layout>
 <article class="event-content prose"><p class="eyebrow">Os Jogos do Agricultor · 10 €</p><h1>{{ __('registration.status') }}</h1><p>{{ __('registration.save_link') }}</p>
 @if(!config('registration.live'))<p class="notice">{{ __('registration.test') }}</p>@endif
 @if($errors->any())<div class="notice" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
 @if($registration->isPaid())
 <p class="notice success">{{ __('registration.paid') }}</p>
 <p>{{ __('registration.'.($registration->decision==='pending'?'await_vote':$registration->decision)) }}</p>
 @if($registration->drinkCredit && $registration->drinkCredit->availableCents()>0)
 <div class="event-card"><h2>{{ __('registration.credit') }} : {{ \App\Domain\Finance\Money::format($registration->drinkCredit->availableCents()) }}</h2><p>{{ __('registration.code') }}</p><code>{{ $registration->drinkCredit->public_id }}</code></div>
 @endif
 @elseif($registration->payment_status==='pending')
 <p class="notice">{{ __('registration.pending') }}</p><form class="event-form" method="post" action="{{ $checkoutUrl }}">@csrf<button class="qapas-button" type="submit">{{ __('registration.pay') }}</button></form>
 @else
 <p class="notice">{{ __('registration.review') }}</p>
 @endif
 <details class="event-section"><summary>{{ __('registration.terms_version') }} : {{ $registration->terms_version }}</summary><p>{{ $registration->terms_snapshot }}</p></details>
 @if(filter_var(config('events.contact_email'),FILTER_VALIDATE_EMAIL))<p><a href="mailto:{{ config('events.contact_email') }}">{{ config('events.contact_email') }}</a></p>@endif
 <a class="text-link" href="{{ route('event.show',['project'=>$registration->campaign->eventProject->slug]) }}">{{ __('registration.back') }}</a>
 </article>
</x-layout>
