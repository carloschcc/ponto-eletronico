<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Move o "tipo" de férias (string livre) para uma referência de verdade na
 * tabela tipos_ferias. A coluna antiga `tipo` fica pra trás como legado —
 * não é mais lida nem gravada pelo código, só preservada pra não perder
 * histórico de registros já existentes.
 */
class AddTipoFeriasIdToFeriasTable extends Migration
{
    public function up()
    {
        Schema::table('ferias', function (Blueprint $table) {
            if (!Schema::hasColumn('ferias', 'tipo_ferias_id')) {
                $table->integer('tipo_ferias_id')->nullable()->after('tipo');
            }
        });

        $mapa = DB::table('tipos_ferias')->pluck('id', 'chave');
        $padrao = $mapa['outras'] ?? null;

        foreach (DB::table('ferias')->whereNull('tipo_ferias_id')->get() as $f) {
            $id = $mapa[$f->tipo] ?? $padrao;
            if ($id) {
                DB::table('ferias')->where('id', $f->id)->update(['tipo_ferias_id' => $id]);
            }
        }
    }

    public function down()
    {
        Schema::table('ferias', function (Blueprint $table) {
            $table->dropColumn('tipo_ferias_id');
        });
    }
}
