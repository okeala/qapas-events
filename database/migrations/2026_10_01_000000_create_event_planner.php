<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planner_events', function (Blueprint $t): void {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->string('slug')->unique();
            $t->string('name');
            $t->json('description')->nullable();
            $t->string('currency', 3)->default('EUR');
            $t->string('timezone')->default('Europe/Lisbon');
            $t->string('venue')->nullable();
            $t->timestamp('starts_at')->nullable();
            $t->timestamp('ends_at')->nullable();
            $t->string('phase')->default('teaser');
            $t->string('decision')->default('pending');
            $t->timestamp('first_signed_at')->nullable();
            $t->timestamp('decision_deadline')->nullable();
            $t->timestamp('decided_at')->nullable();
            $t->unsignedTinyInteger('decision_days')->default(21);
            $t->unsignedBigInteger('published_revision_id')->nullable();
            $t->unsignedBigInteger('confirmed_scenario_id')->nullable();
            $t->boolean('is_demo')->default(false);
            $t->json('settings')->nullable();
            $t->timestamps();
        });
        Schema::create('event_scenarios', function (Blueprint $t): void {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->foreignId('event_id')->constrained('planner_events')->cascadeOnDelete();
            $t->string('name');
            $t->boolean('is_base')->default(false);
            $t->decimal('center_lat', 10, 7)->default(0);
            $t->decimal('center_lng', 10, 7)->default(0);
            $t->unsignedBigInteger('revision')->default(0);
            $t->json('prerequisites')->nullable();
            $t->bigInteger('contingency_cents')->default(0);
            $t->bigInteger('target_profit_cents')->default(0);
            $t->bigInteger('own_cash_cents')->default(0);
            $t->boolean('profit_required')->default(false);
            $t->string('readiness_reference')->nullable();
            $t->timestamps();
        });
        Schema::create('plan_elements', function (Blueprint $t): void {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->foreignId('scenario_id')->constrained('event_scenarios')->cascadeOnDelete();
            $t->string('reference_key')->nullable();
            $t->string('name');
            $t->string('category');
            $t->string('subcategory');
            $t->string('shape');
            $t->json('geometry');
            $t->json('dimensions')->nullable();
            $t->string('color', 7)->default('#258052');
            $t->boolean('is_public')->default(false);
            $t->boolean('is_essential')->default(false);
            $t->json('four_ps')->nullable();
            $t->json('success')->nullable();
            $t->json('public_content')->nullable();
            $t->json('operations')->nullable();
            $t->unsignedInteger('tour_order')->nullable();
            $t->unsignedBigInteger('revision')->default(0);
            $t->timestamp('archived_at')->nullable();
            $t->timestamps();
            $t->unique(['scenario_id', 'reference_key']);
        });
        Schema::create('event_capacity_pools', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('scenario_id')->constrained('event_scenarios')->cascadeOnDelete();
            $t->string('name');
            $t->unsignedInteger('capacity');
            $t->unsignedInteger('reserved')->default(0);
            $t->timestamps();
        });
        Schema::create('event_offers', function (Blueprint $t): void {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->foreignId('scenario_id')->constrained('event_scenarios')->cascadeOnDelete();
            $t->foreignId('element_id')->nullable()->constrained('plan_elements')->nullOnDelete();
            $t->foreignId('pool_id')->nullable()->constrained('event_capacity_pools')->restrictOnDelete();
            $t->string('reference_key')->nullable();
            $t->string('name');
            $t->string('audience');
            $t->string('seller')->default('organizer');
            $t->bigInteger('price_cents');
            $t->bigInteger('net_price_cents')->nullable();
            $t->bigInteger('delivery_cost_cents')->nullable();
            $t->unsignedInteger('price_version')->default(1);
            $t->json('content')->nullable();
            $t->json('four_ps')->nullable();
            $t->boolean('is_public')->default(false);
            $t->boolean('active')->default(true);
            $t->timestamps();
            $t->unique(['scenario_id', 'reference_key']);
        });
        Schema::create('event_budget_lines', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('scenario_id')->constrained('event_scenarios')->cascadeOnDelete();
            $t->foreignId('element_id')->nullable()->constrained('plan_elements')->nullOnDelete();
            $t->string('reference_key')->nullable();
            $t->string('label');
            $t->string('kind');
            $t->string('cost_class')->default('operating');
            $t->string('status')->default('forecast');
            $t->bigInteger('gross_cents')->nullable();
            $t->bigInteger('net_cents')->nullable();
            $t->unsignedInteger('quantity_milli')->default(1000);
            $t->string('unit')->default('lot');
            $t->string('source_reference')->nullable();
            $t->timestamp('cash_due_at')->nullable();
            $t->boolean('essential')->default(true);
            $t->boolean('earmarked')->default(false);
            $t->json('metadata')->nullable();
            $t->timestamps();
            $t->unique(['scenario_id', 'reference_key']);
        });
        Schema::create('event_bookings', function (Blueprint $t): void {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->foreignId('event_id')->constrained('planner_events')->restrictOnDelete();
            $t->foreignId('offer_id')->constrained('event_offers')->restrictOnDelete();
            $t->string('idempotency_key')->unique();
            $t->text('buyer_name');
            $t->text('buyer_email');
            $t->string('buyer_subject')->nullable();
            $t->unsignedInteger('quantity');
            $t->unsignedInteger('price_version');
            $t->bigInteger('gross_cents');
            $t->bigInteger('net_cents')->nullable();
            $t->json('offer_snapshot');
            $t->timestamp('signed_at');
            $t->timestamp('deadline');
            $t->string('agreement_reference');
            $t->string('state')->default('signed');
            $t->string('payment_reference')->nullable()->unique();
            $t->timestamp('paid_at')->nullable();
            $t->timestamps();
        });
        Schema::create('event_refunds', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('booking_id')->unique()->constrained('event_bookings')->restrictOnDelete();
            $t->bigInteger('amount_cents');
            $t->string('state')->default('requested');
            $t->string('reference')->nullable()->unique();
            $t->timestamp('submitted_at')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
        });
        Schema::create('event_publications', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('event_id')->constrained('planner_events')->restrictOnDelete();
            $t->foreignId('scenario_id')->constrained('event_scenarios')->restrictOnDelete();
            $t->unsignedInteger('number');
            $t->json('snapshot');
            $t->string('checksum', 64);
            $t->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $t->timestamps();
            $t->unique(['event_id', 'number']);
        });
        Schema::create('event_four_p_reviews', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('event_id')->constrained('planner_events')->cascadeOnDelete();
            $t->foreignId('scenario_id')->nullable()->constrained('event_scenarios')->cascadeOnDelete();
            $t->foreignId('element_id')->nullable()->constrained('plan_elements')->nullOnDelete();
            $t->foreignId('offer_id')->nullable()->constrained('event_offers')->nullOnDelete();
            $t->string('level');
            $t->string('audience');
            $t->string('name');
            $t->json('four_ps');
            $t->text('hypothesis')->nullable();
            $t->string('indicator')->nullable();
            $t->string('unit')->nullable();
            $t->string('target')->nullable();
            $t->string('observed')->nullable();
            $t->string('evidence_kind')->default('hypothesis');
            $t->text('evidence_reference')->nullable();
            $t->timestamp('observed_at')->nullable();
            $t->string('owner')->nullable();
            $t->timestamp('due_at')->nullable();
            $t->text('next_action')->nullable();
            $t->string('decision')->default('improve');
            $t->text('decision_reason')->nullable();
            $t->timestamps();
        });
        Schema::create('event_campaigns', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('event_id')->constrained('planner_events')->cascadeOnDelete();
            $t->string('name');
            $t->string('audience');
            $t->string('channel');
            $t->string('phase');
            $t->text('message');
            $t->string('call_to_action');
            $t->bigInteger('budget_cents')->default(0);
            $t->string('owner')->nullable();
            $t->timestamp('due_at')->nullable();
            $t->json('results')->nullable();
            $t->string('delivery_reference')->nullable();
            $t->string('state')->default('draft');
            $t->timestamps();
        });
        Schema::create('event_leads', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('event_id')->constrained('planner_events')->cascadeOnDelete();
            $t->foreignId('offer_id')->nullable()->constrained('event_offers')->nullOnDelete();
            $t->foreignId('campaign_id')->nullable()->constrained('event_campaigns')->nullOnDelete();
            $t->string('subject_id')->nullable();
            $t->text('name');
            $t->text('email');
            $t->string('audience');
            $t->string('source')->nullable();
            $t->string('stage')->default('contact');
            $t->text('notes')->nullable();
            $t->string('owner')->nullable();
            $t->text('next_action')->nullable();
            $t->timestamp('due_at')->nullable();
            $t->timestamps();
        });
        Schema::create('event_products', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('scenario_id')->constrained('event_scenarios')->cascadeOnDelete();
            $t->foreignId('element_id')->nullable()->constrained('plan_elements')->nullOnDelete();
            $t->string('sku');
            $t->string('name');
            $t->string('seller')->default('organizer');
            $t->bigInteger('price_cents');
            $t->bigInteger('net_price_cents')->nullable();
            $t->bigInteger('unit_cost_cents')->nullable();
            $t->unsignedInteger('stock')->default(0);
            $t->boolean('is_public')->default(false);
            $t->json('content')->nullable();
            $t->timestamps();
            $t->unique(['scenario_id', 'sku']);
        });
        Schema::create('event_stock_movements', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('product_id')->constrained('event_products')->restrictOnDelete();
            $t->string('reference')->unique();
            $t->string('kind');
            $t->integer('quantity');
            $t->bigInteger('gross_cents')->default(0);
            $t->bigInteger('net_cents')->nullable();
            $t->foreignId('element_id')->nullable()->constrained('plan_elements')->nullOnDelete();
            $t->text('reason')->nullable();
            $t->timestamps();
        });
        Schema::create('event_audit_entries', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('event_id')->constrained('planner_events')->restrictOnDelete();
            $t->string('action');
            $t->string('reference');
            $t->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $t->json('details')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['event_audit_entries', 'event_stock_movements', 'event_products', 'event_leads', 'event_campaigns', 'event_four_p_reviews', 'event_publications', 'event_refunds', 'event_bookings', 'event_budget_lines', 'event_offers', 'event_capacity_pools', 'plan_elements', 'event_scenarios', 'planner_events'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
