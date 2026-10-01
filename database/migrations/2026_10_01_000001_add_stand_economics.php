<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('cabin_projects',function(Blueprint $t){$t->json('construction_costs')->nullable();$t->string('construction_price_basis')->default('unknown');$t->unsignedInteger('construction_vat_basis_points')->nullable();});
  Schema::create('stand_external_lines',function(Blueprint $t){
   $t->id();$t->uuid('public_id')->unique();$t->foreignId('stand_id')->constrained()->restrictOnDelete();$t->foreignId('scenario_id')->constrained()->restrictOnDelete();$t->string('template_key')->nullable();$t->string('name');$t->string('party');$t->string('holder');$t->string('kind');$t->unsignedInteger('quantity')->nullable();$t->string('unit')->default('lot');$t->unsignedInteger('unit_gross_cents')->nullable();$t->text('evidence')->nullable();$t->foreignId('qapas_budget_line_id')->nullable()->constrained('budget_lines')->restrictOnDelete();$t->timestamps();$t->unique(['stand_id','scenario_id','template_key']);
  });
 }
 public function down(): void {Schema::dropIfExists('stand_external_lines');Schema::table('cabin_projects',fn(Blueprint $t)=>$t->dropColumn(['construction_costs','construction_price_basis','construction_vat_basis_points']));}
};
