<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {Schema::create('commercial_plans',function(Blueprint $t){$t->id();$t->uuid('public_id')->unique();$t->foreignId('scenario_id')->unique()->constrained()->restrictOnDelete();$t->string('name');$t->unsignedInteger('independent_price_cents')->default(65000);$t->unsignedInteger('vat_basis_points')->default(2300);$t->unsignedInteger('unknown_allowance_cents')->default(200000);$t->unsignedInteger('rounding_cents')->default(5000);$t->text('assumptions')->nullable();$t->timestamp('applied_at')->nullable();$t->timestamps();});}
 public function down(): void {Schema::dropIfExists('commercial_plans');}
};
