<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddObservacoesToPontoTable extends Migration {

    public function up()
    {
        Schema::table('ponto', function (Blueprint $table) {
            $table->text('observacoes')->nullable()->after('status');
        });
    }

    public function down()
    {
        Schema::table('ponto', function (Blueprint $table) {
            $table->dropColumn('observacoes');
        });
    }
}
