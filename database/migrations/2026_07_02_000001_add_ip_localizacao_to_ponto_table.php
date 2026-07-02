<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIpLocalizacaoToPontoTable extends Migration {

    public function up()
    {
        Schema::table('ponto', function (Blueprint $table) {
            $table->string('entrada_ip', 45)->nullable()->after('entrada_status');
            $table->string('entrada_latitude', 50)->nullable()->after('entrada_ip');
            $table->string('entrada_longitude', 50)->nullable()->after('entrada_latitude');
            $table->string('saida_ip', 45)->nullable()->after('saida_status');
            $table->string('saida_latitude', 50)->nullable()->after('saida_ip');
            $table->string('saida_longitude', 50)->nullable()->after('saida_latitude');
        });
    }

    public function down()
    {
        Schema::table('ponto', function (Blueprint $table) {
            $table->dropColumn([
                'entrada_ip',
                'entrada_latitude',
                'entrada_longitude',
                'saida_ip',
                'saida_latitude',
                'saida_longitude',
            ]);
        });
    }
}
