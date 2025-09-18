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
        Schema::create('user_favorites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('product_id');
            $table->boolean('price_alert_enabled')->default(false);
            $table->decimal('target_price', 10, 2)->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

            // Unique constraint to prevent duplicate favorites
            $table->unique(['user_id', 'product_id']);

            // Indexes
            $table->index(['user_id', 'created_at']);
            $table->index(['product_id', 'created_at']);
            $table->index(['price_alert_enabled', 'target_price']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_favorites');
    }
};
