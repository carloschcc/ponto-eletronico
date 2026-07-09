<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePeriodoFechamentoTable extends Migration {

    /**
     * A tabela e a coluna 'ativo' podem já existir em ambientes onde o
     * PeriodoController a criava em runtime (padrão descontinuado) — os
     * guards abaixo tornam esta migration segura de rodar nesses casos.
     */
    public function up()
    {
        if (!Schema::hasTable('periodo_fechamento')) {
            Schema::create('periodo_fechamento', function (Blueprint $table) {
                $table->increments('id');
                $table->string('descricao', 100)->nullable();
                $table->date('data_inicio');
                $table->date('data_fim');
                $table->tinyInteger('ativo')->default(0);
                $table->timestamps();
            });

            return;
        }

        if (!Schema::hasColumn('periodo_fechamento', 'ativo')) {
            Schema::table('periodo_fechamento', function (Blueprint $table) {
                $table->tinyInteger('ativo')->default(0)->after('data_fim');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('periodo_fechamento');
    }
}
