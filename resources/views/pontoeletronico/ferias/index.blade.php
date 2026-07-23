@extends('pontoeletronico.painel')

@section('conteudo')
<section class="content-header">
  <h1>Férias</h1>
</section>

<section class="content">

  <div class="row">
    <div class="col-md-5">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title"><i class="fa fa-suitcase"></i> Solicitar Férias</h3>
        </div>
        <form method="POST" action="{{ getenv('APP_URL') }}/painel/ferias/solicitar">
          {{ csrf_field() }}
          <div class="box-body">
            <div class="form-group">
              <label>Tipo</label>
              <select name="tipo_ferias_id" class="form-control" required>
                @foreach(\App\TipoFerias::where('ativo', 1)->orderBy('ordem')->get() as $tipoFerias)
                  <option value="{{ $tipoFerias->id }}">{{ $tipoFerias->descricao }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group">
              <label>Ano de referência</label>
              <input type="number" name="ano_referencia" class="form-control" min="{{ date('Y') - 5 }}" max="{{ date('Y') + 1 }}" value="{{ date('Y') }}" required>
            </div>
            <div class="form-group">
              <label>Data de início</label>
              <input type="text" name="data_inicio" class="form-control datepicker" placeholder="dd/mm/aaaa" required>
            </div>
            <div class="form-group">
              <label>Data de fim</label>
              <input type="text" name="data_fim" class="form-control datepicker" placeholder="dd/mm/aaaa" required>
            </div>
            <div class="form-group">
              <label>Observação <small class="text-muted">(opcional)</small></label>
              <textarea name="observacao" class="form-control" rows="3"></textarea>
            </div>
          </div>
          <div class="box-footer">
            <button type="submit" class="btn btn-primary pull-right"><i class="fa fa-paper-plane"></i> Solicitar</button>
          </div>
        </form>
      </div>
    </div>

    <div class="col-md-7">
      <div class="box box-default">
        <div class="box-header with-border">
          <h3 class="box-title"><i class="fa fa-list"></i> Minhas Solicitações</h3>
        </div>
        <div class="box-body table-responsive" style="padding:0;">
          <table class="table table-bordered table-striped" style="margin-bottom:0;">
            <thead>
              <tr>
                <th>Tipo</th>
                <th>Ano Ref.</th>
                <th>Período</th>
                <th>Dias</th>
                <th>Status</th>
                <th>Situação</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @forelse($solicitacoes as $solicitacao)
                <tr>
                  <td>{{ $solicitacao->tipoLabel() }}</td>
                  <td>{{ $solicitacao->ano_referencia }}</td>
                  <td>{{ $solicitacao->data_inicio->format('d/m/Y') }} a {{ $solicitacao->data_fim->format('d/m/Y') }}</td>
                  <td>{{ $solicitacao->dias() }}</td>
                  <td>
                    @if($solicitacao->status == 0)
                      <span class="badge bg-yellow">Pendente</span>
                    @elseif($solicitacao->status == 3)
                      <span class="badge bg-aqua">Pré-aprovado</span>
                    @elseif($solicitacao->status == 1)
                      <span class="badge bg-green">Aprovado</span>
                    @else
                      <span class="badge bg-red">Não aprovado</span>
                    @endif
                  </td>
                  <td>
                    @if($solicitacao->status == 1 && isset($timelines[$solicitacao->id]))
                      <span class="badge bg-{{ $timelines[$solicitacao->id]['cor'] }}">{{ $timelines[$solicitacao->id]['rotulo'] }}</span>
                    @else
                      <span class="text-muted">—</span>
                    @endif
                  </td>
                  <td>
                    @if($solicitacao->status == 0)
                      <a href="#" data-url="{{ getenv('APP_URL') }}/painel/ferias/excluir/{{ $solicitacao->id }}"
                         data-msg="Excluir esta solicitação de férias?"
                         class="btn btn-acao btn-danger btnExluir" title="Excluir">
                          <i class="fa fa-trash"></i>
                      </a>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center text-muted" style="padding:20px;">Nenhuma solicitação de férias ainda.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

</section>
@endsection
