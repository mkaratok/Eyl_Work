<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('price_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_price_id');
            $table->decimal('old_price', 10, 2);
            $table->decimal('new_price', 10, 2);
            $table->integer('old_stock');
            $table->integer('new_stock');
            $table->enum('change_type', ['price_increase', 'price_decrease', 'stock_change', 'both']);
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('product_price_id')->references('id')->on('product_prices')->onDelete('cascade');

            // Indexes
            $table->index(['product_price_id', 'created_at']);
            $table->index(['change_type', 'created_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_history');
    }
};
