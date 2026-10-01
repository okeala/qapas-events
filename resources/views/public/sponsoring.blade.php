<x-layout :title="__('sponsoring.title').' · '.$project->name">
@if($preview)<x-preview-notice />@endif
@php($catalogRoute=$preview?'sponsoring.preview':'sponsoring.index')
<div class="event-content"><p><a href="{{ route($preview?'event.preview':'event.show',['project'=>$project->slug]) }}">← {{ __('sponsoring.back') }}</a></p>
<h1>{{ __('sponsoring.title') }}</h1><p class="lead">{{ __('sponsoring.intro') }}</p>
@if(session('sponsorship_received'))<p class="notice success" role="status">{{ __('sponsoring.received') }}</p>@endif
<nav class="sponsoring-choices" aria-label="{{ __('sponsoring.title') }}"><a class="qapas-button" @if($target==='stand') aria-current="page" @endif href="{{ route($catalogRoute,['project'=>$project->slug,'target'=>'stand']) }}">{{ __('sponsoring.stand_choice') }}</a><a class="qapas-button" @if($target==='event') aria-current="page" @endif href="{{ route($catalogRoute,['project'=>$project->slug,'target'=>'event']) }}">{{ __('sponsoring.event_choice') }}</a></nav>
@if($target==='stand')
<p>{{ __('sponsoring.stand_intro') }}</p>
@if(!$stand)
<div class="event-grid">@forelse($stands as $item)
@php($r=app(\App\Domain\Promotion\StandSponsoring::class)->report($item))
<article class="event-card"><p class="eyebrow">{{ $item->freguesia }}</p><h2>{{ $item->name }}</h2><p>{{ __('sponsoring.part') }}</p><p class="price">{{ $r['unit_cents']===null?__('sponsoring.quote'):\App\Domain\Finance\Money::format($r['unit_cents']) }} @if($r['unit_cents']!==null)<small>{{ __('sponsoring.ttc') }}</small>@endif</p><p>{{ __('sponsoring.available',['count'=>$r['available']]) }}</p><a class="qapas-button" href="{{ route($catalogRoute,['project'=>$project->slug,'target'=>'stand','stand'=>$item->public_id]) }}">{{ __('sponsoring.choose_stand') }} →</a></article>
@empty<p>{{ __('sponsoring.empty') }}</p>@endforelse</div>
@else
<section class="event-card"><p class="eyebrow">{{ $stand->freguesia }}</p><h2>{{ $stand->name }}</h2><p>{{ __('sponsoring.total') }} : <strong>{{ $allocation['total_cents']===null?__('sponsoring.quote'):\App\Domain\Finance\Money::format($allocation['total_cents']) }}</strong> @if($allocation['total_cents']!==null){{ __('sponsoring.ttc') }}@endif</p><p>{{ __('sponsoring.shared_note') }}</p>
<h3>{{ __('sponsoring.visibility') }}</h3><p>{{ __('sponsoring.visibility_body') }}</p>
<div class="sponsor-share-board" aria-label="{{ __('sponsoring.visibility') }}">
@foreach($allocation['partners'] as $partner)<div class="sponsor-share occupied" style="grid-column:span {{ $partner->sponsorshipUnits() }}"><strong>{{ $partner->sponsorshipUnits()*20 }} %</strong><span>{{ ($partner->is_public&&filled($partner->evidence))?$partner->name:__('sponsoring.allocated') }}</span></div>@endforeach
@for($i=0;$i<$allocation['available'];$i++)<div class="sponsor-share"><strong>20 %</strong><span>{{ __('sponsoring.free') }}</span></div>@endfor
</div><p>{{ __('sponsoring.available',['count'=>$allocation['available']]) }}</p><p>{{ __('sponsoring.exclusivity_body') }}</p><p><a href="{{ route($preview?'stand.preview':'stand.show',['project'=>$project->slug,'stand'=>$stand->public_id]) }}">{{ __('sponsoring.details') }} →</a></p></section>
@endif
<x-stand-funding :project="$project" :stand="$stand" :preview="$preview" />
@else
<p>{{ __('sponsoring.event_intro') }}</p><p class="notice">{{ __('sponsoring.capacity') }}</p>
<div class="event-grid">@forelse($slots as $item)
<article class="event-card" id="slot-{{ $item->public_id }}"><p class="eyebrow">{{ __('sponsoring.slot') }}</p><h2>{{ $item->purpose==='cups'?__('sponsoring.cups'):__('sponsoring.'.$item->scope,['slot'=>$item->secondary_slot]) }}</h2>
@if($item->scope==='activity')<h3>{{ $item->activity?->name }}</h3>@elseif($item->scope==='award')<h3>{{ __('sponsoring.'.$item->purpose) }}</h3>@endif
<p>{{ $item->{'catalog_'.app()->getLocale()}?:$item->catalog_fr }}</p><p class="price">{{ $item->catalog_price_cents===null?__('sponsoring.quote'):\App\Domain\Finance\Money::format($item->catalog_price_cents) }} @if($item->catalog_price_cents!==null)<small>{{ __('sponsoring.ttc') }}</small>@endif</p>
@if($item->structural())<p><strong>{{ __('sponsoring.own_stand') }}</strong></p>@if($item->stand&&($preview||($item->stand->is_public&&$item->stand->status!=='withdrawn')))<a href="{{ route($preview?'stand.preview':'stand.show',['project'=>$project->slug,'stand'=>$item->stand->public_id]) }}">{{ __('sponsoring.details') }} →</a>@endif @endif
<p>{{ $item->catalogAvailable()?__('sponsoring.free'):__('sponsoring.allocated') }}</p>@if($item->visible())<p>{{ $item->sponsor_name }}</p>@endif
@if($item->catalogAvailable())<a class="qapas-button" href="{{ route($catalogRoute,['project'=>$project->slug,'target'=>'event','slot'=>$item->public_id]) }}#request">{{ __('sponsoring.choose_slot') }} →</a>@endif
</article>@empty<p>{{ __('sponsoring.empty') }}</p>@endforelse</div>
<p>{{ __('sponsoring.specific_note') }}</p><p>{{ __('sponsoring.print_note') }}</p>
@endif
<p>{{ __('sponsoring.indicative') }}</p>
@if(($target==='stand'&&$stand&&$allocation['available']>0)||($target==='event'&&$slot&&$slot->catalogAvailable()))
<section class="event-section event-card" id="request"><h2>{{ __('sponsoring.request') }}</h2><p>{{ __('sponsoring.request_intro') }}</p>
@if($target==='event')<p><strong>{{ $slot->purpose==='cups'?__('sponsoring.cups'):__('sponsoring.'.$slot->scope,['slot'=>$slot->secondary_slot]) }} @if($slot->activity) · {{ $slot->activity->name }} @endif @if($slot->scope==='award') · {{ __('sponsoring.'.$slot->purpose) }} @endif</strong></p>@endif
@if($errors->any())<div class="notice" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
@php($canSubmit=!$preview&&config('events.privacy_ready')&&filled(config('events.organizer_name'))&&filter_var(config('events.contact_email'),FILTER_VALIDATE_EMAIL))
<form class="event-form" method="post" action="{{ route('sponsoring.store',['project'=>$project->slug]) }}">@csrf
<fieldset @disabled(!$canSubmit)><input type="hidden" name="target" value="{{ $target }}">
@if($target==='stand')<input type="hidden" name="stand" value="{{ $stand->public_id }}"><label>{{ __('sponsoring.share') }}<select name="package" required>@for($i=1;$i<=5;$i++)@if($i<5||$allocation['exclusive_available'])<option value="{{ $i }}" @disabled($i>$allocation['available']) @selected((int)old('package',1)===$i)>{{ $i===5?__('sponsoring.exclusive'):($i*20).' %' }} · {{ $allocation['unit_cents']===null?__('sponsoring.quote'):\App\Domain\Finance\Money::format($allocation['unit_cents']*$i).' '.__('sponsoring.ttc') }}</option>@endif @endfor</select></label>
@else<input type="hidden" name="slot" value="{{ $slot->public_id }}">@endif
<label>{{ __('sponsoring.name') }}<input name="name" maxlength="120" required autocomplete="name" value="{{ old('name') }}"></label><label>{{ __('sponsoring.email') }}<input name="email" type="email" maxlength="254" required autocomplete="email" value="{{ old('email') }}"></label><label>{{ __('sponsoring.message') }}<textarea name="message" maxlength="3000" rows="4">{{ old('message') }}</textarea></label>
<div class="trap" aria-hidden="true"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div>
<label class="check"><input type="checkbox" name="privacy" value="1" required @checked(old('privacy'))><span>{{ __('events.privacy_ack') }} <a href="{{ route('privacy') }}">{{ __('events.privacy') }}</a></span></label><label class="check"><input type="checkbox" name="marketing_opt_in" value="1" @checked(old('marketing_opt_in'))><span>{{ __('events.marketing') }}</span></label><button class="qapas-button" type="submit">{{ __('sponsoring.send') }} →</button></fieldset>
</form>@if(!$canSubmit)<p class="notice">{{ $preview?__('sponsoring.preview_form'):__('events.privacy_closed') }}</p>@endif
</section>@endif
</div></x-layout>
