<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * O padrão do projeto é entidade_subtipo (ponto_razao, ponto_ajuste,
 * periodo_fechamento) — a tabela tinha saído como tipos_ferias, invertido.
 * Corrige pra ferias_tipos.
 */
class RenameTiposFeriasToFeriasTipos extends Migration
{
    public function up()
    {
        if (Schema::hasTable('tipos_ferias') && !Schema::hasTable('ferias_tipos')) {
            Schema::rename('tipos_ferias', 'ferias_tipos');
        }
    }

    public function down()
    {
        if (Schema::hasTable('ferias_tipos') && !Schema::hasTable('tipos_ferias')) {
            Schema::rename('ferias_tipos', 'tipos_ferias');
        }
    }
}
