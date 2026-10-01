<?php
use App\Http\Controllers\PublicEventController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
Route::get('/',[PublicEventController::class,'home'])->name('home');
Route::get('/events/forqua-de-ouro',function(){
 $project=\App\Models\EventProject::where('slug','os-jogos-do-agricultor')->where('is_public',true)->first();
 if($project)return redirect()->route('event.show',['project'=>$project->slug],301);
 $old=\App\Models\EventProject::where('slug','forqua-de-ouro')->firstOrFail();return app(PublicEventController::class)->show($old);
});
Route::get('/events/{project:slug}',[PublicEventController::class,'show'])->name('event.show');
Route::post('/events/{project:slug}/interest',[PublicEventController::class,'interest'])->middleware('throttle:interest')->name('event.interest');
Route::get('/events/{project:slug}/candidature',[\App\Http\Controllers\RegistrationController::class,'show'])->name('registration.show');
Route::post('/events/{project:slug}/candidature',[\App\Http\Controllers\RegistrationController::class,'store'])->middleware('throttle:interest')->name('registration.store');
Route::get('/candidatures/{registration}',[\App\Http\Controllers\RegistrationController::class,'status'])->middleware(['signed','throttle:60,1'])->name('registration.status');
Route::post('/candidatures/{registration}/payer',[\App\Http\Controllers\RegistrationController::class,'pay'])->middleware(['signed','throttle:6,1'])->name('registration.pay');
Route::post('/payments/stripe/webhook',[\App\Http\Controllers\RegistrationController::class,'webhook'])->middleware('throttle:120,1')->name('registration.webhook');
Route::view('/privacy','public.privacy')->name('privacy');
Route::post('/language',function(Request $request){
 $data=$request->validate(['locale'=>['required',Rule::in(config('qapas_application.locales'))]]);
 $request->session()->put('locale',$data['locale']);
 return redirect()->route('home');
})->name('language.set');

Route::get('/events/{project:slug}/epreuves/{activity}',[\App\Http\Controllers\ActivityController::class,'show'])->name('activity.show');
Route::get('/plans/{project}/image',[\App\Http\Controllers\ActivityController::class,'image'])->name('plan.image');
Route::get('/workspace/plans/{project}/image',[\App\Http\Controllers\ActivityController::class,'privateImage'])->name('plan.private-image');
Route::get('/events/{project:slug}/presse/{press}',[\App\Http\Controllers\PressController::class,'show'])->name('press.show');

Route::get('/workspace/consultations/{consultation}/email/{locale}',[\App\Http\Controllers\ConsultationController::class,'email'])->name('consultation.email');

Route::get('/events/{project:slug}/offres/{offer}',[\App\Http\Controllers\CatalogController::class,'offer'])->name('offer.show');
Route::get('/events/{project:slug}/stands/{stand}',[\App\Http\Controllers\CatalogController::class,'stand'])->name('stand.show');
Route::get('/workspace/preview/{project:slug}',[PublicEventController::class,'preview'])->name('event.preview');
Route::get('/workspace/preview/{project:slug}/offres/{offer}',[\App\Http\Controllers\CatalogController::class,'previewOffer'])->name('offer.preview');
Route::get('/workspace/preview/{project:slug}/stands/{stand}',[\App\Http\Controllers\CatalogController::class,'previewStand'])->name('stand.preview');
Route::get('/workspace/preview/{project:slug}/epreuves/{activity}',[\App\Http\Controllers\CatalogController::class,'previewActivity'])->name('activity.preview');

Route::get('/events/{project:slug}/prevente',[\App\Http\Controllers\TicketController::class,'show'])->name('presales.show');
Route::post('/events/{project:slug}/prevente',[\App\Http\Controllers\TicketController::class,'store'])->middleware('throttle:12,1')->name('presales.store');
Route::get('/events/{project:slug}/soutiens',[\App\Http\Controllers\TicketController::class,'listing'])->name('tickets.list');
Route::get('/billets/{ticket}/verifier',[\App\Http\Controllers\TicketController::class,'verify'])->middleware('throttle:60,1')->name('ticket.verify');
Route::get('/billets/{ticket}/recu',[\App\Http\Controllers\TicketController::class,'owner'])->middleware(['signed','throttle:60,1'])->name('ticket.owner');
Route::post('/billets/{ticket}/payer',[\App\Http\Controllers\TicketController::class,'pay'])->middleware(['signed','throttle:6,1'])->name('ticket.pay');
Route::post('/billets/{ticket}/retirer-nom',[\App\Http\Controllers\TicketController::class,'withdrawListing'])->middleware(['signed','throttle:10,1'])->name('ticket.unlist');
Route::get('/relais/connexion',[\App\Http\Controllers\RelayController::class,'login'])->name('relay.login');
Route::post('/relais/connexion',[\App\Http\Controllers\RelayController::class,'authenticate'])->middleware('throttle:5,1')->name('relay.authenticate');
Route::post('/relais/deconnexion',[\App\Http\Controllers\RelayController::class,'logout'])->name('relay.logout');
Route::get('/relais',[\App\Http\Controllers\RelayController::class,'dashboard'])->name('relay.dashboard');
Route::get('/relais/especes',[\App\Http\Controllers\RelayController::class,'cash'])->name('relay.cash');
Route::get('/relais/recus/{ticket}',[\App\Http\Controllers\RelayController::class,'receipt'])->name('relay.receipt');
Route::get('/workspace/billets/{ticket}',[\App\Http\Controllers\TicketController::class,'control'])->name('ticket.control');
Route::post('/workspace/billets/{ticket}/controler',[\App\Http\Controllers\TicketController::class,'redeem'])->middleware('throttle:30,1')->name('ticket.redeem');

Route::get('/workspace/tournees/{visit}/courriers',[\App\Http\Controllers\OutreachController::class,'letter'])->name('outreach.letter');

Route::post('/workspace/billets/{ticket}/avantages',[\App\Http\Controllers\TicketController::class,'benefit'])->middleware('throttle:30,1')->name('ticket.benefit');

Route::get('/events/{project:slug}/plantation',[\App\Http\Controllers\PlantingController::class,'show'])->name('planting.show');
Route::post('/billets/{ticket}/plantation',[\App\Http\Controllers\PlantingController::class,'reserve'])->middleware(['signed','throttle:10,1'])->name('planting.reserve');
Route::post('/billets/{ticket}/arbres/{slot}/retirer-nom',[\App\Http\Controllers\PlantingController::class,'unlist'])->middleware(['signed','throttle:10,1'])->name('planting.unlist');

Route::get('/workspace/campagne/{poster}',function(string $poster){abort_unless(auth('admin')->user()?->is_active,403);abort_unless(in_array($poster,['01-teaser','02-candidatures','03-programme'],true),404);return response()->file(resource_path('campaign/'.$poster.'.webp'),['Content-Type'=>'image/webp','Cache-Control'=>'private, no-store','X-Robots-Tag'=>'noindex, nofollow']);})->name('campaign.poster');

Route::get('/workspace/preview/{project:slug}/plantation',[\App\Http\Controllers\PlantingController::class,'preview'])->name('planting.preview');

Route::post('/workspace/billets/{ticket}/boissons',[\App\Http\Controllers\TicketController::class,'drinks'])->middleware('throttle:30,1')->name('ticket.drinks');
Route::get('/signaler/{point}',[\App\Http\Controllers\CommunityController::class,'report'])->middleware('throttle:60,1')->name('incident.report');
Route::post('/signaler/{point}',[\App\Http\Controllers\CommunityController::class,'submitReport'])->middleware('throttle:6,1')->name('incident.submit');
Route::get('/workspace/plaques/{point}',[\App\Http\Controllers\CommunityController::class,'pointSheet'])->name('incident.sheet');
Route::get('/merci/{invitation}',[\App\Http\Controllers\CommunityController::class,'feedback'])->middleware('throttle:30,1')->name('feedback.show');
Route::post('/merci/{invitation}',[\App\Http\Controllers\CommunityController::class,'saveFeedback'])->middleware('throttle:6,1')->name('feedback.submit');
Route::get('/events/{project:slug}/rallye',[\App\Http\Controllers\CommunityController::class,'rally'])->name('rally.show');
Route::get('/events/{project:slug}/engagements',[\App\Http\Controllers\CommunityController::class,'commitments'])->name('community.show');
Route::get('/events/{project:slug}/blog',[\App\Http\Controllers\CommunityController::class,'blog'])->name('blog.index');
Route::get('/events/{project:slug}/blog/{post}',[\App\Http\Controllers\CommunityController::class,'article'])->name('blog.show');

Route::get('/workspace/sponsors/{sponsorship}/plaque',[\App\Http\Controllers\CommunityController::class,'sponsorSheet'])->name('sponsor.sheet');

Route::get('/workspace/preview/{project:slug}/rallye',[\App\Http\Controllers\CommunityController::class,'previewRally'])->name('rally.preview');
Route::get('/workspace/preview/{project:slug}/engagements',[\App\Http\Controllers\CommunityController::class,'previewCommunity'])->name('community.preview');
Route::get('/workspace/preview/{project:slug}/blog',[\App\Http\Controllers\CommunityController::class,'previewBlog'])->name('blog.preview');
Route::get('/workspace/preview/{project:slug}/blog/{post}',[\App\Http\Controllers\CommunityController::class,'previewArticle'])->name('blog.article-preview');

Route::get('/badges/{badge}/verifier',[\App\Http\Controllers\BadgeController::class,'verify'])->middleware('throttle:60,1')->name('badge.verify');
Route::get('/workspace/badges/{badge}',[\App\Http\Controllers\BadgeController::class,'print'])->name('badge.print');

Route::get('/events/{project:slug}/communes',[\App\Http\Controllers\MobilizationController::class,'communes'])->name('mobilization.communes');
Route::get('/events/{project:slug}/communes/{commune}',[\App\Http\Controllers\MobilizationController::class,'commune'])->name('mobilization.commune');
Route::get('/events/{project:slug}/equipes',[\App\Http\Controllers\MobilizationController::class,'teams'])->name('mobilization.teams');
Route::get('/events/{project:slug}/equipes/{team}',[\App\Http\Controllers\MobilizationController::class,'team'])->name('mobilization.team');
Route::get('/events/{project:slug}/relais-locaux',[\App\Http\Controllers\MobilizationController::class,'relays'])->name('mobilization.relays');
Route::get('/events/{project:slug}/partenaires',[\App\Http\Controllers\MobilizationController::class,'sponsors'])->name('mobilization.sponsors');
Route::get('/events/{project:slug}/grandir',[\App\Http\Controllers\MobilizationController::class,'growth'])->name('mobilization.growth');

Route::get('/events/{project:slug}/cabanes',[\App\Http\Controllers\CabinController::class,'index'])->name('cabins.index');
Route::get('/workspace/preview/{project:slug}/cabanes',[\App\Http\Controllers\CabinController::class,'preview'])->name('cabins.preview');

Route::get('/events/{project:slug}/parrainer',[\App\Http\Controllers\SponsoringController::class,'index'])->name('sponsoring.index');
Route::post('/events/{project:slug}/parrainer',[\App\Http\Controllers\SponsoringController::class,'store'])->middleware('throttle:interest')->name('sponsoring.store');
Route::get('/workspace/preview/{project:slug}/parrainer',[\App\Http\Controllers\SponsoringController::class,'preview'])->name('sponsoring.preview');
