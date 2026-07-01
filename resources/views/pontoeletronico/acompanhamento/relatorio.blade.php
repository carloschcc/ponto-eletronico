<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Folha de Ponto</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #000; background: #f5f5f5; }

        .no-print {
            background: #333;
            color: #fff;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .no-print a, .no-print button {
            padding: 7px 16px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 13px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-voltar { background: #888; color: #fff; }
        .btn-voltar:hover { background: #666; }
        .btn-imprimir { background: #3c8dbc; color: #fff; }
        .btn-imprimir:hover { background: #337ab7; }
        .no-print span { font-size: 14px; font-weight: bold; color: #fff; }

        /* cada colaborador é um bloco independente (= 1 página A4) */
        .employee-section {
            background: #fff;
            max-width: 1100px;
            margin: 20px auto;
            padding: 25px 30px;
            box-shadow: 0 0 8px rgba(0,0,0,.15);
        }

        .folha-header { text-align: center; margin-bottom: 12px; }
        .folha-header h1 { font-size: 20px; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 6px; }

        .folha-meta {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            border: 1px solid #bbb;
            margin-bottom: 12px;
            background: #fafafa;
        }
        .folha-meta-cell {
            padding: 7px 12px;
            border-right: 1px solid #bbb;
            font-size: 12px;
        }
        .folha-meta-cell:last-child { border-right: none; }
        .folha-meta-cell p { margin: 2px 0; }
        .folha-meta-cell strong { font-size: 11px; color: #555; font-weight: normal; text-transform: uppercase; display: block; margin-bottom: 1px; }
        .folha-meta-cell span { font-size: 13px; font-weight: bold; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th {
            background: #e0e0e0;
            border: 1px solid #999;
            padding: 6px 4px;
            text-align: center;
            font-size: 10px;
            text-transform: uppercase;
        }
        td {
            border: 1px solid #aaa;
            padding: 5px 4px;
            font-size: 11px;
            vertical-align: middle;
        }
        td.col-data { font-weight: bold; text-align: left; padding-left: 6px; }
        td.col-center { text-align: center; }
        tr.total-geral td { background: #ddeeff; font-weight: bold; font-size: 11px; }

        /* Bloco de assinatura único no rodapé */
        .assinatura-rodape {
            display: flex;
            gap: 30px;
            margin-top: 18px;
            padding-top: 10px;
        }
        .assinatura-bloco {
            flex: 1;
            border-top: 1px solid #333;
            padding-top: 6px;
            font-size: 11px;
            text-align: center;
        }
        .assinatura-bloco .assinatura-linha {
            height: 36px;
            border-bottom: 1px solid #555;
            margin-bottom: 4px;
        }

        .legenda { font-size: 10px; color: #555; margin-top: 8px; }
        .legenda span { margin-right: 15px; }

        @media print {
            .no-print { display: none !important; }
            body { background: #fff; font-size: 10px; }

            /* cada .employee-section = exatamente 1 página A4 */
            .employee-section {
                box-shadow: none;
                margin: 0;
                padding: 8px 10px;
                max-width: 100%;
                page-break-after: always;
                page-break-inside: avoid;
            }
            /* último colaborador não força folha em branco extra */
            .employee-section:last-child {
                page-break-after: auto;
            }

            @page { size: A4 landscape; margin: 1cm; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <a href="/painel/acompanhamento" class="btn-voltar">&larr; Voltar</a>
    <button onclick="window.print()" class="btn-imprimir">&#9113; Imprimir / Salvar PDF</button>
    <span>
        @if(!empty($todos) && $todos)
            Espelho de Ponto — Todos os Colaboradores &mdash;
        @else
            Folha de Ponto &mdash;
        @endif
        {{ $data_inicio ?? date('d/m/Y') }} a {{ $data_fim ?? date('d/m/Y') }}
        @if(isset($data) && count($data) > 0)
            &mdash; {{ count($data) }} colaborador(es)
        @endif
    </span>
</div>

@if(isset($data) && count($data) > 0)
@foreach($data as $nome => $registros)
<?php
$registro_dia        = '';
$conta_registro      = 0;
$total_registro      = count($registros);
$horas_total_min     = 0;
$horas_dia_min       = 0;
$ultima_hora_saida   = '';
$rows = [];

foreach ($registros as $reg) {
    $varData = '';
    if ($registro_dia != $reg->data) {
        $varData = $reg->data;
        $arr = explode("-", $varData);
        $varData = $arr[2].'/'.$arr[1].'/'.$arr[0];
        $ultima_hora_saida = '';
        if ($registro_dia != '') {
            $horas_total_min += $horas_dia_min;
        }
        $horas_dia_min = 0;
    }

    $horas_trabalhadas = '';
    if (!empty($reg->entrada) && !empty($reg->saida)) {
        $dt_e = new DateTime($reg->entrada);
        $dt_s = new DateTime($reg->saida);
        $diff = $dt_s->diff($dt_e);
        $h = str_pad($diff->h, 2, '0', STR_PAD_LEFT);
        $m = str_pad($diff->i, 2, '0', STR_PAD_LEFT);
        $horas_trabalhadas = "$h:$m";
        $horas_dia_min += $diff->h * 60 + $diff->i;
    }

    $intervalo_pausa = '';
    if (!empty($ultima_hora_saida) && !empty($reg->entrada)) {
        $dt_a = new DateTime($ultima_hora_saida);
        $dt_b = new DateTime($reg->entrada);
        $diff2 = $dt_b->diff($dt_a);
        $h2 = str_pad($diff2->h, 2, '0', STR_PAD_LEFT);
        $m2 = str_pad($diff2->i, 2, '0', STR_PAD_LEFT);
        $intervalo_pausa = "$h2:$m2";
    }
    $ultima_hora_saida = $reg->saida;

    $justificativa_texto = '';
    $ajustes_reg = App\PontoAjuste::with('pontoRazao')
        ->where('ponto_id', $reg->id)
        ->whereIn('status', [0, 1, 2])
        ->orderBy('created_at', 'ASC')
        ->get();
    foreach ($ajustes_reg as $aj) {
        if ($aj->pontoRazao) {
            $justificativa_texto .= $aj->pontoRazao->descricao;
        }
        if (!empty($aj->obs_supervisor)) {
            $justificativa_texto .= ' — ' . $aj->obs_supervisor;
        }
        $justificativa_texto .= ' ';
    }

    $entrada_fmt = !empty($reg->entrada) ? substr($reg->entrada, 0, 5) : '—';
    $saida_fmt   = !empty($reg->saida)   ? substr($reg->saida,   0, 5) : '—';

    $registro_dia = $reg->data;
    $conta_registro++;

    if ($conta_registro == $total_registro) {
        $horas_total_min += $horas_dia_min;
    }

    $rows[] = [
        'data'        => $varData,
        'entrada'     => $entrada_fmt,
        'saida'       => $saida_fmt,
        'horas'       => $horas_trabalhadas,
        'intervalo'   => $intervalo_pausa,
        'just'        => trim($justificativa_texto),
        'is_last_day' => false,
        'dia_min'     => $horas_dia_min,
    ];
}

$total_h = str_pad((int)($horas_total_min / 60), 2, '0', STR_PAD_LEFT);
$total_m = str_pad($horas_total_min % 60,          2, '0', STR_PAD_LEFT);
?>

<?php
$usuario_reg   = $registros[0]->usuario ?? null;
$local_empresa = $usuario_reg ? ($usuario_reg->local ?? getenv('APP_NAME')) : getenv('APP_NAME');
$cargo_func    = $usuario_reg ? ($usuario_reg->cargo ?? '') : '';
?>
<div class="employee-section">

    <div class="folha-header">
        <h1>Folha de Ponto</h1>
    </div>

    <div class="folha-meta">
        <div class="folha-meta-cell">
            <strong>Funcionário</strong>
            <span>{{ strtoupper(utf8_decode($nome)) }}</span>
        </div>
        <div class="folha-meta-cell">
            <strong>Empresa / Local</strong>
            <span>{{ strtoupper($local_empresa) }}</span>
            @if($cargo_func)
            <p style="margin-top:3px;font-size:11px;"><strong>Cargo / Função:</strong> {{ $cargo_func }}</p>
            @endif
        </div>
        <div class="folha-meta-cell" style="text-align:right;">
            <strong>Período</strong>
            <span>{{ $data_inicio ?? date('d/m/Y') }} &nbsp;a&nbsp; {{ $data_fim ?? date('d/m/Y') }}</span>
            <p style="margin-top:3px;font-size:11px;"><strong>Total de horas:</strong> <?= $total_h ?>:<?= $total_m ?></p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="11%">Data</th>
                <th width="9%">Entrada</th>
                <th width="9%">Saída</th>
                <th width="10%">Horas Trabalhadas</th>
                <th width="9%">Intervalo</th>
                <th width="52%">Justificativa</th>
            </tr>
        </thead>
        <tbody>
        @foreach($rows as $row)
            <tr>
                <td class="col-data">{{ $row['data'] }}</td>
                <td class="col-center">{{ $row['entrada'] }}</td>
                <td class="col-center">{{ $row['saida'] }}</td>
                <td class="col-center">{{ $row['horas'] }}</td>
                <td class="col-center">{{ $row['intervalo'] }}</td>
                <td>{{ $row['just'] }}</td>
            </tr>
        @endforeach

        <tr class="total-geral">
            <td colspan="3" align="right" style="padding-right:8px;">Total trabalhado no período:</td>
            <td class="col-center"><?= $total_h ?>:<?= $total_m ?></td>
            <td colspan="2"></td>
        </tr>
        </tbody>
    </table>

    <div class="legenda">
        <span><strong>Legenda:</strong></span>
        <span>— = sem registro</span>
    </div>

    {{-- Bloco de assinatura único no rodapé da folha --}}
    <div class="assinatura-rodape">
        <div class="assinatura-bloco">
            <div class="assinatura-linha"></div>
            <div>Assinatura do Funcionário</div>
            <div style="font-size:10px; color:#555; margin-top:2px;">{{ strtoupper(utf8_decode($nome)) }}</div>
        </div>
        <div class="assinatura-bloco">
            <div class="assinatura-linha"></div>
            <div>Visto Supervisor / Gerente</div>
        </div>
    </div>

</div>
@endforeach
@else
    <div class="employee-section" style="text-align:center; padding:40px 0;">
        <h3>Nenhum registro encontrado para este período.</h3>
        <p style="margin-top:10px; color:#888;">Período: {{ $data_inicio ?? '' }} a {{ $data_fim ?? '' }}</p>
    </div>
@endif

</body>
</html>