@extends('pontoeletronico.painel')

@push('styles')
<style>
/* ── Calendário de Feriados ── */
.cal-nav { display:flex; align-items:center; gap:10px; }
.cal-ano-badge {
    font-size: 16px;
    font-weight: 700;
    background: #f39c12;
    color: #fff;
    padding: 4px 14px;
    border-radius: 20px;
    letter-spacing: 1px;
}

.cal-grade {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    margin-top: 12px;
}
@media (max-width:1100px) { .cal-grade { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 768px) { .cal-grade { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 480px) { .cal-grade { grid-template-columns: 1fr; } }

.cal-mes {
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow: hidden;
    font-size: 11px;
}
.cal-mes-titulo {
    background: #444;
    color: #fff;
    text-align: center;
    font-size: 10px;
    font-weight: 700;
    padding: 5px 2px;
    letter-spacing: 1px;
    text-transform: uppercase;
}
.cal-dias-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
}
.cal-dh {
    background: #666;
    color: #fff;
    text-align: center;
    font-size: 8px;
    font-weight: 700;
    padding: 3px 0;
    border: 1px solid #555;
}
.cal-dc, .cal-dc-vazio {
    background: #fff;
    text-align: center;
    font-size: 10px;
    padding: 4px 1px;
    border: 1px solid #eee;
    min-height: 22px;
    line-height: 14px;
}
.cal-dc-vazio { background: #fafafa; }
.cal-dc-util  { cursor: pointer; }
.cal-dc-util:hover { background: #d6eaf8 !important; }
.cal-dc-dom   { background: #fff5f5; color: #c0392b; }
.cal-dc-sab   { background: #f5f5f5; color: #888; }
.cal-dc-fer   {
    background: #f39c12 !important;
    color: #fff !important;
    font-weight: 700;
    cursor: pointer;
    position: relative;
}
.cal-dc-fer.cal-dc-rec::after {
    content: '★';
    font-size: 6px;
    position: absolute;
    top: 1px;
    right: 2px;
    opacity: .8;
}
.cal-dc-fer:hover { background: #d68910 !important; }
</style>
@endpush

@section('conteudo')

<section class="content-header">
  <h1>Períodos de Fechamento <small>e Feriados</small></h1>
</section>

<section class="content">
  <div class="row">

    {{-- Formulário de cadastro de período --}}
    <div class="col-md-5">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title" id="form-titulo">Novo Período</h3>
        </div>
        <form method="POST" action="/painel/periodo/salvar" id="form-periodo">
          {{ csrf_field() }}
          <input type="hidden" name="id" id="periodo-id" value="">
          <div class="box-body">
            <div class="form-group">
              <label>Descrição <small class="text-muted">(ex: Junho/2026)</small></label>
              <input type="text" name="descricao" id="periodo-descricao" class="form-control" placeholder="Ex: Junho/2026" required>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Data de Início</label>
                  <input type="text" name="data_inicio" id="periodo-inicio" class="form-control datepicker" placeholder="dd/mm/aaaa" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Data de Fim</label>
                  <input type="text" name="data_fim" id="periodo-fim" class="form-control datepicker" placeholder="dd/mm/aaaa" required>
                </div>
              </div>
            </div>
          </div>
          <div class="box-footer">
            <button type="button" class="btn btn-default" id="btn-cancelar-edicao" style="display:none;">Cancelar</button>
            <button type="submit" class="btn btn-primary pull-right">Salvar Período</button>
          </div>
        </form>
      </div>
    </div>

    {{-- Lista de períodos --}}
    <div class="col-md-7">
      <div class="box box-default">
        <div class="box-header with-border">
          <h3 class="box-title">Períodos Cadastrados</h3>
        </div>
        <div class="box-body table-responsive no-padding">
          <table class="table table-hover table-striped">
            <thead>
              <tr>
                <th>Descrição</th>
                <th>Início</th>
                <th>Fim</th>
                <th>Status</th>
                <th width="340">Ações</th>
              </tr>
            </thead>
            <tbody>
            @forelse($periodos as $p)
              <tr class="{{ $p->ativo ? 'success' : '' }}">
                <td><strong>{{ $p->descricao }}</strong></td>
                <td>{{ $p->data_inicio->format('d/m/Y') }}</td>
                <td>{{ $p->data_fim->format('d/m/Y') }}</td>
                <td>
                  @if($p->ativo)
                    <span class="label label-success"><i class="fa fa-check"></i> Ativo</span>
                  @else
                    <span class="label label-default">Inativo</span>
                  @endif
                </td>
                <td style="white-space:nowrap;">
                  @if($p->ativo)
                  <a href="/painel/periodo/desativar/{{ $p->id }}"
                     class="btn btn-sm btn-warning"
                     onclick="return confirm('Desativar o período {{ $p->descricao }}?')">
                    <i class="fa fa-pause"></i> Desativar
                  </a>
                  @else
                  <a href="/painel/periodo/ativar/{{ $p->id }}"
                     class="btn btn-sm btn-success"
                     onclick="return confirm('Ativar o período {{ $p->descricao }}? Os demais serão desativados.')">
                    <i class="fa fa-play"></i> Ativar
                  </a>
                  @endif
                  <button type="button" class="btn btn-sm btn-default btn-editar"
                    data-id="{{ $p->id }}"
                    data-descricao="{{ $p->descricao }}"
                    data-inicio="{{ $p->data_inicio->format('d/m/Y') }}"
                    data-fim="{{ $p->data_fim->format('d/m/Y') }}">
                    <i class="fa fa-pencil"></i> Editar
                  </button>
                  <a href="#"
                     class="btn btn-sm btn-danger btnExluir"
                     data-url="/painel/periodo/excluir/{{ $p->id }}"
                     data-msg="Excluir o período {{ $p->descricao }}?">
                    <i class="fa fa-trash"></i> Excluir
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center text-muted">Nenhum período cadastrado.</td>
              </tr>
            @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>

  {{-- ══════════════════════════════════════════════════════
       CALENDÁRIO DE FERIADOS
  ══════════════════════════════════════════════════════ --}}
  <div class="row">
    <div class="col-md-12">
      <div class="box box-warning">
        <div class="box-header with-border">
          <h3 class="box-title"><i class="fa fa-calendar-o"></i> Calendário de Feriados</h3>
          <div class="box-tools pull-right">
            <div class="cal-nav">
              <a href="/painel/periodo?ano={{ $ano_cal - 1 }}" class="btn btn-xs btn-default">
                <i class="fa fa-chevron-left"></i> {{ $ano_cal - 1 }}
              </a>
              <span class="cal-ano-badge">{{ $ano_cal }}</span>
              <a href="/painel/periodo?ano={{ $ano_cal + 1 }}" class="btn btn-xs btn-default">
                {{ $ano_cal + 1 }} <i class="fa fa-chevron-right"></i>
              </a>
            </div>
          </div>
        </div>
        <div class="box-body">

          {{-- Grade dos 12 meses --}}
          @php
            $meses_nomes = ['','JANEIRO','FEVEREIRO','MARÇO','ABRIL','MAIO','JUNHO',
                            'JULHO','AGOSTO','SETEMBRO','OUTUBRO','NOVEMBRO','DEZEMBRO'];
            $dias_sem = ['D','S','T','Q','Q','S','S'];
          @endphp

          <div class="cal-grade">
          @for($mes = 1; $mes <= 12; $mes++)
            @php
              $dias_no_mes  = (int) date('t', mktime(0,0,0,$mes,1,$ano_cal));
              $primeiro_dow = (int) date('w', mktime(0,0,0,$mes,1,$ano_cal)); // 0=Dom
            @endphp
            <div class="cal-mes">
              <div class="cal-mes-titulo">{{ $meses_nomes[$mes] }}</div>
              <div class="cal-dias-grid">
                {{-- Cabeçalho dos dias da semana --}}
                @foreach($dias_sem as $ds)
                  <div class="cal-dh">{{ $ds }}</div>
                @endforeach
                {{-- Células vazias antes do 1º dia --}}
                @for($e = 0; $e < $primeiro_dow; $e++)
                  <div class="cal-dc-vazio"></div>
                @endfor
                {{-- Dias do mês --}}
                @for($d = 1; $d <= $dias_no_mes; $d++)
                  @php
                    $data_str  = sprintf('%04d-%02d-%02d', $ano_cal, $mes, $d);
                    $data_fmt  = sprintf('%02d/%02d/%04d', $d, $mes, $ano_cal);
                    $dow_d     = (int) date('w', mktime(0,0,0,$mes,$d,$ano_cal));
                    $is_fer    = isset($feriados_lookup[$data_str]);
                    $is_dom    = $dow_d === 0;
                    $is_sab    = $dow_d === 6;
                    $fer_info  = $is_fer ? $feriados_lookup[$data_str] : null;
                  @endphp
                  @if($is_fer)
                    <div class="cal-dc cal-dc-fer {{ $fer_info['rec'] ? 'cal-dc-rec' : '' }}"
                         title="{{ $fer_info['desc'] }}"
                         data-toggle="modal" data-target="#modal-feriado"
                         data-data="{{ $data_str }}"
                         data-data-fmt="{{ $data_fmt }}"
                         data-fid="{{ $fer_info['id'] }}"
                         data-desc="{{ $fer_info['desc'] }}"
                         data-rec="{{ $fer_info['rec'] ? 1 : 0 }}">{{ $d }}</div>
                  @elseif($is_dom)
                    <div class="cal-dc cal-dc-dom" title="{{ $data_fmt }}">{{ $d }}</div>
                  @elseif($is_sab)
                    <div class="cal-dc cal-dc-sab" title="{{ $data_fmt }}">{{ $d }}</div>
                  @else
                    <div class="cal-dc cal-dc-util"
                         title="Adicionar feriado: {{ $data_fmt }}"
                         data-toggle="modal" data-target="#modal-feriado"
                         data-data="{{ $data_str }}"
                         data-data-fmt="{{ $data_fmt }}"
                         data-fid=""
                         data-desc=""
                         data-rec="0">{{ $d }}</div>
                  @endif
                @endfor
              </div>{{-- /.cal-dias-grid --}}
            </div>{{-- /.cal-mes --}}
          @endfor
          </div>{{-- /.cal-grade --}}

          {{-- Legenda --}}
          <div style="margin-top:8px; display:flex; gap:16px; font-size:12px; flex-wrap:wrap;">
            <span><span style="display:inline-block;width:14px;height:14px;background:#f39c12;border-radius:2px;vertical-align:middle;margin-right:4px;"></span>Feriado cadastrado</span>
            <span><span style="display:inline-block;width:14px;height:14px;background:#f5f5f5;border:1px solid #ccc;border-radius:2px;vertical-align:middle;margin-right:4px;"></span>Sábado</span>
            <span><span style="display:inline-block;width:14px;height:14px;background:#fff5f5;border:1px solid #ccc;border-radius:2px;vertical-align:middle;margin-right:4px;"></span>Domingo</span>
            <span style="color:#888;"><i class="fa fa-info-circle"></i> Clique em qualquer dia útil para cadastrar feriado. ★ = recorrente projetado.</span>
          </div>

          {{-- Lista de feriados do ano --}}
          @if($feriados_lista->count() > 0)
          <div style="margin-top:20px;">
            <h4 style="margin-bottom:10px; color:#e67e22;"><i class="fa fa-list"></i> Feriados cadastrados em {{ $ano_cal }}</h4>
            <table class="table table-condensed table-bordered table-striped" style="max-width:600px;">
              <thead>
                <tr style="background:#f39c12; color:#fff;">
                  <th>Data</th>
                  <th>Feriado</th>
                  <th width="80">Recorrente</th>
                  <th width="60">Ação</th>
                </tr>
              </thead>
              <tbody>
              @foreach($feriados_lista as $f)
                <tr>
                  <td>{{ $f->data->format('d/m/Y') }}</td>
                  <td>{{ $f->descricao ?: '—' }}</td>
                  <td class="text-center">
                    @if($f->recorrente)
                      <span class="label label-warning"><i class="fa fa-repeat"></i> Sim</span>
                    @else
                      <span class="label label-default">Não</span>
                    @endif
                  </td>
                  <td class="text-center">
                    <a href="/painel/feriado/excluir/{{ $f->id }}"
                       class="btn btn-xs btn-danger btnExluir"
                       data-url="/painel/feriado/excluir/{{ $f->id }}"
                       data-msg="Remover o feriado {{ $f->data->format('d/m/Y') }} - {{ $f->descricao }}?">
                      <i class="fa fa-trash"></i>
                    </a>
                  </td>
                </tr>
              @endforeach
              </tbody>
            </table>
          </div>
          @else
          <p class="text-muted" style="margin-top:15px;"><i class="fa fa-info-circle"></i> Nenhum feriado cadastrado para {{ $ano_cal }}. Clique em qualquer dia útil no calendário para adicionar.</p>
          @endif

        </div>{{-- /.box-body --}}
      </div>{{-- /.box --}}
    </div>
  </div>
</section>

{{-- ══════════════════════════════════════════════════════
     MODAL — ADICIONAR / EDITAR FERIADO
══════════════════════════════════════════════════════ --}}
<div id="modal-feriado" class="modal fade" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-sm" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background:#f39c12; color:#fff; border-radius:3px 3px 0 0;">
        <button type="button" class="close" data-dismiss="modal" style="color:#fff; opacity:1;">&times;</button>
        <h4 class="modal-title" id="modal-feriado-titulo"><i class="fa fa-calendar"></i> Feriado</h4>
      </div>
      <form method="POST" action="/painel/feriado/salvar" id="form-feriado">
        {{ csrf_field() }}
        <input type="hidden" name="data" id="feriado-data-hidden">
        <div class="modal-body">
          <div class="form-group">
            <label>Data</label>
            <input type="text" id="feriado-data-display" class="form-control" readonly
                   style="background:#f9f9f9; cursor:default; font-weight:600;">
          </div>
          <div class="form-group">
            <label>Nome do Feriado</label>
            <input type="text" name="descricao" id="feriado-desc" class="form-control"
                   placeholder="Ex: Tiradentes" maxlength="150">
          </div>
          <div class="form-group">
            <label>
              <input type="checkbox" name="recorrente" value="1" id="feriado-recorrente">
              &nbsp;Repetir todo ano (recorrente)
            </label>
            <p class="help-block" style="font-size:11px; margin-top:2px;">
              Se marcado, este feriado aparecerá automaticamente na mesma data todos os anos.
            </p>
          </div>
        </div>
        <div class="modal-footer">
          <a href="#" id="btn-excluir-feriado"
             class="btn btn-danger pull-left btnExluir"
             style="display:none;"
             data-url=""
             data-msg="">
            <i class="fa fa-trash"></i> Remover
          </a>
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-warning" id="btn-salvar-feriado">
            <i class="fa fa-save"></i> Salvar Feriado
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

@push('scripts')
<script>
$(document).ready(function(){

    // ── Editar período ──
    $('.btn-editar').on('click', function(){
        var btn = $(this);
        $('#periodo-id').val(btn.data('id'));
        $('#periodo-descricao').val(btn.data('descricao'));
        $('#periodo-inicio').val(btn.data('inicio'));
        $('#periodo-fim').val(btn.data('fim'));
        $('#form-titulo').text('Editar Período');
        $('#btn-cancelar-edicao').show();
        $('html,body').animate({scrollTop:0}, 300);
    });

    $('#btn-cancelar-edicao').on('click', function(){
        $('#form-periodo')[0].reset();
        $('#periodo-id').val('');
        $('#form-titulo').text('Novo Período');
        $(this).hide();
    });

    // ── Seletor de período de fechamento no acompanhamento ──
    $('.btn-ver-periodo').on('click', function(e){
        e.preventDefault();
        sessionStorage.setItem('periodo_inicio', $(this).data('inicio'));
        sessionStorage.setItem('periodo_fim',    $(this).data('fim'));
        window.location.href = '/painel/acompanhamento';
    });

    // ── Modal de feriado ──
    $('#modal-feriado').on('show.bs.modal', function(e){
        var btn      = $(e.relatedTarget);
        var dataDb   = btn.data('data');      // yyyy-mm-dd
        var dataFmt  = btn.data('data-fmt');  // dd/mm/yyyy
        var fid      = btn.data('fid');
        var desc     = btn.data('desc') || '';
        var rec      = btn.data('rec')  || 0;

        // Remove o sufixo ★ que pode vir de recorrentes projetados
        desc = desc.replace(' ★', '').trim();

        $('#feriado-data-hidden').val(dataFmt);
        $('#feriado-data-display').val(dataFmt);
        $('#feriado-desc').val(desc);
        $('#feriado-recorrente').prop('checked', rec == 1);

        if (fid) {
            $('#modal-feriado-titulo').html('<i class="fa fa-calendar"></i> Editar Feriado');
            var urlExcluir = '/painel/feriado/excluir/' + fid;
            $('#btn-excluir-feriado')
                .attr('data-url', urlExcluir)
                .attr('data-msg', 'Remover o feriado ' + dataFmt + '?')
                .show();
            $('#btn-salvar-feriado').html('<i class="fa fa-save"></i> Atualizar');
        } else {
            $('#modal-feriado-titulo').html('<i class="fa fa-plus-circle"></i> Novo Feriado');
            $('#btn-excluir-feriado').hide();
            $('#btn-salvar-feriado').html('<i class="fa fa-save"></i> Salvar Feriado');
        }
    });

    // Limpar modal ao fechar
    $('#modal-feriado').on('hidden.bs.modal', function(){
        $('#feriado-desc').val('');
        $('#feriado-recorrente').prop('checked', false);
        $('#btn-excluir-feriado').hide();
    });
});
</script>
@endpush

@endsection
