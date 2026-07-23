<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Ferias extends Model
{
    protected $table = 'ferias';

    protected $casts = [
        'data_inicio' => 'date',
        'data_fim'    => 'date',
    ];

    public function usuario()
    {
        return $this->belongsTo('App\Usuario', 'usuario_id');
    }

    public function tipoFerias()
    {
        return $this->belongsTo('App\TipoFerias', 'tipo_ferias_id');
    }

    public function dias()
    {
        return $this->data_inicio->diffInDays($this->data_fim) + 1;
    }

    public function tipoLabel()
    {
        return optional($this->tipoFerias)->descricao ?? '—';
    }

    /**
     * Situação da linha do tempo das férias (agendada / em férias /
     * retornando), calculada a partir das datas — não é persistida, muda
     * sozinha conforme o calendário avança. Só faz sentido pra uma
     * solicitação já aprovada definitivamente (status 1); quem chama decide
     * se filtra por isso.
     *
     * Retorna ['chave', 'rotulo', 'cor', 'dias']:
     *   - agendada/em_ferias/retornando: situação normal
     *   - alerta_saida/alerta_retorno: dentro de 2 dias do início ou do fim
     *     (mesmo rótulo da situação normal correspondente, mas cor vermelha)
     *   - 'dias' é a contagem regressiva (ou, em retornando, dias desde o fim)
     */
    public function statusLinhaDoTempo($hoje = null)
    {
        $hoje   = $hoje ? Carbon::parse($hoje)->startOfDay() : Carbon::today();
        $inicio = $this->data_inicio->copy()->startOfDay();
        $fim    = $this->data_fim->copy()->startOfDay();

        if ($hoje->lt($inicio)) {
            $diasParaSair = $hoje->diffInDays($inicio);
            if ($diasParaSair <= 2) {
                return ['chave' => 'alerta_saida', 'rotulo' => 'Agendada', 'cor' => 'red', 'dias' => $diasParaSair];
            }
            return ['chave' => 'agendada', 'rotulo' => 'Agendada', 'cor' => 'blue', 'dias' => $diasParaSair];
        }

        if ($hoje->between($inicio, $fim)) {
            $diasParaRetornar = $hoje->diffInDays($fim);
            if ($diasParaRetornar <= 2) {
                return ['chave' => 'alerta_retorno', 'rotulo' => 'Em Férias', 'cor' => 'red', 'dias' => $diasParaRetornar];
            }
            return ['chave' => 'em_ferias', 'rotulo' => 'Em Férias', 'cor' => 'green', 'dias' => $diasParaRetornar];
        }

        $diasDesdeRetorno = $fim->diffInDays($hoje);
        return ['chave' => 'retornando', 'rotulo' => 'Retornando', 'cor' => 'yellow', 'dias' => $diasDesdeRetorno];
    }
}
