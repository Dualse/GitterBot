<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMessagesTable extends Migration
{
    /**
     * Run the migrations.
     * @return void
     */
    public function up()
    {
        Schema::create('messages', function(Blueprint $t) {
            $t->increments('id');
            $t->string('gitter_id', 30)->index(); // Gitter message id
            $t->string('user_id', 30)->index(); // fromUser
            $t->string('room_id', 30)->index();
            $t->text('text');
            $t->text('html');
            $t->text('urls');
            $t->timestamps(); // sent && editedAt
        });

        Schema::create('mentions', function(Blueprint $t) {
            $t->increments('id');
            $t->string('user_id', 30)->index();
            $t->string('message_id', 30)->index();
        });
    }

    /**
     * Reverse the migrations.
     * @return void
     */
    public function down()
    {
        Schema::drop('messages');
        Schema::drop('messages_mentions');
    }
}
