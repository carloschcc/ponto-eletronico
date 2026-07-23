<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTipoAnoReferenciaToFeriasTable extends Migration
{
    public function up()
    {
        Schema::table('ferias', function (Blueprint $table) {
            if (!Schema::hasColumn('ferias', 'tipo')) {
                $table->string('tipo', 30)->default('ferias')->after('usuario_id');
            }
            if (!Schema::hasColumn('ferias', 'ano_referencia')) {
                $table->integer('ano_referencia')->nullable()->after('tipo');
            }
        });
    }

    public function down()
    {
        Schema::table('ferias', function (Blueprint $table) {
            $table->dropColumn(['tipo', 'ano_referencia']);
        });
    }
}
