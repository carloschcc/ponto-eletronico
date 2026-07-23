<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Alguns registros de ponto_ajuste apontam para um ponto_razao_id que não
 * existe mais na tabela ponto_razao (provavelmente removido manualmente em
 * algum momento, fora do fluxo normal do app, que só desativa — nunca
 * apaga — uma justificativa). Isso quebra a tela de Ajustes com erro 500
 * ao tentar ler pontoRazao->descricao de uma relação nula.
 *
 * Aqui criamos uma justificativa "Motivo indisponível (histórico)",
 * inativa (não aparece pra seleção em novos ajustes), e repontamos os
 * registros órfãos pra ela — preserva o histórico sem inventar um motivo
 * que não sabemos qual era.
 */
class CorrigirPontoAjusteRazaoOrfao extends Migration
{
    public function up()
    {
        $idsValidos = DB::table('ponto_razao')->pluck('id')->toArray();

        $orfaos = DB::table('ponto_ajuste')
            ->whereNotNull('ponto_razao_id')
            ->whereNotIn('ponto_razao_id', $idsValidos)
            ->get();

        if ($orfaos->isEmpty()) {
            return;
        }

        $placeholder = DB::table('ponto_razao')->where('descricao', 'Motivo indisponível (histórico)')->first();

        if (!$placeholder) {
            $placeholderId = DB::table('ponto_razao')->insertGetId([
                'descricao' => 'Motivo indisponível (histórico)',
                'ativo'     => 0,
            ]);
        } else {
            $placeholderId = $placeholder->id;
        }

        DB::table('ponto_ajuste')
            ->whereIn('id', $orfaos->pluck('id'))
            ->update(['ponto_razao_id' => $placeholderId]);
    }

    public function down()
    {
        // Irreversível de forma segura — não temos como saber o
        // ponto_razao_id original de cada registro corrigido.
    }
}
