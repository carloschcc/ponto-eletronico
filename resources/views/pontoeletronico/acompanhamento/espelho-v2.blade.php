<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Espelho de Ponto</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      background: #d8dce4;
      display: flex;
      flex-direction: column;
      align-items: center;
      min-height: 100vh;
      padding: 20px 15px 40px;
      font-family: 'Times New Roman', Times, serif;
    }

    /* Barra de ações */
    .no-print {
      background: #1a1a1a;
      color: #fff;
      padding: 10px 18px;
      display: flex;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
      width: 100%;
      max-width: 1180px;
      margin-bottom: 22px;
      border-radius: 4px;
    }
    .btn-voltar {
      background: #444;
      color: #fff;
      padding: 6px 14px;
      border: none;
      border-radius: 3px;
      cursor: pointer;
      text-decoration: none;
      font-size: 13px;
      font-family: Arial, sans-serif;
    }
    .btn-voltar:hover { background: #333; }
    .btn-imprimir {
      background: #2e7d32;
      color: #fff;
      padding: 6px 14px;
      border: none;
      border-radius: 3px;
      cursor: pointer;
      font-size: 13px;
      font-family: Arial, sans-serif;
    }
    .btn-imprimir:hover { background: #256427; }
    .no-print .info-texto { font-size: 13px; font-family: Arial, sans-serif; }

    /* Card da folha */
    .folha-ponto {
      max-width: 1100px;
      width: 100%;
      background: #ffffff;
      box-shadow: 0 8px 25px rgba(0,0,0,.15);
      padding: 35px 40px 30px;
      border: 1px solid #000000;
      margin-bottom: 40px;
    }

    /* ===== CABEÇALHO COM BRASÃO ===== */
    .header-gov {
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 3px solid #000000;
      padding-bottom: 12px;
      margin-bottom: 16px;
    }
    .header-left {
      display: flex;
      align-items: center;
      gap: 18px;
    }
    .brasao {
      width: 65px;
      height: 65px;
      background: #000000;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #ffffff;
      font-size: 32px;
      font-weight: bold;
      border: 3px solid #000000;
      flex-shrink: 0;
      font-family: 'Georgia', serif;
    }
    .header-titles { display: flex; flex-direction: column; }
    .header-titles .estado {
      font-size: 28px;
      font-weight: 700;
      color: #000000;
      letter-spacing: 3px;
      line-height: 1.1;
    }
    .header-titles .governo {
      font-size: 18px;
      font-weight: 400;
      color: #000000;
      letter-spacing: 2px;
    }
    .header-right { text-align: right; }
    .header-right .titulo-folha {
      font-size: 22px;
      font-weight: 700;
      color: #000000;
      letter-spacing: 2px;
      text-transform: uppercase;
      border-bottom: 2px double #000000;
      padding-bottom: 4px;
    }
    .header-right .subtitulo {
      font-size: 13px;
      color: #000000;
      margin-top: 2px;
    }

    /* ===== IDENTIFICAÇÃO — TABELA CSS ===== */
    .identificacao {
      border: 1px solid #000000;
      margin-bottom: 22px;
      display: table;
      width: 100%;
      border-collapse: collapse;
    }
    .identificacao .linha { display: table-row; }
    .identificacao .celula {
      display: table-cell;
      border: 1px solid #000000;
      padding: 6px 12px;
      font-size: 13px;
      vertical-align: middle;
      min-height: 32px;
    }
    .identificacao .celula-rotulo {
      font-weight: 600;
      color: #000000;
      background: #f5f5f5;
      white-space: nowrap;
      width: 1%;
    }
    .identificacao .celula-valor {
      font-weight: 500;
      color: #000000;
      background: #ffffff;
    }

    /* ===== TABELA PRINCIPAL ===== */
    .tabela-wrapper { overflow-x: auto; margin-bottom: 18px; }
    .tabela-ponto {
      width: 100%;
      border-collapse: collapse;
      font-size: 12px;
      border: 1px solid #000000;
    }
    .tabela-ponto th {
      background: #000000;
      color: #ffffff;
      font-weight: 600;
      padding: 6px 3px;
      border: 1px solid #000000;
      text-align: center;
      font-size: 11px;
      letter-spacing: 0.3px;
    }
    .tabela-ponto th.periodo-header {
      background: #1a1a1a;
      font-size: 10px;
      padding: 5px 2px;
    }
    .tabela-ponto td {
      border: 1px solid #000000;
      padding: 4px 2px;
      text-align: center;
      vertical-align: middle;
      background-color: #ffffff;
      height: 28px;
    }
    .col-dia {
      font-weight: 600;
      background-color: #f5f5f5 !important;
      width: 36px;
      font-size: 13px;
      color: #000000;
    }
    .col-assinatura {
      background-color: #fafafa;
      min-width: 38px;
      font-size: 10px;
      color: #000000;
    }
    .col-horario {
      min-width: 38px;
      font-family: 'Courier New', monospace;
      font-weight: 600;
      font-size: 12px;
    }
    .col-chefe {
      min-width: 55px;
      background-color: #f5f5f5 !important;
    }
    .feriado, .sabado, .domingo {
      font-weight: 600;
      font-size: 11px;
      letter-spacing: 0.5px;
    }
    .feriado  { background-color: #e8e8e8 !important; color: #000000; }
    .sabado   { background-color: #eeeeee !important; color: #000000; }
    .domingo  { background-color: #e4e4e4 !important; color: #000000; }

    /* ===== OBSERVAÇÕES ===== */
    .obs-linha {
      border: 1px solid #000000;
      padding: 4px 10px;
      margin: 16px 0 12px;
      min-height: 32px;
      background: #ffffff;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .obs-linha .obs-label {
      font-weight: 600;
      color: #000000;
      font-size: 13px;
      white-space: nowrap;
    }
    .obs-linha .obs-conteudo {
      flex: 1;
      font-size: 12px;
      color: #000000;
      border-bottom: 1px solid #000000;
      min-height: 20px;
      padding: 0 4px;
    }

    /* ===== AVISO ===== */
    .aviso-entrega {
      font-size: 11px;
      color: #000000;
      text-align: right;
      border-bottom: 1px solid #000000;
      padding-bottom: 8px;
      margin-bottom: 18px;
      font-weight: bold;
    }

    /* ===== ASSINATURAS CENTRALIZADAS ===== */
    .rodape-assinaturas {
      display: flex;
      justify-content: center;
      gap: 120px;
      margin: 25px 0 10px;
      padding-top: 5px;
    }
    .assinatura-bloco {
      display: flex;
      flex-direction: column;
      align-items: center;
      min-width: 280px;
    }
    .linha-assinatura {
      border-bottom: 1.5px solid #000000;
      width: 100%;
      min-height: 35px;
      margin-bottom: 6px;
    }
    .assinatura-bloco .label {
      font-size: 13px;
      font-weight: 600;
      color: #000000;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      margin-bottom: 2px;
      text-align: center;
    }
    .assinatura-bloco .texto-aux {
      font-size: 11px;
      color: #000000;
      font-style: italic;
      text-align: center;
    }

    /* ===== RODAPÉ ===== */
    .footer-info {
      display: flex;
      justify-content: flex-end;
      margin-top: 10px;
      font-size: 10px;
      color: #000000;
      border-top: 1px solid #000000;
      padding-top: 8px;
    }

    /* ===== IMPRESSÃO ===== */
    @media print {
      .no-print { display: none !important; }
      body { background: white; padding: 0; display: block; }
      .folha-ponto {
        box-shadow: none;
        border: 1px solid #000;
        border-radius: 0;
        margin: 0;
        padding: 10mm 12mm;
        max-width: 100%;
        page-break-after: always;
      }
      .folha-ponto:last-child { page-break-after: auto; }
      @page { size: A4 landscape; margin: 0.8cm; }
    }

    /* ===== RESPONSIVO ===== */
    @media (max-width: 800px) {
      .folha-ponto { padding: 20px 15px; }
      .header-gov { flex-direction: column; align-items: flex-start; gap: 10px; }
      .header-right { text-align: left; width: 100%; }
      .header-right .titulo-folha { font-size: 18px; }
      .identificacao .celula { padding: 4px 8px; font-size: 11px; display: block; width: 100% !important; border-bottom: 1px solid #000; }
      .identificacao .celula:last-child { border-bottom: none; }
      .identificacao .linha { display: block; border-bottom: 1px solid #000; }
      .identificacao .linha:last-child { border-bottom: none; }
      .rodape-assinaturas { flex-direction: column; gap: 30px; align-items: center; }
      .assinatura-bloco { min-width: 200px; width: 100%; max-width: 350px; }
      .tabela-ponto { font-size: 10px; }
      .tabela-ponto th { font-size: 9px; padding: 4px 1px; }
      .tabela-ponto td { padding: 2px 1px; height: 24px; }
      .col-dia { width: 28px; font-size: 11px; }
      .col-assinatura, .col-horario { min-width: 28px; font-size: 9px; }
    }
    @media (max-width: 500px) {
      .tabela-ponto { font-size: 8px; }
      .tabela-ponto th { font-size: 7px; padding: 3px 1px; }
      .header-titles .estado { font-size: 20px; }
      .header-titles .governo { font-size: 14px; }
      .brasao { width: 48px; height: 48px; font-size: 22px; }
      .assinatura-bloco { min-width: 150px; }
    }
  </style>
</head>
<body>

<div class="no-print">
  <a href="/painel/acompanhamento" class="btn-voltar">&larr; Voltar</a>
  <button onclick="window.print()" class="btn-imprimir">&#9113; Imprimir / Salvar PDF</button>
  <span class="info-texto">
    Espelho de Ponto &mdash;
    @php
      $arr_i = explode('-', $data_inicio);
      $arr_f = explode('-', $data_fim);
      echo $arr_i[2].'/'.$arr_i[1].'/'.$arr_i[0].' a '.$arr_f[2].'/'.$arr_f[1].'/'.$arr_f[0];
    @endphp
    @if(!empty($todos) && $todos) &mdash; Todos os Colaboradores @endif
    &mdash; {{ count($data) }} colaborador(es)
  </span>
</div>

@forelse($data as $nome => $registros)
@php
  $usuario_reg   = $registros[0]->usuario ?? null;
  $local_unidade = $usuario_reg ? ($usuario_reg->local      ?? $app_name) : $app_name;
  $cargo_func    = $usuario_reg ? ($usuario_reg->cargo     ?? '')        : '';
  $matricula     = $usuario_reg ? ($usuario_reg->matricula ?? '')        : '';

  // Primeira letra da organização (fallback do brasão)
  $brasao_letra       = mb_strtoupper(mb_substr(trim($app_name), 0, 1));
  $logo_espelho_path  = public_path('img/logo_espelho_v2.png');
  $logo_espelho_existe = file_exists($logo_espelho_path);
  $logo_espelho_url   = getenv('APP_URL') . '/img/logo_espelho_v2.png';

  // Agrupar registros por data
  $regs_por_dia = [];
  foreach ($registros as $r) {
      $regs_por_dia[$r->data][] = $r;
  }

  // Intervalo de datas
  $dt_ini = new DateTime($data_inicio);
  $dt_fim = new DateTime($data_fim);

  // Label do mês/período
  $meses_pt = [
    1=>'JANEIRO', 2=>'FEVEREIRO', 3=>'MARÇO',    4=>'ABRIL',
    5=>'MAIO',    6=>'JUNHO',     7=>'JULHO',     8=>'AGOSTO',
    9=>'SETEMBRO',10=>'OUTUBRO', 11=>'NOVEMBRO', 12=>'DEZEMBRO',
  ];
  if ($dt_ini->format('Y-m') === $dt_fim->format('Y-m')) {
      $mes_label = $meses_pt[(int)$dt_ini->format('m')] . ' ' . $dt_ini->format('Y');
  } else {
      $mes_label = $dt_ini->format('d/m/Y') . ' a ' . $dt_fim->format('d/m/Y');
  }

  // Lista de todos os dias do período
  $dias = [];
  $cursor = clone $dt_ini;
  while ($cursor <= $dt_fim) {
      $dias[] = $cursor->format('Y-m-d');
      $cursor->modify('+1 day');
  }

  // Observações: em branco — espaço de uso livre (caneta) por quem recebe a folha impressa.
  $obs_texto = '';
@endphp

<div class="folha-ponto">

  {{-- CABEÇALHO --}}
  <div class="header-gov">
    <div class="header-left">
      @if($logo_espelho_existe)
        <img src="{{ $logo_espelho_url }}?t={{ time() }}" alt="Logo"
             style="max-height:70px; max-width:200px; object-fit:contain;">
      @else
        <div class="brasao">{{ $brasao_letra }}</div>
      @endif
    </div>
    <div class="header-right">
      <div class="titulo-folha">FOLHA DE FREQUÊNCIA</div>
      <div class="subtitulo">Controle de Ponto · Servidor</div>
    </div>
  </div>

  {{-- IDENTIFICAÇÃO --}}
  <div class="identificacao">
    {{-- Linha 1: Unidade + Mês --}}
    <div class="linha">
      <div class="celula celula-rotulo" style="width:17%;">UNIDADE ADMINISTRATIVA:</div>
      <div class="celula celula-valor"  style="width:53%;">{{ strtoupper($local_unidade) }}</div>
      <div class="celula celula-rotulo" style="width:10%;">MÊS:</div>
      <div class="celula celula-valor"  style="width:20%;">{{ $mes_label }}</div>
    </div>
    {{-- Linha 2: Nome + Matrícula --}}
    <div class="linha">
      <div class="celula celula-rotulo" style="width:10%;">NOME:</div>
      <div class="celula celula-valor"  style="width:60%;">{{ strtoupper($nome) }}</div>
      <div class="celula celula-rotulo" style="width:10%;">MATRÍCULA:</div>
      <div class="celula celula-valor"  style="width:20%;">{{ $matricula }}</div>
    </div>
    {{-- Linha 3: Cargo (span total) --}}
    <div class="linha">
      <div class="celula celula-rotulo" style="width:17%;">CARGO/FUNÇÃO:</div>
      <div class="celula celula-valor">{{ strtoupper($cargo_func) }}</div>
    </div>
  </div>

  {{-- TABELA PRINCIPAL --}}
  <div class="tabela-wrapper">
    <table class="tabela-ponto">
      <thead>
        <tr>
          <th rowspan="2" style="width:44px; background:#000000;">Dia</th>
          <th colspan="4" class="periodo-header" style="background:#1a1a1a;">Matutino</th>
          <th colspan="4" class="periodo-header" style="background:#1a1a1a;">Vespertino</th>
          <th rowspan="2" style="background:#000000; min-width:68px;">Chefe<br>Imediato</th>
        </tr>
        <tr>
          <th style="background:#1a1a1a;">Assinatura</th>
          <th style="background:#1a1a1a;">Entrada</th>
          <th style="background:#1a1a1a;">Assinatura</th>
          <th style="background:#1a1a1a;">Saída</th>
          <th style="background:#1a1a1a;">Assinatura</th>
          <th style="background:#1a1a1a;">Entrada</th>
          <th style="background:#1a1a1a;">Assinatura</th>
          <th style="background:#1a1a1a;">Saída</th>
        </tr>
      </thead>
      <tbody>
      @foreach($dias as $dia)
      @php
        $dow     = (int) date('N', strtotime($dia)); // 1=Seg…6=Sáb…7=Dom
        $dia_num = (int) date('d', strtotime($dia));
        $regs_dia = $regs_por_dia[$dia] ?? [];

        // Primeiro registro = Matutino, segundo = Vespertino
        $mat = $regs_dia[0] ?? null;
        $ves = $regs_dia[1] ?? null;

        $mat_e = ($mat && $mat->entrada) ? substr($mat->entrada, 0, 5) : '';
        $mat_s = ($mat && $mat->saida)   ? substr($mat->saida,   0, 5) : '';
        $ves_e = ($ves && $ves->entrada) ? substr($ves->entrada, 0, 5) : '';
        $ves_s = ($ves && $ves->saida)   ? substr($ves->saida,   0, 5) : '';

        $is_feriado   = isset($feriados_set[$dia]);
        $feriado_nome = $is_feriado ? strtoupper($feriados_set[$dia] ?: 'FERIADO') : '';
      @endphp
      @if($is_feriado)
        <tr>
          <td class="col-dia">{{ $dia_num }}</td>
          <td class="feriado" colspan="4">{{ $feriado_nome }}</td>
          <td class="feriado" colspan="4">{{ $feriado_nome }}</td>
          <td class="feriado">{{ $feriado_nome }}</td>
        </tr>
      @elseif($dow === 6)
        <tr>
          <td class="col-dia">{{ $dia_num }}</td>
          <td class="sabado" colspan="4">SÁBADO</td>
          <td class="sabado" colspan="4">SÁBADO</td>
          <td class="sabado">SÁBADO</td>
        </tr>
      @elseif($dow === 7)
        <tr>
          <td class="col-dia">{{ $dia_num }}</td>
          <td class="domingo" colspan="4">DOMINGO</td>
          <td class="domingo" colspan="4">DOMINGO</td>
          <td class="domingo">DOMINGO</td>
        </tr>
      @else
        <tr>
          <td class="col-dia">{{ $dia_num }}</td>
          <td class="col-assinatura"></td>
          <td class="col-horario">{{ $mat_e }}</td>
          <td class="col-assinatura"></td>
          <td class="col-horario">{{ $mat_s }}</td>
          <td class="col-assinatura"></td>
          <td class="col-horario">{{ $ves_e }}</td>
          <td class="col-assinatura"></td>
          <td class="col-horario">{{ $ves_s }}</td>
          <td class="col-chefe"></td>
        </tr>
      @endif
      @endforeach
      </tbody>
    </table>
  </div>

  {{-- OBSERVAÇÕES --}}
  <div class="obs-linha">
    <span class="obs-label">Observações:</span>
    <span class="obs-conteudo">{{ $obs_texto }}</span>
  </div>

  {{-- AVISO --}}
  <div class="aviso-entrega">
    Este documento deverá ser entregue sem rasuras ou corretivo, até o 5º dia útil do mês subsequente.
  </div>

  {{-- ASSINATURAS --}}
  <div class="rodape-assinaturas">
    <div class="assinatura-bloco">
      <div class="linha-assinatura"></div>
      <div class="label">ASSINATURA SERVIDOR</div>
      <div class="texto-aux">(carimbo e assinatura)</div>
    </div>
    <div class="assinatura-bloco">
      <div class="linha-assinatura"></div>
      <div class="label">ASSINATURA CHEFE IMEDIATO</div>
      <div class="texto-aux">(carimbo e assinatura)</div>
    </div>
  </div>

  {{-- RODAPÉ --}}
  <div class="footer-info">
    <span>Espelho de ponto &middot; Gerado em {{ date('d/m/Y') }}</span>
  </div>

</div>{{-- /.folha-ponto --}}

@empty
<div style="background:#fff; max-width:900px; width:100%; padding:40px; text-align:center; border: 1px solid #000;">
  <h3>Nenhum registro encontrado para este período.</h3>
</div>
@endforelse

</body>
</html>
