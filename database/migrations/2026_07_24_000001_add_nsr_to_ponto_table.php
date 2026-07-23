<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNsrToPontoTable extends Migration
{
    public function up()
    {
        Schema::table('ponto', function (Blueprint $table) {
            if (!Schema::hasColumn('ponto', 'entrada_nsr')) {
                $table->unsignedBigInteger('entrada_nsr')->nullable()->after('entrada_status');
            }
            if (!Schema::hasColumn('ponto', 'saida_nsr')) {
                $table->unsignedBigInteger('saida_nsr')->nullable()->after('saida_status');
            }
        });
    }

    public function down()
    {
        Schema::table('ponto', function (Blueprint $table) {
            $table->dropColumn(['entrada_nsr', 'saida_nsr']);
        });
    }
}
