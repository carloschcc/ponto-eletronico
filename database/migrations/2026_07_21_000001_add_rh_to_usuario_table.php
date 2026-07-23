<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRhToUsuarioTable extends Migration {

    public function up()
    {
        if (Schema::hasColumn('usuario', 'rh')) {
            return;
        }

        Schema::table('usuario', function (Blueprint $table) {
            $table->smallInteger('rh')->default(0)->after('gerente');
        });
    }

    public function down()
    {
        Schema::table('usuario', function (Blueprint $table) {
            $table->dropColumn('rh');
        });
    }
}
