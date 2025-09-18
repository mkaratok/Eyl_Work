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
        Schema::table('products', function (Blueprint $table) {
            // Check if stock_quantity column already exists
            if (!Schema::hasColumn('products', 'stock_quantity')) {
                $table->integer('stock_quantity')->default(0);
                
                // Add index for better query performance with explicit name
                $table->index('stock_quantity', 'products_stock_quantity_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Check if stock_quantity column exists before dropping
            if (Schema::hasColumn('products', 'stock_quantity')) {
                // Drop index first if it exists
                $indexName = 'products_stock_quantity_index';
                if (Schema::hasIndex('products', $indexName)) {
                    $table->dropIndex([$indexName]);
                }
                $table->dropColumn('stock_quantity');
            }
        });
    }
};