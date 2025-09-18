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
            // Add columns without specific ordering to avoid "after" column issues
            if (!Schema::hasColumn('products', 'short_description')) {
                $table->text('short_description')->nullable();
            }
            
            if (!Schema::hasColumn('products', 'specifications')) {
                $table->json('specifications')->nullable();
            }
            
            if (!Schema::hasColumn('products', 'thumbnail')) {
                $table->string('thumbnail')->nullable();
            }
            
            if (!Schema::hasColumn('products', 'dimensions')) {
                $table->json('dimensions')->nullable();
            }
            
            // Add tags before meta_title to avoid the "Unknown column 'tags'" error
            if (!Schema::hasColumn('products', 'tags')) {
                $table->json('tags')->nullable();
            }
            
            if (!Schema::hasColumn('products', 'meta_title')) {
                $table->string('meta_title')->nullable();
            }
            
            if (!Schema::hasColumn('products', 'meta_description')) {
                $table->text('meta_description')->nullable();
            }
            
            if (!Schema::hasColumn('products', 'is_featured')) {
                $table->boolean('is_featured')->default(false);
            }
            
            if (!Schema::hasColumn('products', 'sort_order')) {
                $table->integer('sort_order')->default(0);
            }
            
            if (!Schema::hasColumn('products', 'approval_notes')) {
                $table->text('approval_notes')->nullable();
            }
            
            if (!Schema::hasColumn('products', 'sku')) {
                $table->string('sku')->nullable();
                // Add unique constraint separately to avoid index naming conflicts
                $table->unique('sku', 'products_sku_unique');
            }
            
            // Add indexes for better query performance (with unique names to avoid conflicts)
            if (Schema::hasColumn('products', 'slug')) {
                $indexName = 'idx_products_slug';
                if (!Schema::hasIndex('products', $indexName)) {
                    $table->index('slug', $indexName);
                }
            }
            
            if (Schema::hasColumn('products', 'sku')) {
                $indexName = 'idx_products_sku';
                if (!Schema::hasIndex('products', $indexName)) {
                    $table->index('sku', $indexName);
                }
            }
            
            if (Schema::hasColumn('products', 'sort_order')) {
                $indexName = 'idx_products_sort_order';
                if (!Schema::hasIndex('products', $indexName)) {
                    $table->index('sort_order', $indexName);
                }
            }
            
            if (Schema::hasColumn('products', 'is_featured')) {
                $indexName = 'idx_products_is_featured';
                if (!Schema::hasIndex('products', $indexName)) {
                    $table->index('is_featured', $indexName);
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
            // Drop indexes first if they exist
            $indexes = [
                'idx_products_slug',
                'idx_products_sku',
                'idx_products_sort_order',
                'idx_products_is_featured'
            ];
            
            foreach ($indexes as $indexName) {
                if (Schema::hasIndex('products', $indexName)) {
                    $table->dropIndex([$indexName]);
                }
            }
            
            // Drop unique constraint on sku if it exists
            if (Schema::hasColumn('products', 'sku')) {
                $indexName = 'products_sku_unique';
                if (Schema::hasIndex('products', $indexName)) {
                    $table->dropUnique([$indexName]);
                }
            }
            
            // Drop columns if they exist
            $columnsToDrop = [
                'short_description',
                'specifications',
                'thumbnail',
                'dimensions',
                'tags',
                'meta_title',
                'meta_description',
                'is_featured',
                'sort_order',
                'approval_notes',
                'sku'
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