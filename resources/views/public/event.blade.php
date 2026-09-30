<x-layout :title="$project->name.' · QAPAS Events'">
 <section class="event-hero event-hero--small"><div><p class="eyebrow">{{ __('events.draft') }}</p><h1>{{ $project->name }}</h1><p class="lead">{{ __('events.lead') }}</p><a class="qapas-button" href="#interest">{{ __('events.interest') }} ↘</a></div></section>
 <div class="event-content">
 @if(session('received'))<div class="notice success" role="status">{{ __('events.received') }}</div>@endif
 <div class="event-grid four-ps">@foreach(['product','price','place','promotion'] as $pillar)<section class="event-card"><p class="eyebrow">0{{ $loop->iteration }}</p><h2>{{ __('events.'.$pillar) }}</h2><p>{{ app()->getLocale()==='pt' ? __('events.'.(['product'=>'official','price'=>'free','place'=>'election','promotion'=>'lead'][$pillar])) : $project->$pillar }}</p></section>@endforeach</div>
 <section class="event-section"><h2>{{ __('events.offers') }}</h2><p>{{ __('events.indicative') }}</p><div class="event-grid">
 @foreach($offers as $offer)<article class="event-card"><h3>{{ $offer->name }}</h3><p class="price">{{ $offer->price_gross_cents===null ? __('events.quote') : \App\Domain\Finance\Money::format($offer->price_gross_cents) }}</p><p>{{ $offer->capacity>0 ? $offer->capacity.' '.__('events.capacity') : __('events.not_confirmed') }}</p><dl><dt>{{ __('events.included') }}</dt><dd>{{ $offer->includes }}</dd>@if($offer->excludes)<dt>{{ __('events.excluded') }}</dt><dd>{{ $offer->excludes }}</dd>@endif<dt>{{ __('events.delivery') }}</dt><dd>{{ $offer->delivery ?: __('events.not_confirmed') }}</dd></dl><a class="text-link" href="#interest">{{ __('events.interest') }} →</a></article>@endforeach</div></section>
 <section class="event-section interest" id="interest"><div><p class="eyebrow">{{ __('events.interest') }}</p><h2>{{ __('events.headline') }}</h2><p>{{ __('events.interest_intro') }}</p></div><div>
 @if(config('events.privacy_ready') && filled(config('events.organizer_name')) && filter_var(config('events.contact_email'), FILTER_VALIDATE_EMAIL))
 @if($errors->any())<div class="notice" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
 <form method="post" action="{{ route('event.interest',['project'=>$project->slug]) }}" class="event-form">@csrf
  <label>{{ __('events.profile') }}<select name="profile" required>@foreach(['resident','team','junta','exhibitor','relay','sponsor','volunteer'] as $profile)<option value="{{ $profile }}" @selected(old('profile')===$profile)>{{ __('events.'.$profile) }}</option>@endforeach</select></label>
  <label>{{ __('events.name') }}<input name="name" autocomplete="name" maxlength="120" required value="{{ old('name') }}"></label>
  <label>{{ __('events.email') }}<input name="email" type="email" autocomplete="email" maxlength="254" required value="{{ old('email') }}"></label>
  <label>{{ __('events.freguesia') }}<input name="freguesia" maxlength="120" value="{{ old('freguesia') }}"></label>
  <label>{{ __('events.message') }}<textarea name="message" rows="4" maxlength="3000">{{ old('message') }}</textarea></label>
  <div class="trap" aria-hidden="true"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div>
  <input type="hidden" name="source" value="{{ in_array(request('source'),['direct','junta','relay','field','social'],true)?request('source'):'direct' }}">
  <label class="check"><input type="checkbox" name="privacy" value="1" required @checked(old('privacy'))><span>{{ __('events.privacy_ack') }} <a href="{{ route('privacy') }}">{{ __('events.privacy') }}</a></span></label>
  <label class="check"><input type="checkbox" name="marketing_opt_in" value="1" @checked(old('marketing_opt_in'))><span>{{ __('events.marketing') }}</span></label>
  <button class="qapas-button" type="submit">{{ __('events.submit') }} →</button>
 </form>
 @else<p class="notice">{{ __('events.privacy_closed') }}</p>@endif
 </div></section>
 </div>
</x-layout>
