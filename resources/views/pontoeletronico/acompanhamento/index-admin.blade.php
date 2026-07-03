<?php $url_base = getenv('URL_BASE'); ?>
<?php
$admin = Session::get('login.ponto.painel.admin');
?>
@extends('pontoeletronico.painel')

@section('conteudo')
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        Acompanhamento 
      </h1>
    </section>

    
       <!-- Main content -->
    <section class="content">
      <div class="row">
        <!-- left column -->
        <div class="col-md-12">
          <!-- general form elements -->
          <div class="box box-primary">
            <div class="box-header with-border">
              <h3 class="box-title">Filtrar por Período</h3>
            </div>
            <!-- /.box-header -->
            <!-- form start -->
 
                <form name="form_add" method="POST" class="valid" action="/painel/acompanhamento">	
                    {{ csrf_field() }}
                    <div class="box-body">
                        @if($periodo_ativo)
                        <div class="row">
                            <div class="col-md-12">
                                <div class="callout callout-info" style="margin-bottom:8px;padding:8px 12px;">
                                    <i class="fa fa-calendar"></i>
                                    <strong>Período ativo:</strong> {{ $periodo_ativo->descricao }}
                                    &nbsp;({{ $periodo_ativo->data_inicio->format('d/m/Y') }} a {{ $periodo_ativo->data_fim->format('d/m/Y') }})
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($periodos_lista->count() > 0)
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Carregar período de fechamento</label>
                                    <select id="select-periodo" class="form-control">
                                        <option value="">— Selecione um período —</option>
                                        @foreach($periodos_lista as $p)
                                        <option value="{{ $p->data_inicio->format('d/m/Y') }}"
                                                data-fim="{{ $p->data_fim->format('d/m/Y') }}">
                                            {{ $p->descricao }} ({{ $p->data_inicio->format('d/m/Y') }} a {{ $p->data_fim->format('d/m/Y') }})
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="row">

                            <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Data início (dd/mm/aaaa)</label>
                                        <input type="text" id="input-data-inicio" value="<?=$data_inicio?>" name="data_inicio" class="form-control datepicker" required />
                                    </div>
                            </div>
                            <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Data fim (dd/mm/aaaa)</label>
                                        <input type="text" id="input-data-fim" value="<?=$data_fim?>" name="data_fim" class="form-control datepicker" required />
                                    </div>
                            </div>


                        </div>
                    </div>

                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary pull-right"><span>Buscar</span></button>
                    </div>
                </form>    
            
            
          </div>
          <!-- /.box -->

        </div>
        <!--/.col (left) -->
      </div>
      <!-- /.row -->
    </section>
    <!-- /.content --> 
    
    
    <!-- Main content -->
    <section class="content">
      <div class="row">
        <div class="col-xs-12">
          
          <div class="box">
            @if($admin !== 1)
            <div class="box-header">
                <div class="col-md-6" style="padding-left: 0;">
                    <h3 class="box-title">Registros de: <b><?=$data_inicio?> a <?=$data_fim?></b></h3>
                </div>  
            </div>
            @endif
            @if($admin == 1)
            <?php
            if(strpos($data_inicio, '/') !== false) {
                $arr = explode('/', $data_inicio);
                $data_inicio = $arr[2].'-'.$arr[1].'-'.$arr[0];
            }
            if(strpos($data_fim, '/') !== false) {
                $arr = explode('/', $data_fim);
                $data_fim = $arr[2].'-'.$arr[1].'-'.$arr[0];
            }
            ?>
            @endif
            <!-- /.box-header -->
            @if($admin == 1)
            <div class="box-header" style="padding-bottom:4px; border-bottom:1px solid #f4f4f4;">
                <div class="col-md-5" style="padding-left:0;">
                    @if(count($data) > 0)
                    <div class="input-group">
                        <span class="input-group-addon"><i class="fa fa-search"></i></span>
                        <input type="text" id="filtro-resultado" class="form-control" placeholder="Filtrar colaborador nos resultados...">
                    </div>
                    @endif
                </div>
                <div class="col-md-7 text-right">
                    {{-- href server-rendered com as datas já convertidas para Y-m-d --}}
                    <a id="btn-espelho-todos"
                       href="/painel/relatorio/all/{{ $data_inicio }}/{{ $data_fim }}"
                       target="_blank"
                       class="btn btn-sm btn-success">
                        <i class="fa fa-print"></i> Espelho — Todos
                    </a>
                    <a href="/painel/espelho-v2/all/{{ $data_inicio }}/{{ $data_fim }}"
                       target="_blank"
                       class="btn btn-sm btn-warning">
                        <i class="fa fa-file-text"></i> Espelho V2 — Todos
                    </a>
                    <a href="/painel/export-txt/all/{{ $data_inicio }}/{{ $data_fim }}"
                       class="btn btn-sm btn-default">
                        <i class="fa fa-file-text-o"></i> Exportar TXT — Todos
                    </a>
                    <a href="/painel/geolocalizacao/all/{{ $data_inicio }}/{{ $data_fim }}"
                       target="_blank"
                       class="btn btn-sm btn-info">
                        <i class="fa fa-map-marker"></i> Geolocalização — Todos
                    </a>
                </div>
            </div>
            @else
            @if(count($data) > 0)
            <div class="box-header" style="padding-bottom:0;">
                <div class="col-md-5" style="padding-left:0;">
                    <div class="input-group">
                        <span class="input-group-addon"><i class="fa fa-search"></i></span>
                        <input type="text" id="filtro-resultado" class="form-control" placeholder="Filtrar colaborador nos resultados...">
                    </div>
                </div>
            </div>
            @endif
            @endif
            <div class="box-body table-responsive">

              <div class="box-group" id="accordion">
                
                @foreach ($data as $nome => $registros)
                
                    @php
                    $registro_dia = '';
                    $conta_registro = 0;
                    $total_registro = count($registros);
                    $horas_trabalhadas_total = 0;
                    $horas_trabalhadas_total_dia = 0;
                    $registro_nome = str_replace(" ", "", $nome);
                    @endphp
                    
                    @if($conta_registro == 0)
                        <div class="panel box box-primary">
                            <div class="box-header with-border">
                                <div class="col-md-6">
                                <h4 class="box-title">
                                    
                                    <a data-toggle="collapse" data-parent="#accordion" href="#{{ $registro_nome }}" aria-expanded="false" class="collapsed">
                                        {{ strtoupper($nome) }}
                                    </a>
                                    
                                </h4>
                                </div>     
                                <div class="col-md-6 text-right">
                                    <?php $uid = $registros[0]->usuario_id; ?>
                                    <a href="/painel/relatorio/{{ $uid }}/{{ $data_inicio }}/{{ $data_fim }}" target="_blank" class="btn btn-xs btn-primary"><i class="fa fa-print"></i> Espelho</a>
                                    <a href="/painel/espelho-v2/{{ $uid }}/{{ $data_inicio }}/{{ $data_fim }}" target="_blank" class="btn btn-xs btn-warning"><i class="fa fa-file-text"></i> Espelho V2</a>
                                    <a href="/painel/excel-acompanhamento/{{ $uid }}/{{ $data_inicio }}/{{ $data_fim }}" class="btn btn-xs btn-default"><i class="fa fa-file-excel-o"></i> Excel</a>
                                    <a href="/painel/export-txt/{{ $uid }}/{{ $data_inicio }}/{{ $data_fim }}" class="btn btn-xs btn-default"><i class="fa fa-file-text-o"></i> TXT</a>
                                    <a href="/painel/geolocalizacao/{{ $uid }}/{{ $data_inicio }}/{{ $data_fim }}" target="_blank" class="btn btn-xs btn-info"><i class="fa fa-map-marker"></i> Geolocalização</a>
                                </div>
                            </div>
                            <div id="{{ $registro_nome }}" class="panel-collapse collapse" aria-expanded="false" style="height: 0px;">
                                <div class="box-body">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                              <th width="15%">Dia</th>
                                              <th width="10%">Entrada</th>
                                              <th width="10%">Saída</th>
                                              <th width="10%">Tempo Trabalhado</th>
                                              <th width="10%">Intervalo</th>
                                              <th width="45%">Obs</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                    @endif
                    
                    @foreach($registros as $registro)
                        <?php

                        $varData = '';
                        if($registro_dia != $registro->data):
                            $varData = $registro->data;
                            $data_arr = explode("-", $varData);
                            $varData = $data_arr[2].'/'.$data_arr[1].'/'.$data_arr[0];
                            $ultima_hora_saida = '';
                            if(isset($horas_trabalhadas_total_dia)):
                                $horas_trabalhadas_total_dia_bkp = $horas_trabalhadas_total_dia;
                                $horas_trabalhadas_total = $horas_trabalhadas_total + $horas_trabalhadas_total_dia;
                            endif;
                            $horas_trabalhadas_total_dia = 0;
                            $varData2 = $varData;
                        endif;


                        $horas_trabalhadas = '';

                        if(!empty($registro->entrada) AND !empty($registro->saida)):
                            $entrada = new DateTime($registro->entrada);
                            $saida = new DateTime($registro->saida);
                            $intervalo = $saida->diff($entrada);

                            $intervalo_hora = $intervalo->h;
                            if(strlen($intervalo->h) == 1):
                                $intervalo_hora = '0'.$intervalo->h;
                            endif;

                            $intervalo_minuto = $intervalo->i;
                            if(strlen($intervalo->i) == 1):
                                $intervalo_minuto = '0'.$intervalo->i;
                            endif;

                            $horas_trabalhadas = $intervalo_hora.':'.$intervalo_minuto;

                            $horas_trabalhadas_total_dia = $horas_trabalhadas_total_dia + ($intervalo->h*60 + $intervalo->i);


                        endif;



                        $intervalo_pausa = '';


                        if(!empty($ultima_hora_saida) AND !empty($registro->entrada)):

                            $entrada = new DateTime($ultima_hora_saida);
                            $saida = new DateTime($registro->entrada);
                            $intervalo2 = $saida->diff($entrada);

                            $intervalo2_hora = $intervalo2->h;
                            if(strlen($intervalo2->h) == 1):
                                $intervalo2_hora = '0'.$intervalo2->h;
                            endif;

                            $intervalo2_minuto = $intervalo2->i;
                            if(strlen($intervalo2->i) == 1):
                                $intervalo2_minuto = '0'.$intervalo2->i;
                            endif;

                            $intervalo_pausa = $intervalo2_hora.':'.$intervalo2_minuto;

                        endif;

                        $ultima_hora_saida = $registro->saida;


                        ?>
                        
                        
                        
                        @if($registro_dia != $registro->data AND $registro_dia != '')
                            <?php
                            $horas_trabalhadas_total_dia_h = (int) ($horas_trabalhadas_total_dia_bkp / 60);
                            $horas_trabalhadas_total_dia_m = ($horas_trabalhadas_total_dia_bkp-($horas_trabalhadas_total_dia_h*60));

                            if(strlen($horas_trabalhadas_total_dia_h) == 1):
                                $horas_trabalhadas_total_dia_h = '0'.$horas_trabalhadas_total_dia_h;
                            endif;

                            if(strlen($horas_trabalhadas_total_dia_m) == 1):
                                $horas_trabalhadas_total_dia_m = '0'.$horas_trabalhadas_total_dia_m;
                            endif;
                            ?>
                            <tr>
                                <td colspan="3" align='right'><b>Total Trabalhado:</b></td>
                                <td colspan="3"><?=$horas_trabalhadas_total_dia_h?>:<?=$horas_trabalhadas_total_dia_m?></td>
                            </tr>
                        @endif
                        <tr>
                          <td><b>{{ $varData }}</b></td>
                          <td>
                              @if($registro->entrada_status === NULL)
                                <i class="fas fa-exclamation-triangle text-yellow"></i>
                              @else
                                <?php
                                if ($registro->entrada_status == 0) $varCor = '#005599';
                                if ($registro->entrada_status == 1) $varCor = '#D39745';
                                if ($registro->entrada_status == 2) $varCor = '#67b021';
                                ?>
                                <span style="color: <?=$varCor?>;">{{ substr($registro->entrada, 0, 5) }}</span>
                                <a href="#" data-url="/painel/ponto/excluir-campo/{{ $registro->id }}/entrada" data-msg="Deseja excluir a batida de ENTRADA deste dia?" class="btn btn-xs btn-danger btnExluir" style="margin-left:3px;" title="Excluir entrada"><i class="fa fa-trash"></i></a>
                              @endif
                          </td>
                          <td>
                              @if($registro->saida_status === NULL)
                                <i class="fas fa-exclamation-triangle text-yellow"></i>
                              @else
                                <?php
                                if ($registro->saida_status == 0) $varCor = '#005599';
                                if ($registro->saida_status == 1) $varCor = '#D39745';
                                if ($registro->saida_status == 2) $varCor = '#67b021';
                                ?>
                                <span style="color: <?=$varCor?>;">{{ substr($registro->saida, 0, 5) }}</span>
                                <a href="#" data-url="/painel/ponto/excluir-campo/{{ $registro->id }}/saida" data-msg="Deseja excluir a batida de SAÍDA deste dia?" class="btn btn-xs btn-danger btnExluir" style="margin-left:3px;" title="Excluir saída"><i class="fa fa-trash"></i></a>
                              @endif
                          </td>
                          <td><?=$horas_trabalhadas?></td>
                          <td><?=$intervalo_pausa?></td>
                          <td>
                              <?php
                              // Só exibe ajustes cujo campo ainda existe no registro
                              $ajustes = App\PontoAjuste::where('ponto_id', '=', $registro->id)
                                  ->whereIn('status', array(0, 1, 2))
                                  ->orderBy('created_at', 'ASC')
                                  ->get()
                                  ->filter(function($aj) use ($registro){
                                      if($aj->tipo == 'entrada') return !is_null($registro->entrada);
                                      if($aj->tipo == 'saida')   return !is_null($registro->saida);
                                      return true;
                                  });
                              ?>
                              @foreach($ajustes as $ajuste)
                                @if($ajuste->status == 0)
                                    <span class="badge bg-yellow">Pendente em {{ $ajuste->created_at->format('d/m/Y') }}</span>
                                @elseif($ajuste->status == 1)
                                    <span class="badge bg-green">Aprovado em {{ $ajuste->updated_at->format('d/m/Y') }}</span>
                                @else
                                    <span class="badge bg-red">Não Aprovado em {{ $ajuste->updated_at->format('d/m/Y') }}</span>
                                @endif
                                @if(!empty($ajuste->obs_supervisor))
                                    {{ $ajuste->obs_supervisor }}
                                @endif
                                @if(!empty($ajuste->anexo))
                                    <a href='../upload/razao/{{ $ajuste->anexo }}' class="btn btn-xs btn-default" target='blank'>Anexo</a>
                                @endif
                                <br>
                              @endforeach
                          </td>
                        </tr>
                        <?php
                        $registro_dia = $registro->data;
                        $conta_registro++;
                        ?>

                        @if($conta_registro == $total_registro)

                            <?php
                            $horas_trabalhadas_total = $horas_trabalhadas_total + $horas_trabalhadas_total_dia;

                            $horas_trabalhadas_total_dia_h = (int) ($horas_trabalhadas_total_dia / 60);
                            $horas_trabalhadas_total_dia_m = ($horas_trabalhadas_total_dia-($horas_trabalhadas_total_dia_h*60));

                            if(strlen($horas_trabalhadas_total_dia_h) == 1):
                                $horas_trabalhadas_total_dia_h = '0'.$horas_trabalhadas_total_dia_h;
                            endif;

                            if(strlen($horas_trabalhadas_total_dia_m) == 1):
                                $horas_trabalhadas_total_dia_m = '0'.$horas_trabalhadas_total_dia_m;
                            endif;
                            ?>
                            <tr>
                                <td colspan="3" align='right'><b>Total Trabalhado:</b></td>
                                <td colspan="3"><?=$horas_trabalhadas_total_dia_h?>:<?=$horas_trabalhadas_total_dia_m?></td>
                            </tr>

                            <?php
                            $horas_trabalhadas_total_h = (int) ($horas_trabalhadas_total / 60);
                            $horas_trabalhadas_total_m = ($horas_trabalhadas_total-($horas_trabalhadas_total_h*60));

                            if(strlen($horas_trabalhadas_total_h) == 1):
                                $horas_trabalhadas_total_h = '0'.$horas_trabalhadas_total_h;
                            endif;

                            if(strlen($horas_trabalhadas_total_m) == 1):
                                $horas_trabalhadas_total_m = '0'.$horas_trabalhadas_total_m;
                            endif;
                            ?>
                            <tr>
                                <td colspan="3" align='right'><b>Total trabalhado no período de <span style="color: #900;"><?=$data_inicio?></span> a <span style="color: #900;"><?=$data_fim?></span> ({{ strtoupper($nome) }}):</b></td>
                                <td colspan="3"><b><?=$horas_trabalhadas_total_h?>:<?=$horas_trabalhadas_total_m?></b></td>
                            </tr>

                        @endif

                    @endforeach 
                    
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>                
                @endforeach 
                
              
              </div>
                
            </div>
            <!-- /.box-body -->
          </div>
          <!-- /.box -->
        </div>
        <!-- /.col -->
      </div>
      <!-- /.row -->
    </section>
    <!-- /.content -->
@push('scripts')
<script>
$(document).ready(function(){
    // Seletor de período de fechamento — preenche as datas e submete o formulário
    $('#select-periodo').on('change', function(){
        var opt = $(this).find(':selected');
        if(opt.val()){
            $('#input-data-inicio').val(opt.val());
            $('#input-data-fim').val(opt.data('fim'));
            $('form[name="form_add"]').submit();
        }
    });

    // Preenche datas se vier de um período de fechamento (via sessionStorage da tela de períodos)
    var pInicio = sessionStorage.getItem('periodo_inicio');
    var pFim    = sessionStorage.getItem('periodo_fim');
    if(pInicio && pFim){
        $('input[name="data_inicio"]').val(pInicio);
        $('input[name="data_fim"]').val(pFim);
        sessionStorage.removeItem('periodo_inicio');
        sessionStorage.removeItem('periodo_fim');
        // Submete automaticamente para carregar os dados do período
        setTimeout(function(){ document.querySelector('form[name="form_add"]').submit(); }, 100);
    }

    // Filtro dos resultados (já existente)
    $('#filtro-resultado').on('input', function(){
        var termo = $(this).val().toLowerCase().trim();
        $('.panel.box.box-primary').each(function(){
            var nome = $(this).find('.box-title a').text().toLowerCase();
            $(this).toggle(nome.indexOf(termo) >= 0 || termo === '');
        });
    });
});
</script>
@endpush
@endsection