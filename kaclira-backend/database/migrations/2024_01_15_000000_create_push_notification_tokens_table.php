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
        Schema::create('push_notification_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('token', 500);
            $table->enum('platform', ['ios', 'android', 'web']);
            $table->string('device_id')->nullable();
            $table->string('endpoint', 500)->nullable(); // For web push
            $table->string('p256dh')->nullable(); // For web push
            $table->string('auth')->nullable(); // For web push
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'platform']);
            $table->index(['user_id', 'is_active']);
            $table->unique(['user_id', 'token', 'platform']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_notification_tokens');
    }
};
