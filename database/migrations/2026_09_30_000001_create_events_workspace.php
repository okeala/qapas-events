<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('admins',function(Blueprint $t){$t->id();$t->string('name');$t->string('email')->unique();$t->string('password');$t->boolean('is_active')->default(true);$t->rememberToken();$t->timestamps();});
  Schema::create('users',function(Blueprint $t){$t->id();$t->uuid('public_id')->unique();$t->string('name');$t->string('email')->unique();$t->string('password');$t->rememberToken();$t->timestamps();});
  Schema::create('event_projects',function(Blueprint $t){
   $t->id();$t->uuid('public_id')->unique();$t->string('name');$t->string('slug')->unique();$t->string('phase')->default('concept');$t->boolean('is_public')->default(false);
   $t->text('product')->nullable();$t->text('price')->nullable();$t->text('place')->nullable();$t->text('promotion')->nullable();
   $t->string('venue')->nullable();$t->dateTime('starts_at')->nullable();$t->dateTime('ends_at')->nullable();$t->unsignedInteger('capacity')->default(0);$t->timestamps();
  });
  Schema::create('scenarios',function(Blueprint $t){
   $t->id();$t->uuid('public_id')->unique();$t->foreignId('event_project_id')->constrained()->restrictOnDelete();$t->string('name');
   $t->unsignedInteger('months')->default(1);$t->unsignedInteger('target_surplus_cents')->default(400000);$t->unsignedInteger('organizer_net_monthly_cents')->default(100000);
   $t->unsignedInteger('organizer_full_monthly_cents')->nullable();$t->boolean('costs_complete')->default(false);
   $t->unsignedInteger('team_target')->default(0);$t->unsignedInteger('stand_target')->default(0);$t->text('assumptions')->nullable();$t->timestamps();
  });
  Schema::create('budget_lines',function(Blueprint $t){
   $t->id();$t->uuid('public_id')->unique();$t->foreignId('scenario_id')->constrained()->restrictOnDelete();$t->string('name');$t->string('kind');
   $t->unsignedInteger('unit_gross_cents');$t->unsignedInteger('vat_basis_points')->nullable();$t->boolean('deductible')->default(false);
   $t->unsignedInteger('forecast_quantity')->default(0);$t->unsignedInteger('committed_quantity')->default(0);$t->unsignedInteger('paid_quantity')->default(0);$t->text('evidence')->nullable();$t->timestamps();
  });
  Schema::create('offers',function(Blueprint $t){
   $t->id();$t->uuid('public_id')->unique();$t->foreignId('event_project_id')->constrained()->restrictOnDelete();$t->string('name');$t->string('kind');
   $t->unsignedInteger('price_gross_cents')->nullable();$t->unsignedInteger('capacity')->default(0);$t->text('includes');$t->text('excludes')->nullable();$t->text('delivery')->nullable();$t->boolean('is_public')->default(false);$t->timestamps();
  });
  Schema::create('interests',function(Blueprint $t){
   $t->id();$t->uuid('public_id')->unique();$t->foreignId('event_project_id')->constrained()->restrictOnDelete();$t->string('profile');$t->string('name');$t->string('email');$t->string('freguesia')->nullable();$t->text('message')->nullable();
   $t->boolean('marketing_opt_in')->default(false);$t->timestamp('privacy_acknowledged_at');$t->string('privacy_version');$t->string('source')->default('direct');$t->string('status')->default('new');$t->timestamps();
  });
  Schema::create('ideas',function(Blueprint $t){
   $t->id();$t->uuid('public_id')->unique();$t->foreignId('event_project_id')->constrained()->restrictOnDelete();$t->string('name');$t->string('pillar')->default('product');$t->text('hypothesis');$t->text('experiment')->nullable();$t->text('evidence')->nullable();$t->string('status')->default('idea');$t->timestamps();
  });
  Schema::create('legal_requirements',function(Blueprint $t){
   $t->id();$t->uuid('public_id')->unique();$t->foreignId('event_project_id')->constrained()->restrictOnDelete();$t->string('code');$t->string('name');$t->string('status')->default('pending');$t->text('evidence')->nullable();$t->string('reviewed_by')->nullable();$t->timestamp('reviewed_at')->nullable();$t->date('expires_at')->nullable();$t->timestamps();$t->unique(['event_project_id','code']);
  });
  Schema::create('teams',function(Blueprint $t){
   $t->id();$t->uuid('public_id')->unique();$t->foreignId('event_project_id')->constrained()->restrictOnDelete();$t->string('name');$t->string('freguesia');$t->string('representative')->nullable();$t->string('ballot_location')->nullable();$t->string('status')->default('forming');$t->text('election_protocol')->nullable();$t->text('election_minutes')->nullable();$t->timestamps();
  });
  Schema::create('activities',function(Blueprint $t){
   $t->id();$t->uuid('public_id')->unique();$t->foreignId('event_project_id')->constrained()->restrictOnDelete();$t->string('name');$t->string('proposer_type')->default('organization');$t->string('proposer_name')->nullable();$t->string('track')->default('public');$t->text('rules');$t->string('referee')->nullable();$t->unsignedInteger('capacity')->default(0);$t->boolean('risk_reviewed')->default(false);$t->text('risk_evidence')->nullable();$t->string('status')->default('idea');$t->timestamps();
  });
  Schema::create('run_items',function(Blueprint $t){
   $t->id();$t->uuid('public_id')->unique();$t->foreignId('event_project_id')->constrained()->restrictOnDelete();$t->string('name');$t->string('owner');$t->string('location')->nullable();$t->dateTime('starts_at');$t->dateTime('ends_at');$t->string('status')->default('planned');$t->text('notes')->nullable();$t->timestamps();
  });
  Schema::create('incidents',function(Blueprint $t){
   $t->id();$t->uuid('public_id')->unique();$t->foreignId('event_project_id')->constrained()->restrictOnDelete();$t->string('name');$t->string('severity')->default('info');$t->string('status')->default('open');$t->string('owner');$t->text('action')->nullable();$t->timestamps();
  });
  Schema::create('audit_entries',function(Blueprint $t){$t->id();$t->foreignId('admin_id')->nullable()->constrained()->nullOnDelete();$t->string('action');$t->string('record_type');$t->uuid('record_public_id');$t->json('changed_fields');$t->timestamp('created_at')->useCurrent();});
  Schema::create('jobs',function(Blueprint $t){$t->id();$t->string('queue')->index();$t->longText('payload');$t->unsignedTinyInteger('attempts');$t->unsignedInteger('reserved_at')->nullable();$t->unsignedInteger('available_at');$t->unsignedInteger('created_at');});
  Schema::create('job_batches',function(Blueprint $t){$t->string('id')->primary();$t->string('name');$t->integer('total_jobs');$t->integer('pending_jobs');$t->integer('failed_jobs');$t->longText('failed_job_ids');$t->mediumText('options')->nullable();$t->integer('cancelled_at')->nullable();$t->integer('created_at');$t->integer('finished_at')->nullable();});
  Schema::create('failed_jobs',function(Blueprint $t){$t->id();$t->string('uuid')->unique();$t->text('connection');$t->text('queue');$t->longText('payload');$t->longText('exception');$t->timestamp('failed_at')->useCurrent();});
 }
 public function down(): void {
  foreach(['failed_jobs','job_batches','jobs','audit_entries','incidents','run_items','activities','teams','legal_requirements','ideas','interests','offers','budget_lines','scenarios','event_projects','users','admins'] as $table) Schema::dropIfExists($table);
 }
};
