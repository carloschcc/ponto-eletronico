<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateTiposFeriasTable extends Migration
{
    private $tipos = [
        ['chave' => 'ferias',              'descricao' => 'Férias',              'ordem' => 1],
        ['chave' => 'recesso',             'descricao' => 'Recesso',             'ordem' => 2],
        ['chave' => 'licenca_maternidade', 'descricao' => 'Licença Maternidade', 'ordem' => 3],
        ['chave' => 'licenca_paternidade', 'descricao' => 'Licença Paternidade', 'ordem' => 4],
        ['chave' => 'licenca_casamento',   'descricao' => 'Licença Casamento',   'ordem' => 5],
        ['chave' => 'outras',              'descricao' => 'Outras',              'ordem' => 6],
    ];

    public function up()
    {
        if (!Schema::hasTable('tipos_ferias')) {
            Schema::create('tipos_ferias', function (Blueprint $table) {
                $table->increments('id');
                $table->string('chave', 40)->unique();
                $table->string('descricao', 100);
                $table->integer('ordem')->nullable();
                $table->smallInteger('ativo')->default(1);
                $table->timestamps();
            });
        }

        foreach ($this->tipos as $tipo) {
            if (!DB::table('tipos_ferias')->where('chave', $tipo['chave'])->exists()) {
                DB::table('tipos_ferias')->insert(array_merge($tipo, [
                    'ativo' => 1, 'created_at' => now(), 'updated_at' => now(),
                ]));
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('tipos_ferias');
    }
}
