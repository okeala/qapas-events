<?php

use App\Http\Controllers\PlannerPlanController;
use App\Http\Controllers\PlannerPublicController;
use Illuminate\Support\Facades\Route;

Route::get('/visites', [PlannerPublicController::class, 'index'])->name('events.index');
Route::get('/visites/{event:slug}/plan.json', [PlannerPublicController::class, 'data'])->name('events.data');
Route::get('/visites/{event:slug}/plan.geojson', [PlannerPublicController::class, 'geojson'])->name('events.geojson');
Route::get('/visites/{event:slug}/plan.svg', [PlannerPublicController::class, 'svg'])->name('events.svg');
Route::get('/visites/{event:slug}/plan.pdf', [PlannerPublicController::class, 'pdf'])->name('events.pdf');
Route::get('/visites/{event:slug}/poster', [PlannerPublicController::class, 'poster'])->name('events.poster');
Route::get('/visites/{event:slug}/offers/{offer}/candidate', [PlannerPublicController::class, 'candidate'])->name('events.candidate');
Route::post('/visites/{event:slug}/offers/{offer}/candidate', [PlannerPublicController::class, 'submitCandidate'])->middleware(['throttle:10,1'])->name('events.candidate.submit');
Route::get('/visites/{event:slug}', [PlannerPublicController::class, 'show'])->name('events.show');

Route::prefix('planner')->group(function (): void {
    Route::get('/scenarios/{scenario}/plan.json', [PlannerPlanController::class, 'data'])->name('planner.plan.data');
    Route::post('/scenarios/{scenario}/elements', [PlannerPlanController::class, 'save'])->name('planner.plan.save');
    Route::get('/scenarios/{scenario}/inventory.csv', [PlannerPlanController::class, 'export'])->name('planner.export');
});
