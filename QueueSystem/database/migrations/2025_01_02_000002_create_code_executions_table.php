<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('code_executions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('code_id');
            $table->unsignedBigInteger('server_id')->nullable();
            $table->enum('execution_type', ['sandbox', 'server', 'scheduled'])->default('sandbox');
            $table->enum('status', ['pending', 'running', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->longText('output')->nullable();
            $table->longText('error_output')->nullable();
            $table->integer('exit_code')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('execution_time_ms')->nullable();
            $table->json('environment_info')->nullable(); // Docker container, server specs, etc.
            $table->string('execution_id')->unique()->nullable(); // For tracking long-running processes
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('code_id')->references('id')->on('generated_code')->onDelete('cascade');
            $table->foreign('server_id')->references('id')->on('servers')->onDelete('set null');
            
            $table->index(['user_id', 'status']);
            $table->index(['server_id', 'execution_type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('code_executions');
    }
};