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
