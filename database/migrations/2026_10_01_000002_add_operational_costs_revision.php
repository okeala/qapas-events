<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {Schema::table('event_projects',fn(Blueprint $t)=>$t->string('operational_costs_version')->nullable());}
 public function down(): void {Schema::table('event_projects',fn(Blueprint $t)=>$t->dropColumn('operational_costs_version'));}
};
