<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAIAssistantTables extends Migration
{
    public function up()
    {
        // Chat History Table
        Schema::create('ai_chat_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('server_id')->nullable();
            $table->text('message');
            $table->text('response');
            $table->json('context')->nullable();
            $table->string('provider');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('server_id')->references('id')->on('servers')->onDelete('cascade');
        });

        // AI Metrics Table
        Schema::create('ai_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('metric_key');
            $table->string('metric_value');
            $table->unsignedBigInteger('server_id')->nullable();
            $table->timestamp('recorded_at');

            $table->foreign('server_id')->references('id')->on('servers')->onDelete('cascade');
            $table->index(['metric_key', 'server_id', 'recorded_at']);
        });

        // AI Settings Table
        Schema::create('ai_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ai_chat_history');
        Schema::dropIfExists('ai_metrics');
        Schema::dropIfExists('ai_settings');
    }
}
