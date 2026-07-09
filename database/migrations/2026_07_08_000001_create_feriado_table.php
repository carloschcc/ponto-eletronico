<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFeriadoTable extends Migration {

    public function up()
    {
        if (Schema::hasTable('feriado')) {
            return;
        }

        Schema::create('feriado', function (Blueprint $table) {
            $table->increments('id');
            $table->date('data')->unique();
            $table->string('descricao', 150)->nullable();
            $table->tinyInteger('recorrente')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('feriado');
    }
}
