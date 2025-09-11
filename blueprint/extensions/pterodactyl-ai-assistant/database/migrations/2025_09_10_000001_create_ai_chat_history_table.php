<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAIChatHistoryTable extends Migration
{
    public function up()
    {
        Schema::create('ai_chat_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('server_id')->nullable();
            $table->text('message');
            $table->text('response');
            $table->string('provider');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('server_id')->references('id')->on('servers')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ai_chat_history');
    }
}
