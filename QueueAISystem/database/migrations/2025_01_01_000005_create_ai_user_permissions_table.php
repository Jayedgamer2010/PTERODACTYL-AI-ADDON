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
            $table->string('permission');
            $table->boolean('granted')->default(true);
            $table->unsignedBigInteger('granted_by')->nullable();
            $table->timestamp('granted_at')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('granted_by')->references('id')->on('users')->onDelete('set null');
            
            $table->unique(['user_id', 'permission']);
            $table->index('permission');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_user_permissions');
    }
};