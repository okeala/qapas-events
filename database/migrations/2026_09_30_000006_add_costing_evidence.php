<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  foreach(['budget_lines','activity_materials','site_needs'] as $table)Schema::table($table,function(Blueprint $t){$t->string('pricing_status')->default('manual');$t->text('price_source')->nullable();$t->date('price_checked_at')->nullable();});
  Schema::table('budget_lines',function(Blueprint $t){$t->string('costing_key')->nullable();$t->string('unit')->default('unité');$t->string('expense_type')->default('operating');$t->unique(['scenario_id','costing_key']);});
  Schema::table('activity_materials',fn(Blueprint $t)=>$t->string('shared_cost_key')->nullable());
 }
 public function down(): void {
  Schema::table('activity_materials',fn(Blueprint $t)=>$t->dropColumn('shared_cost_key'));
  Schema::table('budget_lines',function(Blueprint $t){$t->dropUnique(['scenario_id','costing_key']);$t->dropColumn(['costing_key','unit','expense_type']);});
  foreach(['budget_lines','activity_materials','site_needs'] as $table)Schema::table($table,fn(Blueprint $t)=>$t->dropColumn(['pricing_status','price_source','price_checked_at']));
 }
};
