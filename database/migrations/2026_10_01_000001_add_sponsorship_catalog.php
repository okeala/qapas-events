<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('event_projects',function(Blueprint $t){$t->string('sponsorship_policy_version')->nullable();$t->string('sponsorship_visibility')->default('hidden');});
  Schema::table('scenarios',fn(Blueprint $t)=>$t->unsignedInteger('sponsor_stand_target')->default(0));
  Schema::table('commercial_plans',fn(Blueprint $t)=>$t->unsignedInteger('village_price_cents')->default(50000));
  Schema::table('stands',function(Blueprint $t){$t->unsignedInteger('sponsorship_total_cents')->nullable();$t->text('sponsorship_price_evidence')->nullable();});
  Schema::table('stand_partners',function(Blueprint $t){$t->unsignedTinyInteger('share_units')->default(0);$t->boolean('exclusive_sponsorship')->default(false);$t->unsignedInteger('reference_total_cents')->nullable();});
  Schema::table('sponsorships',function(Blueprint $t){$t->foreignId('stand_id')->nullable()->constrained()->restrictOnDelete();$t->boolean('catalog_visible')->default(false);$t->unsignedInteger('catalog_price_cents')->nullable();$t->text('catalog_fr')->nullable();$t->text('catalog_pt')->nullable();});
  Schema::table('interests',fn(Blueprint $t)=>$t->json('sponsorship_request')->nullable());
 }
 public function down(): void {
  Schema::table('interests',fn(Blueprint $t)=>$t->dropColumn('sponsorship_request'));
  Schema::table('sponsorships',function(Blueprint $t){$t->dropConstrainedForeignId('stand_id');$t->dropColumn(['catalog_visible','catalog_price_cents','catalog_fr','catalog_pt']);});
  Schema::table('stand_partners',fn(Blueprint $t)=>$t->dropColumn(['share_units','exclusive_sponsorship','reference_total_cents']));
  Schema::table('stands',fn(Blueprint $t)=>$t->dropColumn(['sponsorship_total_cents','sponsorship_price_evidence']));
  Schema::table('commercial_plans',fn(Blueprint $t)=>$t->dropColumn('village_price_cents'));
  Schema::table('scenarios',fn(Blueprint $t)=>$t->dropColumn('sponsor_stand_target'));
  Schema::table('event_projects',fn(Blueprint $t)=>$t->dropColumn(['sponsorship_policy_version','sponsorship_visibility']));
 }
};
