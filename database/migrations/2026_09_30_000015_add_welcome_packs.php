<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {Schema::create('welcome_pack_plans',function(Blueprint $t){
  $t->id();$t->uuid('public_id')->unique();$t->foreignId('event_project_id')->unique()->constrained()->restrictOnDelete();$t->string('name');
  $t->foreignId('sponsor_id')->nullable()->constrained('sponsorships')->restrictOnDelete();
  foreach(['shirt_cost_line_id','setup_cost_line_id','delivery_cost_line_id'] as $column)$t->foreignId($column)->nullable()->constrained('budget_lines')->restrictOnDelete();
  $t->json('cohorts');$t->json('sizes');$t->json('people')->nullable();$t->boolean('use_roster')->default(false);$t->unsignedTinyInteger('shirts_per_person')->default(1);$t->unsignedTinyInteger('reserve_percent')->default(20);
  $t->text('specification')->nullable();$t->text('assumptions')->nullable();$t->timestamp('approved_at')->nullable();$t->foreignId('approved_by')->nullable()->constrained('admins')->restrictOnDelete();$t->text('approval_evidence')->nullable();$t->timestamps();
 });}
 public function down(): void {Schema::dropIfExists('welcome_pack_plans');}
};
