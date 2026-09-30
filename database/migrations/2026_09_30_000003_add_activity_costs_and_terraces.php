<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('event_projects',function(Blueprint $t){$t->string('plan_image')->nullable();$t->boolean('plan_is_public')->default(false);});
  Schema::create('terraces',function(Blueprint $t){
   $t->id();$t->uuid('public_id')->unique();$t->foreignId('event_project_id')->constrained()->restrictOnDelete();$t->string('name');$t->json('boundary');$t->string('access')->default('restricted');$t->boolean('is_public')->default(false);$t->text('notes')->nullable();$t->timestamps();
  });
  Schema::table('activities',function(Blueprint $t){
   $t->string('template_key')->nullable();$t->text('summary')->nullable();$t->text('original_concept')->nullable();$t->text('scoring')->nullable();$t->text('operator_requirements')->nullable();$t->text('technical_review')->nullable();$t->text('media_plan')->nullable();
   $t->string('risk_category')->default('manual');$t->string('access')->default('team');$t->boolean('is_public')->default(false);$t->boolean('broadcast_planned')->default(false);$t->boolean('materials_complete')->default(false);$t->unsignedInteger('planned_runs')->default(1);$t->unsignedInteger('sort_order')->default(100);
   $t->foreignId('terrace_id')->nullable()->constrained()->restrictOnDelete();$t->foreignId('spectator_terrace_id')->nullable()->constrained('terraces')->restrictOnDelete();$t->decimal('map_x',9,6)->nullable();$t->decimal('map_y',9,6)->nullable();
   $t->unique(['event_project_id','template_key']);
  });
  Schema::create('activity_materials',function(Blueprint $t){
   $t->id();$t->uuid('public_id')->unique();$t->foreignId('activity_id')->constrained()->restrictOnDelete();$t->string('name');$t->unsignedInteger('quantity')->nullable();$t->string('unit')->default('pièce');$t->string('basis')->default('fixed');$t->string('procurement')->default('purchase');$t->unsignedInteger('unit_gross_cents')->nullable();$t->unsignedInteger('vat_basis_points')->nullable();$t->boolean('deductible')->default(false);$t->text('evidence')->nullable();$t->timestamps();
  });
  Schema::create('activity_scenario',function(Blueprint $t){$t->id();$t->foreignId('activity_id')->constrained()->restrictOnDelete();$t->foreignId('scenario_id')->constrained()->restrictOnDelete();$t->unique(['activity_id','scenario_id']);});
 }
 public function down(): void {
  Schema::dropIfExists('activity_scenario');Schema::dropIfExists('activity_materials');
  Schema::table('activities',function(Blueprint $t){$t->dropForeign(['terrace_id']);$t->dropForeign(['spectator_terrace_id']);$t->dropUnique(['event_project_id','template_key']);$t->dropColumn(['template_key','summary','original_concept','scoring','operator_requirements','technical_review','media_plan','risk_category','access','is_public','broadcast_planned','materials_complete','planned_runs','sort_order','terrace_id','spectator_terrace_id','map_x','map_y']);});
  Schema::dropIfExists('terraces');Schema::table('event_projects',fn(Blueprint $t)=>$t->dropColumn(['plan_image','plan_is_public']));
 }
};
