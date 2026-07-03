<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillIpLocalizacaoPontoTable extends Migration {

    public function up()
    {
        if (!Schema::hasColumn('ponto', 'observacoes') || !Schema::hasColumn('ponto', 'entrada_ip')) {
            return;
        }

        $registros = DB::table('ponto')
            ->whereNotNull('observacoes')
            ->where(function ($q) {
                $q->whereNull('entrada_ip')->orWhereNull('saida_ip');
            })
            ->get(['id', 'observacoes', 'entrada_ip', 'saida_ip']);

        foreach ($registros as $registro) {
            $obs = $registro->observacoes;
            $update = [];

            // Separa o texto em segmento de entrada e segmento de saída, pois
            // um registro combinado tem o formato "Entrada - ... | Saída - ...".
            $entradaSeg = null;
            $saidaSeg = null;

            if (preg_match('/^(.*?)\s*\|\s*(Sa[ií]da\s*-.*)$/us', $obs, $mm)) {
                $entradaSeg = $mm[1];
                $saidaSeg = $mm[2];
            } elseif (preg_match('/^Sa[ií]da\s*-/u', trim($obs))) {
                $saidaSeg = $obs;
            } else {
                $entradaSeg = $obs;
            }

            if (empty($registro->entrada_ip) && $entradaSeg && preg_match('/IP:\s*([^\|]+)/u', $entradaSeg, $m)) {
                $ip = trim($m[1]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    $update['entrada_ip'] = $ip;
                }
                if (preg_match('/Localiza[çc][ãa]o IP:\s*([\-0-9\.]+)\s*,\s*([\-0-9\.]+)/u', $entradaSeg, $ml)) {
                    $update['entrada_latitude'] = trim($ml[1]);
                    $update['entrada_longitude'] = trim($ml[2]);
                }
            }

            if (empty($registro->saida_ip) && $saidaSeg && preg_match('/IP:\s*([^\|]+)/u', $saidaSeg, $m)) {
                $ip = trim($m[1]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    $update['saida_ip'] = $ip;
                }
                if (preg_match('/Localiza[çc][ãa]o IP:\s*([\-0-9\.]+)\s*,\s*([\-0-9\.]+)/u', $saidaSeg, $ml)) {
                    $update['saida_latitude'] = trim($ml[1]);
                    $update['saida_longitude'] = trim($ml[2]);
                }
            }

            if (!empty($update)) {
                DB::table('ponto')->where('id', $registro->id)->update($update);
            }
        }
    }

    public function down()
    {
        // Backfill de dados — não há rollback estrutural.
    }
}
