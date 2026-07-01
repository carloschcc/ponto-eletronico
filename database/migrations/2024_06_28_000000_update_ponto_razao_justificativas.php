<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdatePontoRazaoJustificativas extends Migration
{
    public function up()
    {
        // Desativa as entradas antigas que não fazem mais parte da lista
        \App\PontoRazao::whereIn('descricao', [
            'Acompanhamento de parente em consulta médica',
            'Falha na máquina de registro de ponto',
            'Outra',
        ])->update(['ativo' => 0]);

        // Garante que "Consulta médica" esteja ativa
        \App\PontoRazao::where('descricao', 'Consulta médica')->update(['ativo' => 1]);

        // Insere as novas justificativas (apenas se não existirem)
        $novas = [
            'Consulta médica pessoa da família',
            'Ajuste manual esquecimento de registrar',
            'Atestado médico',
            'Sistema de ponto fora do ar',
            'Outros',
        ];

        foreach ($novas as $descricao) {
            if (!\App\PontoRazao::where('descricao', $descricao)->exists()) {
                \App\PontoRazao::create(['descricao' => $descricao, 'ativo' => 1]);
            }
        }
    }

    public function down()
    {
        // Reverte: reativa as antigas e desativa as novas
        \App\PontoRazao::whereIn('descricao', [
            'Acompanhamento de parente em consulta médica',
            'Falha na máquina de registro de ponto',
            'Outra',
        ])->update(['ativo' => 1]);

        \App\PontoRazao::whereIn('descricao', [
            'Consulta médica pessoa da família',
            'Ajuste manual esquecimento de registrar',
            'Atestado médico',
            'Sistema de ponto fora do ar',
            'Outros',
        ])->delete();
    }
}
