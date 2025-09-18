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
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('seller_id');
            $table->decimal('price', 10, 2);
            $table->integer('stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('seller_id')->references('id')->on('sellers')->onDelete('cascade');

            // Unique constraint to prevent duplicate product-seller combinations
            $table->unique(['product_id', 'seller_id']);

            // Indexes
            $table->index(['product_id', 'is_active']);
            $table->index(['seller_id', 'is_active']);
            $table->index(['price', 'is_active']);
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
