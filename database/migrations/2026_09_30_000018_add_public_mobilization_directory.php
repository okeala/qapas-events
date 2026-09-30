<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('freguesia_invitations',function(Blueprint $t){$t->id();$t->uuid('public_id')->unique();$t->foreignId('event_project_id')->constrained()->restrictOnDelete();$t->foreignId('prospect_id')->nullable()->constrained()->nullOnDelete();$t->string('source_key');$t->string('name');$t->string('municipality');$t->string('status')->default('planned');$t->boolean('is_public')->default(false);$t->text('summary_fr')->nullable();$t->text('summary_pt')->nullable();$t->string('source_url',1000)->nullable();$t->timestamp('invited_at')->nullable();$t->text('invitation_evidence')->nullable();$t->timestamp('accepted_at')->nullable();$t->text('acceptance_evidence')->nullable();$t->timestamps();$t->unique(['event_project_id','source_key']);});
  Schema::table('teams',function(Blueprint $t){$t->foreignId('freguesia_invitation_id')->nullable()->constrained()->restrictOnDelete();$t->boolean('is_public')->default(false);$t->boolean('is_demo')->default(false);$t->string('demo_key')->nullable()->unique();$t->text('summary_fr')->nullable();$t->text('summary_pt')->nullable();});
  Schema::table('stand_partners',function(Blueprint $t){$t->boolean('is_public')->default(false);$t->string('public_address',500)->nullable();$t->string('public_hours',500)->nullable();$t->text('summary_fr')->nullable();$t->text('summary_pt')->nullable();$t->decimal('latitude',10,7)->nullable();$t->decimal('longitude',10,7)->nullable();$t->text('location_evidence')->nullable();});
 }
 public function down(): void {Schema::table('stand_partners',fn(Blueprint $t)=>$t->dropColumn(['is_public','public_address','public_hours','summary_fr','summary_pt','latitude','longitude','location_evidence']));Schema::table('teams',function(Blueprint $t){$t->dropConstrainedForeignId('freguesia_invitation_id');$t->dropColumn(['is_public','is_demo','demo_key','summary_fr','summary_pt']);});Schema::dropIfExists('freguesia_invitations');}
};
