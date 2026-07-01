<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PeriodoFechamento extends Model
{
    protected $table = 'periodo_fechamento';
    protected $fillable = ['descricao', 'data_inicio', 'data_fim', 'ativo'];
    protected $dates = ['data_inicio', 'data_fim'];
}
