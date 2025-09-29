<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_user_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('permission'); // ai_chat, code_generation, etc.
            $table->boolean('granted')->default(true);
            $table->unsignedBigInteger('granted_by')->nullable();
            $table->timestamp('granted_at')->nullable();
            $table->unsignedBigInteger('revoked_by')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->text('reason')->nullable(); // Reason for granting/revoking
            $table->timestamp('expires_at')->nullable(); // Optional expiration
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('granted_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('revoked_by')->references('id')->on('users')->onDelete('set null');
            
            $table->unique(['user_id', 'permission']);
            $table->index(['user_id', 'granted']);
            $table->index('permission');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_user_permissions');
    }
};