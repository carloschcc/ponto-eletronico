<?php $url_base = getenv('APP_URL'); ?>
@extends('pontoeletronico.painel')

@section('conteudo')

<section class="content-header">
    <h1>Dashboard <small>{{ Date('d/m/Y') }}</small></h1>
</section>

<section class="content">

    {{-- Cards de resumo --}}
    <div class="row">

        @if($admin == 1)
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-aqua">
                <div class="inner">
                    <h3>{{ $total_colaboradores }}</h3>
                    <p>Colaboradores Ativos</p>
                </div>
                <div class="icon"><i class="fas fa-users"></i></div>
                <a href="{{ $url_base }}/painel/usuarios" class="small-box-footer">Ver colaboradores <i class="fa fa-arrow-circle-right"></i></a>
            </div>
        </div>
        @endif

        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-green">
                <div class="inner">
                    <h3>{{ $entradas_hoje }}</h3>
                    <p>Entradas Hoje</p>
                </div>
                <div class="icon"><i class="fas fa-sign-in-alt"></i></div>
                <a href="{{ $url_base }}/painel/acompanhamento" class="small-box-footer">Ver acompanhamento <i class="fa fa-arrow-circle-right"></i></a>
            </div>
        </div>

        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-yellow">
                <div class="inner">
                    <h3>{{ $saidas_hoje }}</h3>
                    <p>Saídas Hoje</p>
                </div>
                <div class="icon"><i class="fas fa-sign-out-alt"></i></div>
                <a href="{{ $url_base }}/painel/acompanhamento" class="small-box-footer">Ver acompanhamento <i class="fa fa-arrow-circle-right"></i></a>
            </div>
        </div>

        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-red">
                <div class="inner">
                    <h3>{{ $ajustes_pendentes }}</h3>
                    <p>Ajustes Pendentes</p>
                </div>
                <div class="icon"><i class="far fa-calendar-check"></i></div>
                @if($admin == 1)
                <a href="{{ $url_base }}/painel/certificacao" class="small-box-footer">Ver ajustes <i class="fa fa-arrow-circle-right"></i></a>
                @else
                <a href="{{ $url_base }}/painel/ajuste" class="small-box-footer">Ver ajustes <i class="fa fa-arrow-circle-right"></i></a>
                @endif
            </div>
        </div>

    </div>
    {{-- /Cards --}}

    <div class="row">

        {{-- Batidas de hoje --}}
        <div class="col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-clock-o"></i> Batidas de Hoje — {{ Date('d/m/Y') }}</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                @if($admin == 1)
                                <th>Colaborador</th>
                                @endif
                                <th>Data</th>
                                <th>Entrada</th>
                                <th>Saída</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($ultimas_batidas as $b)
                            <?php
                            // Corrige a data para objeto DateTime se for string
                            $data_formatada = $b->data;
                            if(is_string($b->data)) {
                                try {
                                    $data_formatada = (new DateTime($b->data))->format('d/m/Y');
                                } catch(Exception $e) {
                                    $data_formatada = '—';
                                }
                            } else {
                                $data_formatada = $b->data->format('d/m/Y');
                            }
                            ?>
                            <tr>
                                @if($admin == 1)
                                <td>{{ utf8_decode($b->usuario->nome) }}</td>
                                @endif
                                <td>{{ $data_formatada }}</td>
                                <td>
                                    @if($b->entrada)
                                        <span class="text-green"><i class="fa fa-sign-in"></i> {{ substr($b->entrada,0,5) }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($b->saida)
                                        <span class="text-red"><i class="fa fa-sign-out"></i> {{ substr($b->saida,0,5) }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($b->entrada_status == 1 || $b->saida_status == 1)
                                        <span class="badge bg-yellow">Ajuste pendente</span>
                                    @elseif($b->entrada_status == 2 || $b->saida_status == 2)
                                        <span class="badge bg-green">Ajustado</span>
                                    @else
                                        <span class="badge bg-blue">Normal</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">Nenhuma batida registrada hoje.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Batidas do mês --}}
        <div class="col-md-6">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-calendar"></i> Últimos Registros do Mês</h3>
                    <div class="box-tools pull-right">
                        <small class="text-muted">{{ Date('m/Y') }}</small>
                    </div>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                @if($admin == 1)
                                <th>Colaborador</th>
                                @endif
                                <th>Data</th>
                                <th>Entrada</th>
                                <th>Saída</th>
                                <th>Horas</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($batidas_mes as $bm)
                            <?php
                            // Cálculo de horas trabalhadas
                            $ht = '';
                            if($bm->entrada && $bm->saida){
                                try {
                                    $de = new DateTime($bm->entrada);
                                    $ds = new DateTime($bm->saida);
                                    $df = $ds->diff($de);
                                    $ht = str_pad($df->h,2,'0',STR_PAD_LEFT).':'.str_pad($df->i,2,'0',STR_PAD_LEFT);
                                } catch(Exception $e) {
                                    $ht = '';
                                }
                            }

                            // 🔥 CORREÇÃO AQUI: verifica se $bm->data é objeto ou string
                            $data_formatada = '—';
                            if($bm->data) {
                                if(is_string($bm->data)) {
                                    try {
                                        $data_formatada = (new DateTime($bm->data))->format('d/m/Y');
                                    } catch(Exception $e) {
                                        $data_formatada = '—';
                                    }
                                } else {
                                    $data_formatada = $bm->data->format('d/m/Y');
                                }
                            }
                            ?>
                            <tr>
                                @if($admin == 1)
                                <td>{{ utf8_decode($bm->usuario->nome) }}</td>
                                @endif
                                <td>{{ $data_formatada }}</td>
                                <td>{{ $bm->entrada ? substr($bm->entrada,0,5) : '—' }}</td>
                                <td>{{ $bm->saida   ? substr($bm->saida,0,5)   : '—' }}</td>
                                <td>{{ $ht ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">Nenhum registro no mês.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="box-footer text-right">
                    <a href="{{ $url_base }}/painel/acompanhamento" class="btn btn-sm btn-default">Ver todos <i class="fa fa-arrow-right"></i></a>
                </div>
            </div>
        </div>

    </div>

</section>

@endsection