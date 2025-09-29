<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_configs', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('model_name');
            $table->enum('model_type', ['chat', 'code', 'fast'])->default('chat');
            $table->text('api_key_encrypted');
            $table->string('api_endpoint')->nullable();
            $table->json('model_config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('rate_limit_per_minute')->default(60);
            $table->integer('rate_limit_per_hour')->default(1000);
            $table->integer('max_tokens')->default(4000);
            $table->decimal('cost_per_1k_tokens', 8, 6)->default(0);
            $table->timestamps();

            $table->index(['provider', 'model_type']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_configs');
    }
};