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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('barcode')->unique()->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('category_id');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->json('specifications')->nullable();
            $table->json('images')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->boolean('admin_approved')->default(false);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

            // Indexes
            $table->index(['status', 'admin_approved']);
            $table->index(['category_id', 'status']);
            $table->index(['brand', 'status']);
            $table->index('created_by');
            $table->index('barcode');
            $table->fullText(['name', 'description', 'brand', 'model']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
