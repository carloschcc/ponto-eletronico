<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InsertPontoRazaoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $razoes = 
        [
          [
            'descricao'          => 'Consulta médica',
            'ativo'          => 1,
          ],
          [
            'descricao'          => 'Consulta médica pessoa da familia',
            'ativo'          => 1,
          ],
          [
            'descricao'          => 'Sistema de ponto fora do ar',
            'ativo'          => 1,
          ], 
          [
            'descricao'          => 'Outra',
            'ativo'          => 1,
          ]  
        ];
        
        foreach ($razoes as $razao) {
            \App\PontoRazao::create($razao);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
