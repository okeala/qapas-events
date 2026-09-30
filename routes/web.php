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
