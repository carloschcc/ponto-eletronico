<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gerador de NSR (Número Sequencial de Registro) exigido pela legislação de
 * ponto eletrônico: um contador global, monotônico e nunca reutilizado, uma
 * linha por marcação gerada (entrada ou saída). Uma tabela auto_increment
 * dedicada garante unicidade sob concorrência sem lock manual — o valor de
 * id nunca decresce nem se repete, mesmo que linhas antigas sejam removidas.
 */
class CreateNsrSequenciaTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('nsr_sequencia')) {
            return;
        }

        Schema::create('nsr_sequencia', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('origem', 30)->nullable();
            $table->timestamp('criado_em')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('nsr_sequencia');
    }
}
