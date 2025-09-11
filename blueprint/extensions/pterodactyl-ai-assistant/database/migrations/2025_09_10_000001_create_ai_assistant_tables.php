<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAIAssistantTables extends Migration
{
    public function up()
    {
        // Chats table
        Schema::create('ai_chats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('server_id')->nullable();
            $table->string('title')->nullable();
            $table->string('status')->default('active');
            $table->json('context')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('server_id')->references('id')->on('servers')->onDelete('set null');
        });

        // Chat messages table
        Schema::create('ai_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chat_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('content');
            $table->string('type')->default('text');
            $table->json('metadata')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('chat_id')->references('id')->on('ai_chats')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        // Chat suggestions table
        Schema::create('ai_chat_suggestions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chat_id');
            $table->unsignedBigInteger('message_id')->nullable();
            $table->string('type');
            $table->text('content');
            $table->json('metadata')->nullable();
            $table->boolean('used')->default(false);
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->foreign('chat_id')->references('id')->on('ai_chats')->onDelete('cascade');
            $table->foreign('message_id')->references('id')->on('ai_chat_messages')->onDelete('set null');
        });

        // Chat actions table
        Schema::create('ai_chat_actions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chat_id');
            $table->unsignedBigInteger('message_id')->nullable();
            $table->string('action_type');
            $table->json('action_data');
            $table->string('status')->default('pending');
            $table->json('result')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            $table->foreign('chat_id')->references('id')->on('ai_chats')->onDelete('cascade');
            $table->foreign('message_id')->references('id')->on('ai_chat_messages')->onDelete('set null');
        });

        // AI settings table
        Schema::create('ai_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->string('type')->default('string');
            $table->boolean('encrypted')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // AI audit logs table
        Schema::create('ai_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action');
            $table->json('context');
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ai_audit_logs');
        Schema::dropIfExists('ai_settings');
        Schema::dropIfExists('ai_chat_actions');
        Schema::dropIfExists('ai_chat_suggestions');
        Schema::dropIfExists('ai_chat_messages');
        Schema::dropIfExists('ai_chats');
    }
}
