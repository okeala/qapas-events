<x-layout :title="$project->name.' · QAPAS'">
 <section class="event-hero event-hero--small"><div><p class="eyebrow">{{ __('events.draft') }}</p><h1>{{ $project->name }}</h1><p class="lead">{{ __('events.lead') }}</p><a class="qapas-button" href="#interest">{{ __('events.interest') }} ↘</a></div></section>
 <div class="event-content">
 @if(session('received'))<div class="notice success" role="status">{{ __('events.received') }}</div>@endif
 <div class="event-grid four-ps">@foreach(['product','price','place','promotion'] as $pillar)<section class="event-card"><p class="eyebrow">0{{ $loop->iteration }}</p><h2>{{ __('events.'.$pillar) }}</h2><p>{{ app()->getLocale()==='pt' ? __('events.'.(['product'=>'official','price'=>'free','place'=>'election','promotion'=>'lead'][$pillar])) : $project->$pillar }}</p></section>@endforeach</div>
 <section class="event-section"><p class="eyebrow">{{ __('events.programme') }}</p><h2>{{ __('events.activities') }}</h2><p>{{ __('events.activity_intro') }}</p>
 <div class="event-grid activity-grid">@foreach($activities as $activity)<article class="event-card activity-card">
 <p class="eyebrow">{{ __('events.track_'.$activity->track) }} · {{ __('events.reveal_'.app(\App\Domain\Planning\Revelation::class)->label($activity)) }}</p><h3>{{ $activity->name }}</h3><p>{{ $activity->summary }}</p>
 <p class="activity-access">{{ __('events.access_'.$activity->access) }}</p>@if($activity->broadcast_planned)<p>{{ __('events.broadcast') }}</p>@endif
 <a class="text-link" href="{{ route('activity.show',['project'=>$project->slug,'activity'=>$activity->public_id]) }}">{{ __('events.discover_activity') }} →</a>
 </article>@endforeach</div><p>{{ __('events.propose_activity') }} <a href="#interest">{{ __('events.interest') }} →</a></p></section>
 @if($launchScenario)<section class="event-section"><h2>{{ __('events.launch_format') }}</h2><p>{{ __('events.launch_rules') }}</p><p>{{ __('events.independent_contribution') }}</p><div class="event-grid">@foreach($milestones as $milestone)<article class="event-card"><p class="eyebrow">{{ $milestone->isValidated() ? __('events.milestone_done') : __('events.milestone_pending') }}</p><h3>{{ $milestone->public_label }}</h3></article>@endforeach</div></section>@endif
 <section class="event-section"><h2>{{ __('events.hospitality_title') }}</h2><div class="event-grid"><article class="event-card"><h3>{{ __('events.soup_title') }}</h3><p>{{ __('events.soup_body') }}</p></article><article class="event-card"><h3>{{ __('events.shelter_title') }}</h3><p>{{ __('events.shelter_body') }}</p></article><article class="event-card"><h3>{{ __('events.relay_meeting_title') }}</h3><p>{{ __('events.relay_meeting_body') }}</p></article></div><p>{{ __('events.food_rules') }}</p></section>
 <section class="event-section"><h2>{{ __('events.relay_reveal_title') }}</h2><p>{{ __('events.relay_reveal_body',['count'=>$relayProgress['active_freguesias']]) }}</p></section>
 @if($press->isNotEmpty())<section class="event-section"><h2>{{ __('events.press') }}</h2>@foreach($press as $release)<p><a class="text-link" href="{{ route('press.show',['project'=>$project->slug,'press'=>$release->public_id]) }}">{{ app()->getLocale()==='pt'&&$release->title_pt?$release->title_pt:$release->name }}</a></p>@endforeach</section>@endif
 <x-geographic-plan :plan="$geoPlan" />
 <x-event-plan :plan="$plan" />
 <section class="event-section"><h2>{{ __('events.offers') }}</h2><p>{{ __('events.indicative') }}</p><div class="event-grid">
 @foreach($offers as $offer)<article class="event-card"><h3>{{ $offer->name }}</h3>@if($offer->is_founder)<p class="eyebrow">{{ __('events.founder') }}</p>@endif<p class="price">{{ $offer->price_gross_cents===null ? __('events.quote') : \App\Domain\Finance\Money::format($offer->price_gross_cents) }}</p><p>{{ $offer->capacity>0 ? $offer->capacity.' '.__('events.capacity') : __('events.not_confirmed') }}</p><dl><dt>{{ __('events.included') }}</dt><dd>{{ $offer->includes }}</dd>@if($offer->excludes)<dt>{{ __('events.excluded') }}</dt><dd>{{ $offer->excludes }}</dd>@endif<dt>{{ __('events.delivery') }}</dt><dd>{{ $offer->delivery ?: __('events.not_confirmed') }}</dd></dl><a class="text-link" href="#interest">{{ __('events.interest') }} →</a></article>@endforeach</div></section>
 <section class="event-section interest" id="interest"><div><p class="eyebrow">{{ __('events.interest') }}</p><h2>{{ __('events.headline') }}</h2><p>{{ __('events.interest_intro') }}</p></div><div>
 @if(config('events.privacy_ready') && filled(config('events.organizer_name')) && filter_var(config('events.contact_email'), FILTER_VALIDATE_EMAIL))
 @if($errors->any())<div class="notice" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
 <form method="post" action="{{ route('event.interest',['project'=>$project->slug]) }}" class="event-form">@csrf
  <label>{{ __('events.profile') }}<select name="profile" required>@foreach(['resident','team','junta','exhibitor','relay','sponsor','volunteer'] as $profile)<option value="{{ $profile }}" @selected(old('profile')===$profile)>{{ __('events.'.$profile) }}</option>@endforeach</select></label>
  <label>{{ __('events.name') }}<input name="name" autocomplete="name" maxlength="120" required value="{{ old('name') }}"></label>
  <label>{{ __('events.email') }}<input name="email" type="email" autocomplete="email" maxlength="254" required value="{{ old('email') }}"></label>
  <label>{{ __('events.freguesia') }}<input name="freguesia" maxlength="120" value="{{ old('freguesia') }}"></label>
  <label>{{ __('events.message') }}<textarea name="message" rows="4" maxlength="3000">{{ old('message') }}</textarea></label>
  <fieldset><legend>{{ __('events.stand_options') }}</legend><p>{{ __('events.options_notice') }}</p>
  <label>{{ __('events.expected_guests') }}<input type="number" name="expected_guests" min="1" max="10000" value="{{ old('expected_guests') }}"></label>
  <label class="check"><input type="checkbox" name="larger_tent_requested" value="1" @checked(old('larger_tent_requested'))><span>{{ __('events.larger_tent') }}</span></label>
  <label class="check"><input type="checkbox" name="extra_furniture_requested" value="1" @checked(old('extra_furniture_requested'))><span>{{ __('events.extra_furniture') }}</span></label></fieldset>
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
