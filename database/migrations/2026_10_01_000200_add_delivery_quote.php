<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_offers', fn (Blueprint $t) => $t->bigInteger('delivery_gross_cents')->nullable());
    }

    public function down(): void
    {
        Schema::table('event_offers', fn (Blueprint $t) => $t->dropColumn('delivery_gross_cents'));
    }
};
