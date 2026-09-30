<?php
namespace App\Providers;
use App\Models\EventProject;
use App\Policies\WorkspacePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\ServiceProvider;
class AppServiceProvider extends ServiceProvider {
 public function boot(): void {
  $this->loadViewsFrom(base_path('packages/qapas-shared/resources/views'),'shared');
  foreach (['ActivityTrial','CostConsultation','SupplierQuote','CupPlan','CupAllocation','EventProject','Scenario','BudgetLine','Offer','Interest','Idea','LegalRequirement','Team','RegistrationCampaign','CandidateRegistration','DrinkCredit','TeamRoleAssignment','Activity','RunItem','Incident','Stand','Debrief','Terrace','ActivityMaterial','SiteFeature','SiteNeed','ActivityLocation','StandPartner','ProgramSlot','PressRelease','FurniturePlan','CateringService','Prospect'] as $model) Gate::policy('App\\Models\\'.$model,WorkspacePolicy::class);
  EventProject::created(function (EventProject $project): void {
   foreach (config('events.requirements') as $code=>$label) $project->requirements()->create(['code'=>$code,'name'=>$label]);
  });
  RateLimiter::for('interest',fn ($request)=>[Limit::perMinute(3)->by($request->ip()),Limit::perDay(20)->by($request->ip())]);
 }
}
