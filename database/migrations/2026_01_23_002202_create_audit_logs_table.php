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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event'); // login_attempt, login_success, login_failed, logout, token_created, token_refreshed, token_revoked
            $table->string('ip_address', 45);
            $table->string('user_agent')->nullable();
            $table->string('email')->nullable(); // For failed login attempts (user might not exist)
            $table->json('metadata')->nullable(); // Additional data like device info, location
            $table->string('status'); // success, failed, blocked
            $table->text('failure_reason')->nullable();
            $table->timestamps();
            
            // Indexes for faster queries
            $table->index('event');
            $table->index('ip_address');
            $table->index('created_at');
            $table->index(['user_id', 'event']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
