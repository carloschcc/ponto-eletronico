<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGerenteToUsuarioTable extends Migration {

    public function up()
    {
        if (Schema::hasColumn('usuario', 'gerente')) {
            return;
        }

        Schema::table('usuario', function (Blueprint $table) {
            $table->smallInteger('gerente')->default(0)->after('admin');
        });
    }

    public function down()
    {
        Schema::table('usuario', function (Blueprint $table) {
            $table->dropColumn('gerente');
        });
    }
}
