<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFeriasTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('ferias')) {
            return;
        }

        Schema::create('ferias', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('usuario_id');
            $table->date('data_inicio');
            $table->date('data_fim');
            // 0 pendente, 1 aprovado (definitivo), 2 rejeitado, 3 pre-aprovado (gerente)
            $table->smallInteger('status')->default(0);
            $table->text('observacao')->nullable();
            $table->text('obs_supervisor')->nullable();
            $table->integer('aprovado_por')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ferias');
    }
}
