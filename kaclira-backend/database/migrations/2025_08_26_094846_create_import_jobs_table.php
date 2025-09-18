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
        Schema::create('import_jobs', function (Blueprint $table) {
            $table->id();
            $table->uuid('file_id')->unique();
            $table->unsignedBigInteger('user_id');
            $table->string('filename');
            $table->string('file_path');
            $table->integer('total_products')->default(0);
            $table->integer('processed_products')->default(0);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->json('results')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_jobs');
    }
};
