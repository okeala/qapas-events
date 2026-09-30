<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('event_projects',fn(Blueprint $t)=>$t->string('commercial_policy_version')->nullable());
  Schema::table('scenarios',function(Blueprint $t){$t->unsignedInteger('minimum_organizer_charges_cents')->default(0);$t->text('organizer_cost_evidence')->nullable();});
  Schema::create('merchandising_options',function(Blueprint $t){
   $t->id();$t->uuid('public_id')->unique();$t->foreignId('welcome_pack_plan_id')->constrained()->restrictOnDelete();$t->string('name');$t->string('method');$t->json('costs');$t->unsignedInteger('prototype_quantity')->default(2);$t->unsignedInteger('setup_minutes')->default(0);$t->unsignedInteger('minutes_per_piece')->nullable();$t->unsignedInteger('hourly_cost_cents')->nullable();$t->unsignedInteger('machine_cost_cents')->nullable();$t->unsignedInteger('other_fixed_cents')->nullable();$t->unsignedInteger('external_unit_cents')->nullable();$t->unsignedInteger('external_fixed_cents')->nullable();$t->unsignedInteger('opportunity_hourly_cents')->nullable();$t->unsignedInteger('qapas_minutes')->default(0);$t->text('source')->nullable();$t->text('specification')->nullable();$t->text('sample_evidence')->nullable();$t->timestamp('sample_approved_at')->nullable();$t->timestamps();$t->unique(['welcome_pack_plan_id','method']);
  });
  Schema::create('event_badges',function(Blueprint $t){
   $t->id();$t->uuid('public_id')->unique();$t->foreignId('event_project_id')->constrained()->restrictOnDelete();$t->foreignId('welcome_pack_plan_id')->constrained()->restrictOnDelete();$t->string('person_key');$t->string('active_key')->nullable();$t->string('role');$t->string('status')->default('draft');$t->string('serial',20)->unique();$t->timestamp('valid_from')->nullable();$t->timestamp('valid_until')->nullable();$t->timestamp('issued_at')->nullable();$t->timestamp('revoked_at')->nullable();$t->text('evidence')->nullable();$t->text('revocation_reason')->nullable();$t->timestamps();$t->unique(['event_project_id','active_key']);
  });
 }
 public function down(): void {Schema::dropIfExists('event_badges');Schema::dropIfExists('merchandising_options');Schema::table('scenarios',fn(Blueprint $t)=>$t->dropColumn(['minimum_organizer_charges_cents','organizer_cost_evidence']));Schema::table('event_projects',fn(Blueprint $t)=>$t->dropColumn('commercial_policy_version'));}
};
