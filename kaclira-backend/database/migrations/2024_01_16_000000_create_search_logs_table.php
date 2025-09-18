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
        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();
            $table->string('query', 255)->index();
            $table->integer('result_count')->default(0);
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('filters')->nullable(); // Store applied filters
            $table->string('sort_by', 50)->nullable();
            $table->string('sort_order', 10)->nullable();
            $table->timestamp('created_at');
            
            // Indexes for analytics
            $table->index(['query', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['created_at']);
            $table->index('result_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_logs');
    }
};
