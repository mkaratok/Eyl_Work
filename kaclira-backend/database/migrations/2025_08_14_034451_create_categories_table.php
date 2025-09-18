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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('merchant_id')->nullable(); // Google Merchant category ID
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->tinyInteger('level')->default(0);
            $table->string('path')->nullable(); // Full category path for easy querying
            $table->string('icon')->nullable();
            $table->string('image')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->string('google_category_id')->nullable();
            $table->string('google_category_path')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('parent_id')->references('id')->on('categories')->onDelete('cascade');

            // Indexes for performance
            $table->index(['parent_id', 'is_active']);
            $table->index(['merchant_id']);
            $table->index(['level']);
            $table->index(['path']);
            $table->index(['slug']);
            $table->index(['is_active', 'is_featured']);
            $table->index(['sort_order']);
            $table->index(['google_category_id']);
            
            // Full-text search index
            $table->fullText(['name', 'description']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
