<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_permission_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('permission');
            $table->enum('action', ['granted', 'revoked', 'modified']);
            $table->unsignedBigInteger('changed_by');
            $table->text('reason')->nullable();
            $table->json('old_value')->nullable(); // For modifications
            $table->json('new_value')->nullable(); // For modifications
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('changed_by')->references('id')->on('users')->onDelete('cascade');
            
            $table->index(['user_id', 'created_at']);
            $table->index(['permission', 'action']);
            $table->index('changed_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_permission_logs');
    }
};