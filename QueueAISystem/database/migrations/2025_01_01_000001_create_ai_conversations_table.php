<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('session_id')->index();
            $table->enum('role', ['user', 'assistant', 'system'])->default('user');
            $table->longText('message');
            $table->json('metadata')->nullable();
            $table->string('ai_provider')->nullable();
            $table->string('ai_model')->nullable();
            $table->integer('tokens_used')->default(0);
            $table->decimal('cost', 10, 6)->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'session_id']);
            $table->index('created_at'); // For performance on recent activity queries
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_conversations');
    }
};