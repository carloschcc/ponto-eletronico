<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddGeoFonteToPontoTable extends Migration {

    public function up()
    {
        if (!Schema::hasColumn('ponto', 'entrada_geo_fonte')) {
            Schema::table('ponto', function (Blueprint $table) {
                $table->string('entrada_geo_fonte', 10)->nullable()->after('entrada_longitude');
            });
        }

        if (!Schema::hasColumn('ponto', 'saida_geo_fonte')) {
            Schema::table('ponto', function (Blueprint $table) {
                $table->string('saida_geo_fonte', 10)->nullable()->after('saida_longitude');
            });
        }

        // Todos os registros anteriores à captura por GPS foram obtidos por IP.
        DB::table('ponto')->whereNotNull('entrada_latitude')->whereNull('entrada_geo_fonte')->update(['entrada_geo_fonte' => 'ip']);
        DB::table('ponto')->whereNotNull('saida_latitude')->whereNull('saida_geo_fonte')->update(['saida_geo_fonte' => 'ip']);
    }

    public function down()
    {
        Schema::table('ponto', function (Blueprint $table) {
            $table->dropColumn(['entrada_geo_fonte', 'saida_geo_fonte']);
        });
    }
}
