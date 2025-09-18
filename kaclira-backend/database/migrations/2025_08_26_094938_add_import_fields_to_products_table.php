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
            // Add SKU field first
            if (!Schema::hasColumn('products', 'sku')) {
                $table->string('sku')->unique()->nullable();
            }
            
            // Add import-related fields
            if (!Schema::hasColumn('products', 'gtin')) {
                $table->string('gtin')->nullable();
            }
            
            if (!Schema::hasColumn('products', 'mpn')) {
                $table->string('mpn')->nullable();
            }
            
            if (!Schema::hasColumn('products', 'price')) {
                $table->decimal('price', 10, 2)->nullable();
            }
            
            if (!Schema::hasColumn('products', 'sale_price')) {
                $table->decimal('sale_price', 10, 2)->nullable();
            }
            
            if (!Schema::hasColumn('products', 'availability')) {
                $table->string('availability')->default('in_stock');
            }
            
            if (!Schema::hasColumn('products', 'condition')) {
                $table->string('condition')->default('new');
            }
            
            if (!Schema::hasColumn('products', 'image_url')) {
                $table->string('image_url')->nullable();
            }
            
            if (!Schema::hasColumn('products', 'additional_images')) {
                $table->json('additional_images')->nullable();
            }
            
            if (!Schema::hasColumn('products', 'external_url')) {
                $table->string('external_url')->nullable();
            }
            
            if (!Schema::hasColumn('products', 'weight')) {
                $table->decimal('weight', 8, 2)->nullable();
            }
            
            if (!Schema::hasColumn('products', 'google_category')) {
                $table->string('google_category')->nullable();
            }
            
            if (!Schema::hasColumn('products', 'import_source')) {
                $table->string('import_source')->nullable();
            }
            
            if (!Schema::hasColumn('products', 'imported_at')) {
                $table->timestamp('imported_at')->nullable();
            }
            
            if (!Schema::hasColumn('products', 'seller_id')) {
                $table->unsignedBigInteger('seller_id')->nullable();
            }
            
            if (!Schema::hasColumn('products', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
            
            if (!Schema::hasColumn('products', 'slug')) {
                $table->string('slug')->nullable();
            }
            
            // Add foreign key for seller if not exists
            if (Schema::hasColumn('products', 'seller_id')) {
                $indexName = 'products_seller_id_foreign';
                if (!Schema::hasIndex('products', $indexName)) {
                    $table->foreign('seller_id')->references('id')->on('users')->onDelete('cascade');
                }
            }
            
            // Add indexes with unique names to avoid conflicts
            $indexes = [
                ['column' => 'sku', 'name' => 'idx_products_sku_2'],
                ['column' => 'gtin', 'name' => 'idx_products_gtin'],
                ['column' => 'seller_id', 'name' => 'idx_products_seller_id'],
                ['column' => 'is_active', 'name' => 'idx_products_is_active'],
                ['column' => 'availability', 'name' => 'idx_products_availability']
            ];
            
            foreach ($indexes as $index) {
                if (Schema::hasColumn('products', $index['column'])) {
                    if (!Schema::hasIndex('products', $index['name'])) {
                        $table->index($index['column'], $index['name']);
                    }
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop indexes first
            $indexes = [
                'idx_products_sku_2',
                'idx_products_gtin',
                'idx_products_seller_id',
                'idx_products_is_active',
                'idx_products_availability'
            ];
            
            foreach ($indexes as $indexName) {
                if (Schema::hasIndex('products', $indexName)) {
                    $table->dropIndex([$indexName]);
                }
            }
            
            // Drop foreign keys first
            if (Schema::hasIndex('products', 'products_seller_id_foreign')) {
                $table->dropForeign(['seller_id']);
            }
            
            // Drop all added columns if they exist
            $columnsToDrop = [
                'sku',
                'gtin',
                'mpn',
                'price',
                'sale_price',
                'availability',
                'condition',
                'image_url',
                'additional_images',
                'external_url',
                'weight',
                'google_category',
                'import_source',
                'imported_at',
                'seller_id',
                'is_active',
                'slug'
            ];
            
            // Filter to only drop columns that actually exist
            $existingColumns = Schema::getColumnListing('products');
            $columnsToActuallyDrop = array_intersect($columnsToDrop, $existingColumns);
            
            if (!empty($columnsToActuallyDrop)) {
                $table->dropColumn($columnsToActuallyDrop);
            }
        });
    }
};