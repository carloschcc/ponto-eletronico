<?php $url_base = getenv('APP_URL'); ?>
<?php $admin_atual = Session::get('login.ponto.painel.admin'); ?>
<?php $rh_atual = Session::get('login.ponto.painel.rh'); ?>
<?php $usuario_logado_id = Session::get('login.ponto.painel.usuario_id'); ?>
@extends('pontoeletronico.painel')

@section('conteudo')
    <section class="content-header">
      <h1>Solicitações de Ajuste</h1>
    </section>

    <section class="content">

      <div class="alert alert-info alert-dismissible">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <h4><i class="icon fa fa-info"></i> Instruções</h4>
        Clique no nome do colaborador para aprovar/rejeitar individualmente. Use os checkboxes para ações em lote.
        Solicitações suas aparecem na lista, mas você não pode aprovar/rejeitar a própria solicitação.
        Aprovação de gerente é uma pré-aprovação — RH/admin ainda podem excluir até a aprovação definitiva.
      </div>

      <div class="row">
        <div class="col-xs-12 text-right" style="margin-bottom:10px;">
          <a href='#modal-solicitacao' data-toggle="modal" class="btn btn-md btn-success"><i class="fa fa-plus"></i> Solicitar Inclusão de Ajuste</a>
        </div>
      </div>

      <div class="row">
        <div class="col-xs-12">
          <div class="box">

            {{-- Painel de ações em lote --}}
            <form id="form-bulk" method="POST" action="/painel/certificacao/bulk">
              {{ csrf_field() }}

              <div class="box-header with-border">
                <div class="col-md-8" style="padding-left:0;">
                    <label for="obs_bulk" style="font-weight:600;">Observação (para ações em lote):</label>
                    <input type="text" name="obs_bulk" id="obs_bulk" class="form-control" placeholder="Justificativa da ação em lote..." style="max-width:400px;display:inline-block;margin-left:8px;">
                </div>
                <div class="col-md-4 text-right" style="padding-top:4px;">
                    <button type="submit" name="acao" value="aprovar" class="btn btn-success btn-sm" onclick="return confirmarBulk('aprovar')">
                        <i class="fa fa-check"></i> Aprovar Selecionados
                    </button>
                    <button type="submit" name="acao" value="rejeitar" class="btn btn-danger btn-sm" onclick="return confirmarBulk('rejeitar')">
                        <i class="fa fa-times"></i> Rejeitar Selecionados
                    </button>
                </div>
              </div>

              <div class="box-body table-responsive">
                <table id="tbl-ajustes" class="table table-bordered table-striped">
                  <thead>
                    <tr>
                      <th width="3%"><input type="checkbox" id="chk-todos" title="Selecionar todos"></th>
                      <th width="10%">Data do Pedido</th>
                      <th width="12%">Colaborador</th>
                      <th width="8%">Registro</th>
                      <th width="8%">Dia</th>
                      <th width="7%">Hora</th>
                      <th width="24%">Justificativa</th>
                      <th width="8%">Anexo</th>
                      <th width="8%">Status</th>
                      <th width="5%">Ações</th>
                    </tr>
                  </thead>
                  <tbody>
                  @foreach($solicitacoes as $solicitacao)
                  <?php $e_propria = ($solicitacao->usuario_id == $usuario_logado_id); ?>
                    <tr>
                      <td>
                          @if(in_array($solicitacao->status, [0, 3]) AND !$e_propria)
                          <input type="checkbox" name="solicitacoes[]" value="{{ $solicitacao->id }}" class="chk-solicitacao">
                          @endif
                      </td>
                      <td>{{ $solicitacao->created_at->format("d/m/Y H:i:s") }}</td>
                      <td>
                          @if($e_propria)
                              {{ utf8_decode($solicitacao->usuario->nome) }} <small class="text-muted">(sua solicitação)</small>
                          @else
                              <a href="#modal-aprovacao-{{ $solicitacao->id }}" data-toggle="modal">
                                  {{ utf8_decode($solicitacao->usuario->nome) }}
                              </a>
                          @endif
                      </td>
                      <td>{{ ucfirst($solicitacao->tipo) }}</td>
                      <td>{{ $solicitacao->data->format("d/m/Y") }}</td>
                      <td>{{ substr($solicitacao->hora, 0, 5) }}</td>
                      <td>{{ optional($solicitacao->pontoRazao)->descricao ?? '—' }}</td>
                      <td>
                          @if(empty($solicitacao->anexo))
                            <span class="text-muted">Sem anexo</span>
                          @else
                            <a href="{{ $url_base }}/upload/razao/{{ $solicitacao->anexo }}" target="_blank">
                                <i class="fa fa-paperclip"></i> Ver
                            </a>
                          @endif
                      </td>
                      <td>
                         @if($solicitacao->status == 0)
                            <span class="badge bg-yellow">Pendente</span>
                         @elseif($solicitacao->status == 3)
                            <span class="badge bg-aqua">Pré-aprovado (gerente)</span>
                         @elseif($solicitacao->status == 1)
                            <span class="badge bg-green">Aprovado</span>
                         @else
                            <span class="badge bg-red">Não aprovado</span>
                         @endif
                      </td>
                      <td>
                         @if($admin_atual == 1 OR $rh_atual == 1)
                         <a href="#" data-url="/painel/ajuste/admin/excluir/{{ $solicitacao->id }}"
                            data-msg="Excluir esta solicitação? Se aprovada, a batida será revertida."
                            class="btn btn-acao btn-danger btnExluir" title="Excluir">
                             <i class="fa fa-trash"></i>
                         </a>
                         @endif
                      </td>
                    </tr>
                  @endforeach
                  </tbody>
                </table>
              </div>
            </form>
            {{-- /form-bulk --}}

          </div>
        </div>
      </div>

      {{-- Modais de aprovação individual — ficam FORA do form-bulk de propósito:
           <form> dentro de <form> é HTML inválido, o navegador descarta a tag de
           abertura do formulário aninhado e o primeiro "</form>" que aparecer
           fecha o form-bulk antes da hora. Isso fazia o clique em "Aprovar"
           submeter pro endpoint de ações em lote (sem nada selecionado), então
           a solicitação nunca mudava de status. --}}
      @foreach($solicitacoes as $solicitacao)
      <?php $e_propria = ($solicitacao->usuario_id == $usuario_logado_id); ?>
      @if(!$e_propria)
      <div id="modal-aprovacao-{{ $solicitacao->id }}" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
          <div class="modal-dialog modal-md">
              <div class="modal-content">
                  <div class="modal-header text-center">
                      <h2 class="modal-title">Informações do Pedido</h2>
                  </div>
                  <form method="post" action="/painel/certificacao/salvar" enctype="multipart/form-data">
                      {{ csrf_field() }}
                      <div class="modal-body">
                          <input type="hidden" name="solicitacao_id" value="{{ $solicitacao->id }}">
                          <div class="row form-group">
                              <div class="col-md-12">
                                  <p>
                                      <label>Colaborador:</label> {{ utf8_decode($solicitacao->usuario->nome) }}<br>
                                      <label>Data do pedido:</label> {{ $solicitacao->created_at->format("d/m/Y H:i:s") }}
                                  </p>
                                  <p>
                                      <label>Tipo:</label> {{ ucfirst($solicitacao->tipo) }}<br>
                                      <label>Data:</label> {{ $solicitacao->data->format("d/m/Y") }}<br>
                                      <label>Hora:</label> {{ substr($solicitacao->hora, 0, 5) }}<br>
                                      <label>Justificativa:</label> {{ optional($solicitacao->pontoRazao)->descricao ?? '—' }}<br>
                                      <label>Anexo:</label>
                                      @if(empty($solicitacao->anexo))
                                          Sem anexo
                                      @else
                                          <a href="{{ $url_base }}/upload/razao/{{ $solicitacao->anexo }}" target="_blank">{{ $solicitacao->anexo }}</a>
                                      @endif
                                  </p>
                                  <br>
                                  <div class="box box-success">
                                      <div class="box-header with-border">
                                          <h3 class="box-title">Certificação</h3>
                                      </div>
                                      <div class="box-body">
                                          <label>Observação do supervisor:</label>
                                          <textarea name="obs" class="form-control" rows="3"></textarea>
                                      </div>
                                  </div>
                              </div>
                          </div>
                      </div>
                      <div class="modal-footer">
                          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Fechar</button>
                          <button type="submit" name="botao" value="sim" class="btn btn-success"><i class="fa fa-check"></i> Aprovar</button>
                          <button type="submit" name="botao" value="nao" class="btn btn-danger"><i class="fa fa-times"></i> Não aprovar</button>
                      </div>
                  </form>
              </div>
          </div>
      </div>
      @endif
      @endforeach

      {{-- Modal nova solicitação --}}
      <div id="modal-solicitacao" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
          <div class="modal-dialog modal-md">
              <div class="modal-content">
                  <div class="modal-header text-center">
                      <h2 class="modal-title">Solicitação de Inclusão de Ajuste</h2>
                  </div>
                  <form id="form-nova-solicitacao" method="post" action="/painel/ponto/periodo/salvar" enctype="multipart/form-data">
                      {{ csrf_field() }}
                      <div class="modal-body">
                          <input type="hidden" name="tipo" value="periodo">
                          <p class="text-muted"><small>Preencha ao menos um dos horários abaixo.</small></p>
                          <div class="row form-group">
                              <div class="col-md-4">
                                  <input type="text" name="data" class="form-control datepicker" placeholder="Data" required>
                              </div>
                          </div>
                          <div class="row form-group">
                              <div class="col-md-4">
                                  <input type="text" id="ns-hora-entrada" name="hora_entrada" class="form-control time" placeholder="Hora de Entrada (opcional)">
                              </div>
                              <div class="col-md-4">
                                  <input type="text" id="ns-hora-saida" name="hora_saida" class="form-control time" placeholder="Hora de Saída (opcional)">
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
              </div>
          </div>
      </div>

    </section>

@push('scripts')
<script>
// Selecionar/desselecionar todos
$('#chk-todos').on('change', function(){
    $('.chk-solicitacao').prop('checked', this.checked);
});

// Se um filho desmarcar, desmarcar o pai
$(document).on('change', '.chk-solicitacao', function(){
    if(!this.checked) $('#chk-todos').prop('checked', false);
});

// Validação do modal de nova solicitação: exige pelo menos um horário
$('#form-nova-solicitacao').on('submit', function(e){
    var entrada = $.trim($('#ns-hora-entrada').val());
    var saida   = $.trim($('#ns-hora-saida').val());
    if(entrada === '' && saida === ''){
        e.preventDefault();
        alert('Informe pelo menos um horário: Entrada ou Saída.');
        return false;
    }
});

// Confirmação antes de ação em lote
function confirmarBulk(acao) {
    var qtd = $('.chk-solicitacao:checked').length;
    if(qtd === 0){
        alert('Selecione pelo menos uma solicitação.');
        return false;
    }
    var label = acao === 'aprovar' ? 'APROVAR' : 'REJEITAR';
    return confirm('Deseja ' + label + ' as ' + qtd + ' solicitação(ões) selecionada(s)?');
}
</script>
@endpush

@endsection
