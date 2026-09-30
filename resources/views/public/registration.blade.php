<x-layout>
 <section class="event-hero event-hero--small"><div><p class="eyebrow">{{ $project->name }}</p><h1>{{ __('registration.title') }}</h1><p class="lead">{{ __('registration.intro') }}</p></div></section>
 <div class="event-content prose">
 @if(!$open)
 <p class="notice">{{ __('registration.closed') }}</p>
 @else
 @if(!config('registration.live'))<p class="notice">{{ __('registration.test') }}</p>@endif
 <h2>{{ __('registration.conditions') }} · {{ $campaign->terms_version }}</h2><p>{{ $campaign->{'terms_'.(app()->getLocale()==='pt'?'pt':'fr')} }}</p>
 <h2>{{ __('registration.refund') }}</h2><p>{{ $campaign->{'refund_policy_'.(app()->getLocale()==='pt'?'pt':'fr')} }}</p>
 @if($errors->any())<div class="notice" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
 <form class="event-form" method="post" action="{{ route('registration.store',['project'=>$project->slug]) }}">
 @csrf
 <input type="hidden" name="terms_version" value="{{ $campaign->terms_version }}">
 <label>{{ __('events.name') }}<input name="name" value="{{ old('name') }}" maxlength="120" autocomplete="name" required></label>
 <label>Email<input name="email" type="email" value="{{ old('email') }}" maxlength="254" autocomplete="email" required></label>
 <label>Freguesia<input name="freguesia" value="{{ old('freguesia') }}" maxlength="120" required></label>
 <fieldset><legend>{{ __('events.expert_roles_select') }}</legend>
 @foreach(\App\Domain\Teams\ExpertRoles::CODES as $role)
 <label class="check"><input type="checkbox" name="expert_roles[]" value="{{ $role }}" @checked(in_array($role,(array)old('expert_roles',[]),true))><span>{{ __('events.roles.'.$role) }}</span></label>
 @endforeach
 </fieldset>
 <label class="check"><input type="checkbox" name="terms" value="1" required><span>{{ __('registration.accept') }}</span></label>
 <label class="check"><input type="checkbox" name="privacy" value="1" required><span>{{ __('registration.privacy') }} <a href="{{ route('privacy') }}">{{ __('events.privacy') }}</a></span></label>
 <div class="trap" aria-hidden="true"><input name="website" tabindex="-1" autocomplete="off"></div>
 <button class="qapas-button" type="submit">{{ __('registration.submit') }} →</button>
 </form>
 @endif
 <a class="text-link" href="{{ route('event.show',['project'=>$project->slug]) }}">{{ __('registration.back') }}</a>
 </div>
</x-layout>
