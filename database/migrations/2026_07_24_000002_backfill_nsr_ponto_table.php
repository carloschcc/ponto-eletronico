<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Atribui NSR aos registros de ponto já existentes (gerados antes desta
 * coluna existir), em ordem cronológica real de marcação (data + hora),
 * usando created_at como critério de desempate quando data+hora coincidem.
 * Sem isso o arquivo de exportação ficaria com marcações antigas sem NSR.
 */
class BackfillNsrPontoTable extends Migration
{
    public function up()
    {
        $pontos = DB::table('ponto')
            ->select('id', 'data', 'entrada', 'entrada_nsr', 'saida', 'saida_nsr', 'created_at')
            ->get();

        $eventos = [];

        foreach ($pontos as $p) {
            if (!empty($p->entrada) && empty($p->entrada_nsr)) {
                $eventos[] = [
                    'ponto_id'  => $p->id,
                    'campo'     => 'entrada',
                    'momento'   => $p->data . ' ' . $p->entrada,
                    'criado_em' => (string) $p->created_at,
                ];
            }
            if (!empty($p->saida) && empty($p->saida_nsr)) {
                $eventos[] = [
                    'ponto_id'  => $p->id,
                    'campo'     => 'saida',
                    'momento'   => $p->data . ' ' . $p->saida,
                    'criado_em' => (string) $p->created_at,
                ];
            }
        }

        usort($eventos, function ($a, $b) {
            $cmp = strcmp($a['momento'], $b['momento']);
            if ($cmp !== 0) {
                return $cmp;
            }
            return strcmp($a['criado_em'], $b['criado_em']);
        });

        foreach ($eventos as $evento) {
            $nsr = DB::table('nsr_sequencia')->insertGetId([
                'origem'    => 'backfill_' . $evento['campo'],
                'criado_em' => now(),
            ]);

            DB::table('ponto')->where('id', $evento['ponto_id'])
                ->update([$evento['campo'] . '_nsr' => $nsr]);
        }
    }

    public function down()
    {
        DB::table('ponto')->update(['entrada_nsr' => null, 'saida_nsr' => null]);
        DB::table('nsr_sequencia')->truncate();
    }
}
