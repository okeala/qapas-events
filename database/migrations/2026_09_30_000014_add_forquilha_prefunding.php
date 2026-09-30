<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('event_projects',fn(Blueprint $t)=>$t->string('prize_policy_version')->nullable());
  Schema::table('sponsorships',function(Blueprint $t){$t->foreignId('delivery_cost_line_id')->nullable()->constrained('budget_lines')->restrictOnDelete();$t->text('delivery_terms')->nullable();$t->timestamp('delivery_due_at')->nullable();$t->timestamp('delivered_at')->nullable();$t->text('delivery_evidence')->nullable();});
  Schema::table('community_awards',function(Blueprint $t){$t->foreignId('prize_sponsorship_id')->nullable()->constrained('sponsorships')->restrictOnDelete();$t->timestamp('reserved_at')->nullable();$t->foreignId('reserved_by')->nullable()->constrained('admins')->restrictOnDelete();$t->text('reserve_evidence')->nullable();$t->string('reserve_fingerprint',64)->nullable();$t->text('trophy_brief')->nullable();$t->timestamp('presentation_at')->nullable();$t->text('presentation_evidence')->nullable();});
 }
 public function down(): void {
  Schema::table('community_awards',function(Blueprint $t){$t->dropConstrainedForeignId('prize_sponsorship_id');$t->dropConstrainedForeignId('reserved_by');$t->dropColumn(['reserved_at','reserve_evidence','reserve_fingerprint','trophy_brief','presentation_at','presentation_evidence']);});
  Schema::table('sponsorships',function(Blueprint $t){$t->dropConstrainedForeignId('delivery_cost_line_id');$t->dropColumn(['delivery_terms','delivery_due_at','delivered_at','delivery_evidence']);});
  Schema::table('event_projects',fn(Blueprint $t)=>$t->dropColumn('prize_policy_version'));
 }
};
