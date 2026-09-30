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
Route::view('/privacy','public.privacy')->name('privacy');
Route::post('/language',function(Request $request){
 $data=$request->validate(['locale'=>['required',Rule::in(config('qapas_application.locales'))]]);
 $request->session()->put('locale',$data['locale']);
 return redirect()->route('home');
})->name('language.set');

Route::get('/events/{project:slug}/epreuves/{activity}',[\App\Http\Controllers\ActivityController::class,'show'])->name('activity.show');
Route::get('/plans/{project}/image',[\App\Http\Controllers\ActivityController::class,'image'])->name('plan.image');
Route::get('/workspace/plans/{project}/image',[\App\Http\Controllers\ActivityController::class,'privateImage'])->name('plan.private-image');
