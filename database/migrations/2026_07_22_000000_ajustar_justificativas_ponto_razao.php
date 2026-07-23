<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AjustarJustificativasPontoRazao extends Migration
{
    /**
     * Lista definitiva de justificativas ativas. Registros antigos não
     * listados aqui são apenas desativados (ativo=0), nunca apagados —
     * solicitações de ajuste já existentes continuam referenciando o
     * ponto_razao_id original normalmente.
     */
    private $finais = [
        'Ajuste manual esquecimento de registrar',
        'Atestado médico',
        'Declaração de comparecimento',
        'Consulta médica pessoa da família',
        'Sistema fora do ar',
        'Ponto inacessível',
    ];

    public function up()
    {
        // Renomeia a variação antiga para o texto canônico, se existir.
        DB::table('ponto_razao')
            ->where('descricao', 'Sistema de ponto fora do ar')
            ->update(['descricao' => 'Sistema fora do ar']);

        // Desativa tudo que não está na lista final.
        DB::table('ponto_razao')
            ->whereNotIn('descricao', $this->finais)
            ->update(['ativo' => 0]);

        // Garante que as justificativas finais existam e estejam ativas.
        foreach ($this->finais as $descricao) {
            $existente = DB::table('ponto_razao')->where('descricao', $descricao)->first();

            if ($existente) {
                DB::table('ponto_razao')->where('id', $existente->id)->update(['ativo' => 1]);
            } else {
                DB::table('ponto_razao')->insert(['descricao' => $descricao, 'ativo' => 1]);
            }
        }
    }

    public function down()
    {
        DB::table('ponto_razao')
            ->where('descricao', 'Sistema fora do ar')
            ->update(['descricao' => 'Sistema de ponto fora do ar']);

        DB::table('ponto_razao')
            ->whereIn('descricao', ['Consulta médica', 'Outros'])
            ->update(['ativo' => 1]);
    }
}
