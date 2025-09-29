<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queues', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->integer('position')->default(0);
            $table->enum('status', ['waiting', 'active', 'completed'])->default('waiting');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('position');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queues');
    }
};