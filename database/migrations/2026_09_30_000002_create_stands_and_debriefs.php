<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('stands',function(Blueprint $t){$t->id();$t->uuid('public_id')->unique();$t->foreignId('event_project_id')->constrained()->restrictOnDelete();$t->string('name');$t->string('kind');$t->string('operator')->nullable();$t->string('zone')->nullable();$t->string('structure')->default('bare');$t->string('status')->default('proposed');$t->text('needs')->nullable();$t->text('material_support')->nullable();$t->timestamps();});
  Schema::create('debriefs',function(Blueprint $t){$t->id();$t->uuid('public_id')->unique();$t->foreignId('event_project_id')->constrained()->restrictOnDelete();$t->string('name');$t->unsignedInteger('observed_visitors')->nullable();$t->string('count_method')->nullable();$t->text('deliveries')->nullable();$t->text('financial_reconciliation')->nullable();$t->text('lessons')->nullable();$t->text('next_decision')->nullable();$t->timestamps();});
 }
 public function down(): void {Schema::dropIfExists('debriefs');Schema::dropIfExists('stands');}
};
