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
        Schema::create('sellers', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('contact_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('tax_number')->nullable()->unique();
            $table->enum('status', ['active', 'inactive', 'pending', 'suspended'])->default('pending');
            $table->unsignedBigInteger('parent_seller_id')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('parent_seller_id')->references('id')->on('sellers')->onDelete('set null');

            // Indexes
            $table->index(['status', 'created_at']);
            $table->index('parent_seller_id');
            $table->index('email');
            $table->index('tax_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sellers');
    }
};
