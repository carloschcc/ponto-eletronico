<?php $url_base = getenv('URL_BASE'); ?>
<?php
$meu_id = Session::get('login.ponto.painel.usuario_id');
if(!empty($periodo_ativo)):
    $imp_inicio = $periodo_ativo->data_inicio->format('Y-m-d');
    $imp_fim = $periodo_ativo->data_fim->format('Y-m-d');
else:
    $imp_inicio = date('Y-m-01');
    $imp_fim = date('Y-m-d');
endif;
?>
@extends('pontoeletronico.painel')

@section('conteudo')
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        Solicitações de Ajuste
      </h1>
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="row">
        <div class="col-xs-12">

          <div class="box">

            <div class="box-header">
                <div class="col-md-7" style="padding-left: 0;">
                    @if(!empty($periodo_ativo))
                    <div class="callout callout-info" style="margin:0;padding:6px 12px;">
                        <i class="fa fa-calendar"></i>
                        <strong>Período ativo:</strong> {{ $periodo_ativo->descricao }}
                        &nbsp;({{ $periodo_ativo->data_inicio->format('d/m/Y') }} a {{ $periodo_ativo->data_fim->format('d/m/Y') }})
                        <span class="label label-warning" style="margin-left:6px;">Exibindo somente este período</span>
                    </div>
                    @else
                    <span class="text-muted"><i class="fa fa-info-circle"></i> Nenhum período ativo — exibindo todo o histórico.</span>
                    @endif
                </div>
                <div class="col-md-5 text-right">
                    <a href='#modal-solicitacao' data-toggle="modal" class="btn btn-md btn-success"><i class="fa fa-plus"></i> Solicitar Inclusão de Ajuste</a>
                    <a href="/painel/espelho-v2/{{ $meu_id }}/{{ $imp_inicio }}/{{ $imp_fim }}" target="_blank" class="btn btn-md btn-warning"><i class="fa fa-file-text"></i> Imprimir Ponto</a>
                </div>
            </div>

            <div class="box-body table-responsive">
              <table class="table table-bordered table-striped">
                <thead>
                <tr>
                  <th width="10%">Data do Pedido</th>
                  <th width="10%">Registro</th>
                  <th width="10%">Dia</th>
                  <th width="10%">Hora</th>
                  <th width="30%">Justificativa</th>
                  <th width="15%">Anexo</th>
                  <th width="10%">Status</th>
                  <th width="5%"></th>
                </tr>
                </thead>
                <tbody>
                <?php
                $registro_dia = '';

                ?>
                @foreach($solicitacoes as $solicitacao)

                    <tr>
                      <td>{{ $solicitacao->created_at->format("d/m/Y H:i:s") }}</td>
                      <td>{{ ucfirst($solicitacao->tipo) }}</td>
                      <td>{{ $solicitacao->data->format("d/m/Y") }}</td>
                      <td>{{ substr($solicitacao->hora, 0, 5) }}</td>
                      <td>{{ optional($solicitacao->pontoRazao)->descricao ?? '—' }}</td>
                      <td><a href="{{ $url_base }}/upload/razao/{{ $solicitacao->anexo }}">{{ $solicitacao->anexo }}</a></td>
                      <td>
                         @if($solicitacao->status == 0)
                            Pendente
                         @endif

                         @if($solicitacao->status == 3)
                            Pré-aprovado
                         @endif

                         @if($solicitacao->status == 1)
                            Aprovado
                         @endif

                         @if($solicitacao->status == 2)
                            Não aprovado
                         @endif
                      </td>
                      <td>
                         @if($solicitacao->status == 0)
                         <a href='ajuste/excluir/{{ $solicitacao->id }}' class="btn btn-acao btn-danger"><i class='fa fa-ban'></i> Excluir</a>
                         @endif
                      </td>

                    </tr>
                @endforeach
                </tbody>
              </table>
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

    <div id="modal-solicitacao" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header text-center">
                    <h2 class="modal-title">Solicitação de Inclusão de Ajuste</h2>
                </div>

                <form id="form-func-solicitacao" method="post" action="/painel/ponto/periodo/salvar" enctype="multipart/form-data">

                    {{ csrf_field() }}

                    <div class="modal-body">

                                <input type="hidden" name="tipo" value="periodo">
                                <p class="text-muted"><small>Preencha ao menos um dos horários abaixo.</small></p>

                                <div class="row form-group">
                                    <div class="col-md-4">
                                        <input type="text" name="data" class="form-control datepicker" placeholder="Data" value="" required>
                                    </div>
                                </div>

                                <div class="row form-group">
                                    <div class="col-md-4">
                                        <input type="text" id="func-hora-entrada" name="hora_entrada" class="form-control time" placeholder="Hora de Entrada (opcional)" value="">
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" id="func-hora-saida" name="hora_saida" class="form-control time" placeholder="Hora de Saída (opcional)" value="">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Justificativa</label>
                                    <select name="justificativa" class="form-control" required>
                                        @foreach($justificativas as $justificativa)
                                        <option value="{{ $justificativa->id }}">{{ $justificativa->descricao }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Anexo</label>
                                    <input type="file" name="anexo">
                                </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Fechar</button>
                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </div>

                </form>
                <br>
            </div>
        </div>
    </div>

@push('scripts')
<script>
$('#form-func-solicitacao').on('submit', function(e){
    var entrada = $.trim($('#func-hora-entrada').val());
    var saida   = $.trim($('#func-hora-saida').val());
    if(entrada === '' && saida === ''){
        e.preventDefault();
        alert('Informe pelo menos um horário: Entrada ou Saída.');
        return false;
    }
});
</script>
@endpush

@endsection
