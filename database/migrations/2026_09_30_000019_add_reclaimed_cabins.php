<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('event_projects', function (Blueprint $t) {
            $t->string('cabin_policy_version')->nullable();
            $t->string('cabin_visibility')->default('hidden');
        });
        Schema::create('cabin_projects', function (Blueprint $t) {
            $t->id(); $t->uuid('public_id')->unique();
            $t->foreignId('event_project_id')->constrained()->restrictOnDelete();
            $t->foreignId('stand_id')->unique()->constrained()->restrictOnDelete();
            $t->string('name'); $t->string('supply_mode'); $t->string('status')->default('concept');
            $t->unsignedInteger('width_mm')->default(2400); $t->unsignedInteger('depth_mm')->default(2400); $t->unsignedInteger('height_mm')->default(2400);
            $t->unsignedInteger('frame_diameter_mm')->default(30);
            $t->unsignedInteger('frame_spacing_mm')->nullable();
            $t->string('structural_reviewer')->nullable(); $t->date('structural_reviewed_on')->nullable(); $t->text('structural_evidence')->nullable();
            $t->json('materials')->nullable(); $t->string('owner')->nullable();
            $t->text('harvest_origin')->nullable(); $t->text('control_plan')->nullable();
            $t->string('follow_up_owner')->nullable(); $t->date('follow_up_on')->nullable();
            $t->text('follow_up_notes')->nullable(); $t->text('reception_evidence')->nullable();
            $t->timestamp('reviewed_at')->nullable(); $t->foreignId('reviewed_by')->nullable()->constrained('admins')->restrictOnDelete();
            $t->string('installation_hash',64)->nullable();
            $t->foreignId('cost_line_id')->nullable()->constrained('budget_lines')->restrictOnDelete();
            $t->foreignId('rental_line_id')->nullable()->constrained('budget_lines')->restrictOnDelete();
            $t->string('rental_pricing')->default('unpriced');
            $t->text('rental_terms')->nullable(); $t->unsignedBigInteger('deposit_cents')->nullable();
            $t->unsignedBigInteger('participant_cost_cents')->nullable(); $t->text('participant_cost_evidence')->nullable();
            $t->boolean('is_public')->default(false); $t->text('summary_fr')->nullable(); $t->text('summary_pt')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('cabin_projects');
        Schema::table('event_projects',fn (Blueprint $t) => $t->dropColumn(['cabin_policy_version','cabin_visibility']));
    }
};
