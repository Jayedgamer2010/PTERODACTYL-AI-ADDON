<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('code_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->string('category'); // server-optimization, plugin-config, backup, monitoring, etc.
            $table->string('language');
            $table->longText('template_code');
            $table->json('variables')->nullable(); // Template variables and their descriptions
            $table->json('requirements')->nullable(); // Required permissions, server specs, etc.
            $table->enum('difficulty', ['beginner', 'intermediate', 'advanced'])->default('beginner');
            $table->json('tags')->nullable(); // For searching and categorization
            $table->boolean('is_public')->default(false);
            $table->boolean('is_official')->default(false);
            $table->unsignedBigInteger('created_by');
            $table->integer('usage_count')->default(0);
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('rating_count')->default(0);
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            
            $table->index(['category', 'language']);
            $table->index(['is_public', 'is_official']);
            $table->index('usage_count');
            $table->fullText(['name', 'description']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('code_templates');
    }
};