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
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('language');
            $table->longText('code');
            $table->longText('explanation')->nullable();
            $table->json('context')->nullable();
            $table->enum('safety_level', ['safe', 'caution', 'dangerous'])->default('safe');
            $table->enum('status', ['draft', 'approved', 'rejected'])->default('draft');
            $table->integer('usage_count')->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'language']);
            $table->index(['status', 'created_at']);
            $table->index('safety_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_code');
    }
};