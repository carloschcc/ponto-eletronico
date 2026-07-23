@extends('pontoeletronico.painel')

@section('conteudo')
<section class="content-header">
  <h1>Console SQL</h1>
</section>

<section class="content">

  <div class="row">
    <div class="col-md-12">
      <div class="alert alert-warning" style="border-left: 4px solid #f39c12;">
        <i class="fa fa-exclamation-triangle"></i>
        <strong>Acesso direto ao banco de produção.</strong>
        Comandos INSERT/UPDATE/DELETE alteram os dados imediatamente e não podem ser desfeitos automaticamente.
        Apenas um comando SELECT, INSERT, UPDATE ou DELETE por execução — sem ";" no meio do texto.
      </div>
    </div>
  </div>

  @if($erro)
  <div class="row">
    <div class="col-md-12">
      <div class="alert alert-danger"><i class="fa fa-times-circle"></i> {{ $erro }}</div>
    </div>
  </div>
  @endif

  @if($resultado)
    @if($resultado['tipo'] === 'select')
      <div class="row">
        <div class="col-md-12">
          <div class="alert alert-success">
            <i class="fa fa-check-circle"></i>
            {{ $resultado['total'] }} linha(s) retornada(s) em {{ $resultado['tempo_ms'] }} ms.
            @if($resultado['truncado'])
              Exibindo as primeiras {{ count($resultado['linhas']) }}.
            @endif
          </div>
        </div>
      </div>
    @else
      <div class="row">
        <div class="col-md-12">
          <div class="alert alert-success">
            <i class="fa fa-check-circle"></i>
            {{ $resultado['afetadas'] }} linha(s) afetada(s) pelo comando {{ strtoupper($resultado['tipo']) }}.
          </div>
        </div>
      </div>
    @endif
  @endif

  <div class="row">

    <div class="col-md-3">
      <div class="box box-default">
        <div class="box-header with-border">
          <h3 class="box-title"><i class="fa fa-table"></i> Tabelas</h3>
        </div>
        <div class="box-body" style="max-height:500px; overflow-y:auto; padding:0;">
          <ul class="list-group" style="margin-bottom:0;">
            @forelse($tabelas as $tabela)
              <li class="list-group-item" style="cursor:pointer; padding:8px 12px; font-size:12px;" onclick="preencherSelect('{{ $tabela }}')">
                <i class="fa fa-table text-muted"></i> {{ $tabela }}
              </li>
            @empty
              <li class="list-group-item text-muted" style="font-size:12px;">Nenhuma tabela encontrada.</li>
            @endforelse
          </ul>
        </div>
      </div>
    </div>

    <div class="col-md-9">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title"><i class="fa fa-terminal"></i> Comando SQL</h3>
        </div>
        <form method="POST" action="{{ getenv('APP_URL') }}/painel/sql-console/executar" id="formSqlConsole">
          {{ csrf_field() }}
          <div class="box-body">
            <div class="form-group">
              <textarea name="sql" id="sqlTextarea" class="form-control" rows="8" style="font-family: monospace; font-size:13px;" placeholder="SELECT * FROM usuario LIMIT 100" required>{{ $sql }}</textarea>
            </div>
          </div>
          <div class="box-footer">
            <button type="submit" class="btn btn-primary btn-sm pull-right"><i class="fa fa-play"></i> Executar</button>
          </div>
        </form>
      </div>

      @if($resultado && $resultado['tipo'] === 'select' && count($resultado['colunas']) > 0)
      <div class="box box-default">
        <div class="box-header with-border">
          <h3 class="box-title"><i class="fa fa-list"></i> Resultado</h3>
        </div>
        <div class="box-body" style="overflow-x:auto; padding:0;">
          <table class="table table-bordered table-striped table-condensed" style="margin-bottom:0; white-space:nowrap;">
            <thead>
              <tr>
                @foreach($resultado['colunas'] as $coluna)
                  <th>{{ $coluna }}</th>
                @endforeach
              </tr>
            </thead>
            <tbody>
              @foreach($resultado['linhas'] as $linha)
                <tr>
                  @foreach($resultado['colunas'] as $coluna)
                    <td>{{ $linha[$coluna] === null ? 'NULL' : $linha[$coluna] }}</td>
                  @endforeach
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
      @endif

    </div>

  </div>

</section>
@endsection

@push('scripts')
<script>
function preencherSelect(tabela) {
    document.getElementById('sqlTextarea').value = 'SELECT * FROM ' + tabela + ' LIMIT 100';
}

document.getElementById('formSqlConsole').addEventListener('submit', function(e) {
    var sql = document.getElementById('sqlTextarea').value.trim();
    var comando = sql.split(/\s+/)[0].toUpperCase();

    if ((comando === 'UPDATE' || comando === 'DELETE') && !/\bWHERE\b/i.test(sql)) {
        e.preventDefault();
        swal({
            title: 'Comando sem cláusula WHERE',
            text: 'Este comando ' + comando + ' vai afetar TODAS as linhas da tabela. Deseja continuar mesmo assim?',
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#DD6B55',
            confirmButtonText: 'Sim, continuar',
            cancelButtonText: 'Cancelar',
            closeOnConfirm: true
        }, function(isConfirm) {
            if (isConfirm) {
                document.getElementById('formSqlConsole').submit();
            }
        });
    }
});
</script>
@endpush
