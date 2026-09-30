<?php
namespace App\Domain\Registration;
use App\Models\RegistrationCampaign;
final class RegistrationGate {
 public function blockers(RegistrationCampaign $campaign): array {
  $cashPlan=\App\Models\PresalePlan::where('event_project_id',$campaign->event_project_id)->first();$cashReady=$cashPlan?->is_open&&!$cashPlan->blockers();$missing=[];if($campaign->vote_closed_at)$missing[]='Vote déjà clôturé';$project=$campaign->eventProject;
  if(!$project?->is_public)$missing[]='Édition publique requise';
  if(!config('events.privacy_ready')||blank(config('events.organizer_name'))||!filter_var(config('events.contact_email'),FILTER_VALIDATE_EMAIL))$missing[]='Collecte et coordonnées à valider';
  foreach(['terms_fr','terms_pt','refund_policy_fr','refund_policy_pt','billing_procedure','validation_evidence'] as $field)if(blank($campaign->$field))$missing[]='À compléter : '.$field;
  if($campaign->vat_basis_points===null)$missing[]='Traitement IVA de l’inscription et de sa contrepartie à qualifier';
  if(!$cashReady&&$campaign->vat_basis_points>0&&!preg_match('/^txr_[A-Za-z0-9]+$/',(string)$campaign->stripe_tax_rate_id))$missing[]='Taux IVA inclusif Stripe à configurer';
  if(!$campaign->closes_at||$campaign->closes_at->isPast())$missing[]='Date limite des candidatures à fixer';
  if($project&&$project->ends_at&&$campaign->closes_at&&$campaign->closes_at->gte($project->starts_at))$missing[]='Clore les candidatures avant l’événement et organiser le vote';
  if($project)$missing=array_merge($missing,app(\App\Domain\Planning\Readiness::class)->blockers($project,'registration'));
  if(!$cashReady&&(!config('registration.enabled')||blank(config('registration.stripe_secret'))||blank(config('registration.webhook_secret'))))$missing[]='Encaissement Stripe à configurer';
  if(!$cashReady&&config('registration.live')&&(!str_starts_with((string)config('registration.stripe_secret'),'sk_live_')||!str_starts_with((string)config('app.url'),'https://')))$missing[]='Clé live et URL HTTPS requises pour les paiements réels';
  if(!$cashReady&&!config('registration.live')&&!str_starts_with((string)config('registration.stripe_secret'),'sk_test_'))$missing[]='Clé de test Stripe requise en mode test';
  return array_values(array_unique($missing));
 }
}
