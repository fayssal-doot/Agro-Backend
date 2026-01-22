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
        Schema::create('security_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type'); // login_success, unauthorized_access, etc.
            $table->enum('severity', ['low', 'medium', 'high', 'critical']); // low, medium, high, critical
            $table->string('user_email')->nullable()->index();
            $table->ipAddress('client_ip')->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->string('request_path')->nullable();
            $table->string('request_method')->nullable();
            $table->json('metadata')->nullable(); // Additional context data
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['created_at', 'severity']);
            $table->index(['created_at', 'event_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_events');
    }
};
