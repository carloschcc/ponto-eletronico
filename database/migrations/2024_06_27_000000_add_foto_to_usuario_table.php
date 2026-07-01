<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFotoToUsuarioTable extends Migration
{
    public function up()
    {
        Schema::table('usuario', function (Blueprint $table) {
            $table->string('foto', 100)->nullable()->after('local');
        });
    }

    public function down()
    {
        Schema::table('usuario', function (Blueprint $table) {
            $table->dropColumn('foto');
        });
    }
}
