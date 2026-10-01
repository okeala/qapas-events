<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('event_projects',fn(Blueprint $t)=>$t->string('financial_policy_version')->nullable());
  Schema::create('financial_plans',function(Blueprint $t){
   $t->id();$t->uuid('public_id')->unique();$t->foreignId('scenario_id')->unique()->constrained()->restrictOnDelete();
   $t->string('vat_regime')->default('unknown');$t->text('vat_evidence')->nullable();$t->text('assumptions')->nullable();
   $t->bigInteger('opening_cash_cents')->default(0);$t->unsignedBigInteger('opening_vat_credit_cents')->default(0);$t->unsignedBigInteger('opening_vat_due_cents')->default(0);$t->unsignedBigInteger('minimum_cash_cents')->default(0);
   $t->unsignedInteger('ticket_target')->default(0);$t->unsignedInteger('visitor_target')->default(0);$t->json('phases');$t->json('financing')->nullable();$t->json('organizer_cash_schedule')->nullable();$t->text('organizer_schedule_evidence')->nullable();$t->timestamps();
  });
  Schema::create('financial_profiles',function(Blueprint $t){
   $t->id();$t->uuid('public_id')->unique();$t->foreignId('financial_plan_id')->constrained()->restrictOnDelete();
   $t->string('source_type');$t->unsignedBigInteger('source_id');$t->string('name');$t->string('source_kind');$t->string('category')->default('other');$t->string('behavior')->default('fixed');$t->string('driver')->default('source');
   $t->boolean('is_stock')->default(false);$t->string('invoice_phase')->nullable();$t->string('consumption_phase')->nullable();$t->json('cash_schedule')->nullable();$t->json('vat_schedule')->nullable();$t->text('schedule_evidence')->nullable();
   $t->string('vat_fingerprint',64)->nullable();$t->string('vat_treatment')->default('pending');$t->unsignedInteger('deduction_basis_points')->default(0);$t->text('vat_evidence')->nullable();$t->unsignedBigInteger('depreciation_unit_cents')->nullable();$t->timestamps();
   $t->unique(['financial_plan_id','source_type','source_id'],'financial_profile_source_unique');
  });
 }
 public function down(): void {Schema::dropIfExists('financial_profiles');Schema::dropIfExists('financial_plans');Schema::table('event_projects',fn(Blueprint $t)=>$t->dropColumn('financial_policy_version'));}
};
