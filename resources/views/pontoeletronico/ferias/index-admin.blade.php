<?php $url_base = getenv('APP_URL'); ?>
<?php $admin_atual = Session::get('login.ponto.painel.admin'); ?>
<?php $rh_atual = Session::get('login.ponto.painel.rh'); ?>
<?php $gerente_atual = Session::get('login.ponto.painel.gerente'); ?>
<?php $usuario_logado_id = Session::get('login.ponto.painel.usuario_id'); ?>
@extends('pontoeletronico.painel')

@section('conteudo')
    <section class="content-header">
      <h1>Férias</h1>
    </section>

    <section class="content">

      <div class="alert alert-info alert-dismissible">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <h4><i class="icon fa fa-info"></i> Instruções</h4>
        Clique no nome do colaborador para aprovar/rejeitar individualmente. Use os checkboxes para ações em lote.
        Solicitações suas aparecem na lista, mas você não pode aprovar/rejeitar a própria solicitação.
      </div>

      {{-- Painel de totais --}}
      <div class="row">
        <div class="col-md-4 col-sm-4 col-xs-12">
          <div class="small-box bg-blue">
            <div class="inner">
              <h3>{{ $totais['agendada'] }}</h3>
              <p>Agendada</p>
            </div>
            <div class="icon"><i class="fa fa-calendar"></i></div>
          </div>
        </div>
        <div class="col-md-4 col-sm-4 col-xs-12">
          <div class="small-box bg-green">
            <div class="inner">
              <h3>{{ $totais['em_ferias'] }}</h3>
              <p>Em Férias</p>
            </div>
            <div class="icon"><i class="fa fa-umbrella-beach"></i></div>
          </div>
        </div>
        <div class="col-md-4 col-sm-4 col-xs-12">
          <div class="small-box bg-yellow">
            <div class="inner">
              <h3>{{ $totais['retornando'] }}</h3>
              <p>Retornando</p>
            </div>
            <div class="icon"><i class="fa fa-sign-in-alt"></i></div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-xs-12">
          <div class="box">

            {{-- Painel de ações em lote --}}
            <form id="form-bulk" method="POST" action="{{ $url_base }}/painel/ferias/certificar/bulk">
              {{ csrf_field() }}

              <div class="box-header with-border">
                <h3 class="box-title">Solicitações Pendentes</h3>
              </div>

              <div class="box-header with-border">
                <div class="col-md-8" style="padding-left:0;">
                    <label for="obs_bulk" style="font-weight:600;">Observação (para ações em lote):</label>
                    <input type="text" name="obs_bulk" id="obs_bulk" class="form-control" placeholder="Justificativa da ação em lote..." style="max-width:400px;display:inline-block;margin-left:8px;">
                </div>
                <div class="col-md-4 text-right" style="padding-top:4px;">
                    <a href="{{ $url_base }}/painel/ferias/imprimir" target="_blank" class="btn btn-default btn-sm">
                        <i class="fa fa-print"></i> Imprimir Agendada/Em Férias
                    </a>
                    <button type="submit" name="acao" value="aprovar" class="btn btn-success btn-sm" onclick="return confirmarBulk('aprovar')">
                        <i class="fa fa-check"></i> Aprovar Selecionados
                    </button>
                    <button type="submit" name="acao" value="rejeitar" class="btn btn-danger btn-sm" onclick="return confirmarBulk('rejeitar')">
                        <i class="fa fa-times"></i> Rejeitar Selecionados
                    </button>
                </div>
              </div>

              <div class="box-body table-responsive">
                <table id="tbl-ferias-pendentes" class="table table-bordered table-striped">
                  <thead>
                    <tr>
                      <th width="3%"><input type="checkbox" id="chk-todos" title="Selecionar todos"></th>
                      <th width="10%">Data do Pedido</th>
                      <th width="14%">Colaborador</th>
                      <th width="10%">Tipo</th>
                      <th width="6%">Ano Ref.</th>
                      <th width="10%">Início</th>
                      <th width="10%">Fim</th>
                      <th width="5%">Dias</th>
                      <th width="14%">Observação</th>
                      <th width="10%">Status</th>
                      <th width="8%">Ações</th>
                    </tr>
                  </thead>
                  <tbody>
                  @forelse($solicitacoes as $solicitacao)
                  <?php $e_propria = ($solicitacao->usuario_id == $usuario_logado_id); ?>
                    <tr>
                      <td>
                          @if(!$e_propria)
                          <input type="checkbox" name="solicitacoes[]" value="{{ $solicitacao->id }}" class="chk-solicitacao">
                          @endif
                      </td>
                      <td>{{ $solicitacao->created_at->format("d/m/Y H:i:s") }}</td>
                      <td>
                          @if($e_propria)
                              {{ utf8_decode($solicitacao->usuario->nome) }} <small class="text-muted">(sua solicitação)</small>
                          @else
                              <a href="#modal-aprovacao-ferias-{{ $solicitacao->id }}" data-toggle="modal">
                                  {{ utf8_decode($solicitacao->usuario->nome) }}
                              </a>
                          @endif
                      </td>
                      <td>{{ $solicitacao->tipoLabel() }}</td>
                      <td>{{ $solicitacao->ano_referencia }}</td>
                      <td>{{ $solicitacao->data_inicio->format('d/m/Y') }}</td>
                      <td>{{ $solicitacao->data_fim->format('d/m/Y') }}</td>
                      <td>{{ $solicitacao->dias() }}</td>
                      <td>{{ $solicitacao->observacao ?? '—' }}</td>
                      <td>
                         @if($solicitacao->status == 0)
                            <span class="badge bg-yellow">Pendente</span>
                         @elseif($solicitacao->status == 3)
                            <span class="badge bg-aqua">Pré-aprovado (gerente)</span>
                         @endif
                      </td>
                      <td>
                         @if($admin_atual == 1 OR $rh_atual == 1)
                         <a href="#" data-url="{{ $url_base }}/painel/ferias/admin/excluir/{{ $solicitacao->id }}"
                            data-msg="Excluir esta solicitação de férias?"
                            class="btn btn-acao btn-danger btnExluir" title="Excluir">
                             <i class="fa fa-trash"></i>
                         </a>
                         @endif
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="11" class="text-center text-muted" style="padding:20px;">Nenhuma solicitação pendente.</td>
                    </tr>
                  @endforelse
                  </tbody>
                </table>
              </div>
            </form>
            {{-- /form-bulk --}}

          </div>
        </div>
      </div>

      {{-- Modais de aprovação individual — de propósito FORA do form-bulk:
           <form> dentro de <form> é HTML inválido e quebra a submissão
           (ver o mesmo bug já corrigido em ajuste/index-admin.blade.php). --}}
      @foreach($solicitacoes as $solicitacao)
      <?php $e_propria = ($solicitacao->usuario_id == $usuario_logado_id); ?>
      @if(!$e_propria)
      <div id="modal-aprovacao-ferias-{{ $solicitacao->id }}" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
          <div class="modal-dialog modal-md">
              <div class="modal-content">
                  <div class="modal-header text-center">
                      <h2 class="modal-title">Solicitação de Férias</h2>
                  </div>
                  <form method="post" action="{{ $url_base }}/painel/ferias/certificar">
                      {{ csrf_field() }}
                      <div class="modal-body">
                          <input type="hidden" name="solicitacao_id" value="{{ $solicitacao->id }}">
                          <p>
              <label>Colaborador:</label> {{ utf8_decode($solicitacao->usuario->nome) }}<br>
                              <label>Data do pedido:</label> {{ $solicitacao->created_at->format("d/m/Y H:i:s") }}<br>
                              <label>Tipo:</label> {{ $solicitacao->tipoLabel() }}<br>
                              <label>Ano de referência:</label> {{ $solicitacao->ano_referencia }}<br>
                              <label>Período:</label> {{ $solicitacao->data_inicio->format('d/m/Y') }} a {{ $solicitacao->data_fim->format('d/m/Y') }} ({{ $solicitacao->dias() }} dia(s))<br>
                              <label>Observação do colaborador:</label> {{ $solicitacao->observacao ?? '—' }}
                          </p>
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

      {{-- Férias já aprovadas — visão geral da linha do tempo --}}
      <div class="row">
        <div class="col-xs-12">
          <div class="box box-default">
            <div class="box-header with-border">
              <h3 class="box-title"><i class="fa fa-umbrella-beach"></i> Férias Aprovadas</h3>
            </div>
            <div class="box-body table-responsive" style="padding:0;">
              <table class="table table-bordered table-striped" style="margin-bottom:0;">
                <thead>
                  <tr>
                    <th>Colaborador</th>
                    <th>Tipo</th>
                    <th>Ano Ref.</th>
                    <th>Início</th>
                    <th>Fim</th>
                    <th>Dias</th>
                    <th>Situação</th>
                    <th width="8%">Ações</th>
                  </tr>
                </thead>
                <tbody>
                @forelse($aprovadas as $solicitacao)
                  <?php $tl = $timelines[$solicitacao->id]; ?>
                  <?php $ainda_agendada = in_array($tl['chave'], ['agendada', 'alerta_saida']); ?>
                  <tr>
                    <td>{{ utf8_decode($solicitacao->usuario->nome) }}</td>
                    <td>{{ $solicitacao->tipoLabel() }}</td>
                    <td>{{ $solicitacao->ano_referencia }}</td>
                    <td>{{ $solicitacao->data_inicio->format('d/m/Y') }}</td>
                    <td>{{ $solicitacao->data_fim->format('d/m/Y') }}</td>
                    <td>{{ $solicitacao->dias() }}</td>
                    <td>
                      <span class="badge bg-{{ $tl['cor'] }}">{{ $tl['rotulo'] }}</span>
                      @if(in_array($tl['chave'], ['alerta_saida', 'alerta_retorno']))
                        <small class="text-red">
                          @if($tl['chave'] === 'alerta_saida')
                            (sai em {{ $tl['dias'] }} dia{{ $tl['dias'] == 1 ? '' : 's' }})
                          @else
                            (retorna em {{ $tl['dias'] }} dia{{ $tl['dias'] == 1 ? '' : 's' }})
                          @endif
                        </small>
                      @endif
                    </td>
                    <td>
                      @if($ainda_agendada)
                        @if($admin_atual == 1 OR $rh_atual == 1 OR $gerente_atual == 1)
                        <a href="#modal-editar-ferias-{{ $solicitacao->id }}" data-toggle="modal" class="btn btn-acao btn-primary" title="Editar datas">
                            <i class="fa fa-pencil"></i>
                        </a>
                        @endif
                        @if($admin_atual == 1 OR $rh_atual == 1)
                        <a href="#" data-url="{{ $url_base }}/painel/ferias/admin/excluir/{{ $solicitacao->id }}"
                           data-msg="Excluir esta solicitação de férias agendada?"
                           class="btn btn-acao btn-danger btnExluir" title="Excluir">
                            <i class="fa fa-trash"></i>
                        </a>
                        @endif
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="8" class="text-center text-muted" style="padding:20px;">Nenhuma férias aprovada no momento.</td>
                  </tr>
                @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      {{-- Modais de edição de data — só pra quem ainda está agendada.
           Fora de qualquer <form>, mesmo motivo dos modais de aprovação. --}}
      @foreach($aprovadas as $solicitacao)
      <?php $tl = $timelines[$solicitacao->id]; ?>
      @if(in_array($tl['chave'], ['agendada', 'alerta_saida']))
      <div id="modal-editar-ferias-{{ $solicitacao->id }}" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
          <div class="modal-dialog modal-sm">
              <div class="modal-content">
                  <div class="modal-header text-center">
                      <h2 class="modal-title">Editar Datas</h2>
                  </div>
                  <form method="post" action="{{ $url_base }}/painel/ferias/editar">
                      {{ csrf_field() }}
                      <div class="modal-body">
                          <input type="hidden" name="ferias_id" value="{{ $solicitacao->id }}">
                          <p><strong>{{ utf8_decode($solicitacao->usuario->nome) }}</strong> <small class="text-muted">— {{ $solicitacao->tipoLabel() }}</small></p>
                          <div class="form-group">
                              <label>Data de início</label>
                              <input type="text" name="data_inicio" class="form-control datepicker" value="{{ $solicitacao->data_inicio->format('d/m/Y') }}" required>
                          </div>
                          <div class="form-group">
                              <label>Data de fim</label>
                              <input type="text" name="data_fim" class="form-control datepicker" value="{{ $solicitacao->data_fim->format('d/m/Y') }}" required>
                          </div>
                      </div>
                      <div class="modal-footer">
                          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Fechar</button>
                          <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Salvar</button>
                      </div>
                  </form>
              </div>
          </div>
      </div>
      @endif
      @endforeach

    </section>

@push('scripts')
<script>
$('#chk-todos').on('change', function(){
    $('.chk-solicitacao').prop('checked', this.checked);
});

$(document).on('change', '.chk-solicitacao', function(){
    if(!this.checked) $('#chk-todos').prop('checked', false);
});

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
