<?php
use App\Http\Controllers\PublicEventController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
Route::get('/',[PublicEventController::class,'home'])->name('home');
Route::get('/events/{project:slug}',[PublicEventController::class,'show'])->name('event.show');
Route::post('/events/{project:slug}/interest',[PublicEventController::class,'interest'])->middleware('throttle:interest')->name('event.interest');
Route::view('/privacy','public.privacy')->name('privacy');
Route::post('/language',function(Request $request){
 $data=$request->validate(['locale'=>['required',Rule::in(config('qapas_application.locales'))]]);
 $request->session()->put('locale',$data['locale']);
 return redirect()->route('home');
})->name('language.set');
