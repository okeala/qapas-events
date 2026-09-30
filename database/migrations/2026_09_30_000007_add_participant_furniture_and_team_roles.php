<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('scenarios',function(Blueprint $t){$t->boolean('is_archived')->default(false);$t->boolean('furniture_paid_by_participant')->default(false);});
  Schema::table('stands',function(Blueprint $t){$t->string('furniture_source')->default('rental');$t->unsignedInteger('furniture_quantity')->default(2);$t->unsignedInteger('furniture_unit_gross_cents')->nullable();$t->unsignedInteger('furniture_delivery_cents')->nullable();$t->unsignedInteger('furniture_deposit_cents')->nullable();$t->text('furniture_evidence')->nullable();$t->boolean('furniture_confirmed')->default(false);});
  Schema::table('interests',fn(Blueprint $t)=>$t->json('expert_roles')->nullable());
  Schema::table('teams',fn(Blueprint $t)=>$t->text('role_cumulation_evidence')->nullable());
  Schema::create('team_role_assignments',function(Blueprint $t){$t->id();$t->uuid('public_id')->unique();$t->foreignId('team_id')->constrained()->restrictOnDelete();$t->string('role_code');$t->string('candidate_name')->nullable();$t->string('candidate_reference')->nullable();$t->foreignId('interest_id')->nullable()->constrained()->nullOnDelete();$t->string('status')->default('vacant');$t->boolean('consent_confirmed')->default(false);$t->text('competence_evidence')->nullable();$t->timestamp('reviewed_at')->nullable();$t->foreignId('reviewed_by')->nullable()->constrained('admins')->nullOnDelete();$t->timestamps();$t->unique(['team_id','role_code']);});
 }
 public function down(): void {
  Schema::dropIfExists('team_role_assignments');Schema::table('teams',fn(Blueprint $t)=>$t->dropColumn('role_cumulation_evidence'));Schema::table('interests',fn(Blueprint $t)=>$t->dropColumn('expert_roles'));Schema::table('stands',fn(Blueprint $t)=>$t->dropColumn(['furniture_source','furniture_quantity','furniture_unit_gross_cents','furniture_delivery_cents','furniture_deposit_cents','furniture_evidence','furniture_confirmed']));Schema::table('scenarios',fn(Blueprint $t)=>$t->dropColumn(['is_archived','furniture_paid_by_participant']));
 }
};
