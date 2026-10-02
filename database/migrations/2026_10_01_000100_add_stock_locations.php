<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_stock_locations', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('product_id')->constrained('event_products')->restrictOnDelete();
            $t->string('location_key');
            $t->foreignId('element_id')->nullable()->constrained('plan_elements')->restrictOnDelete();
            $t->unsignedInteger('quantity')->default(0);
            $t->timestamps();
            $t->unique(['product_id', 'location_key']);
        });
        Schema::table('event_stock_movements', function (Blueprint $t): void {
            $t->foreignId('source_element_id')->nullable()->constrained('plan_elements')->restrictOnDelete();
        });
        foreach (DB::table('event_products')->where('stock', '>', 0)->get() as $product) {
            DB::table('event_stock_locations')->insert(['product_id' => $product->id, 'location_key' => 'depot', 'quantity' => $product->stock, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::table('event_stock_movements', fn (Blueprint $t) => $t->dropConstrainedForeignId('source_element_id'));
        Schema::dropIfExists('event_stock_locations');
    }
};
