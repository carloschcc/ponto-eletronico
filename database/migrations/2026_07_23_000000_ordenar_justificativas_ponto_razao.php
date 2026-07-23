<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OrdenarJustificativasPontoRazao extends Migration
{
    /**
     * Ordem de exibição definitiva das justificativas ativas.
     */
    private $ordem = [
        'Ajuste manual esquecimento de registrar' => 1,
        'Consulta médica pessoa da família'       => 2,
        'Declaração de comparecimento'             => 3,
        'Atestado médico'                          => 4,
        'Sistema inacessível'                      => 5,
    ];

    public function up()
    {
        if (!Schema::hasColumn('ponto_razao', 'ordem')) {
            Schema::table('ponto_razao', function (Blueprint $table) {
                $table->integer('ordem')->nullable()->after('ativo');
            });
        }

        // "Sistema fora do ar" sai da lista ativa.
        DB::table('ponto_razao')
            ->where('descricao', 'Sistema fora do ar')
            ->update(['ativo' => 0]);

        // "Ponto inacessível" passa a se chamar "Sistema inacessível".
        DB::table('ponto_razao')
            ->where('descricao', 'Ponto inacessível')
            ->update(['descricao' => 'Sistema inacessível', 'ativo' => 1]);

        foreach ($this->ordem as $descricao => $posicao) {
            DB::table('ponto_razao')
                ->where('descricao', $descricao)
                ->update(['ordem' => $posicao, 'ativo' => 1]);
        }
    }

    public function down()
    {
        DB::table('ponto_razao')
            ->where('descricao', 'Sistema inacessível')
            ->update(['descricao' => 'Ponto inacessível']);

        DB::table('ponto_razao')
            ->where('descricao', 'Sistema fora do ar')
            ->update(['ativo' => 1]);
    }
}
