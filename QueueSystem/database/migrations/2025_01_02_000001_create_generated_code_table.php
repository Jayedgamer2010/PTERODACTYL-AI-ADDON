<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_code', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('conversation_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('language'); // bash, python, php, yaml, json, sql
            $table->longText('code');
            $table->longText('explanation')->nullable();
            $table->json('context')->nullable(); // Server info, user permissions, etc.
            $table->enum('safety_level', ['safe', 'caution', 'dangerous'])->default('safe');
            $table->json('safety_warnings')->nullable();
            $table->enum('status', ['draft', 'approved', 'rejected', 'executed'])->default('draft');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->integer('version')->default(1);
            $table->unsignedBigInteger('parent_id')->nullable(); // For versioning
            $table->boolean('is_template')->default(false);
            $table->integer('usage_count')->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('conversation_id')->references('id')->on('ai_conversations')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('parent_id')->references('id')->on('generated_code')->onDelete('cascade');
            
            $table->index(['user_id', 'language']);
            $table->index(['status', 'safety_level']);
            $table->index('is_template');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_code');
    }
};