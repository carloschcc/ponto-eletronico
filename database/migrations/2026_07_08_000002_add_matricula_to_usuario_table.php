<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMatriculaToUsuarioTable extends Migration {

    public function up()
    {
        if (Schema::hasColumn('usuario', 'matricula')) {
            return;
        }

        Schema::table('usuario', function (Blueprint $table) {
            $table->string('matricula', 50)->nullable()->after('cpf');
        });
    }

    public function down()
    {
        Schema::table('usuario', function (Blueprint $table) {
            $table->dropColumn('matricula');
        });
    }
}
