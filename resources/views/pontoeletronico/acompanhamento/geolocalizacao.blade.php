<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Relatório de Geolocalização</title>
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

        .legenda { font-size: 10px; color: #555; margin-top: 8px; }
        .legenda span { margin-right: 15px; }

        @media print {
            .no-print { display: none !important; }
            .no-print-col { display: none !important; }
            body { background: #fff; font-size: 10px; }

            .employee-section {
                box-shadow: none;
                margin: 0;
                padding: 8px 10px;
                max-width: 100%;
                page-break-after: always;
                page-break-inside: avoid;
            }
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
            Relatório de Geolocalização — Todos os Colaboradores &mdash;
        @else
            Relatório de Geolocalização &mdash;
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
$rows = [];
foreach ($registros as $reg) {
    $arr = explode('-', $reg->data);
    $data_fmt = $arr[2].'/'.$arr[1].'/'.$arr[0];

    if (!empty($reg->entrada)) {
        $rows[] = [
            'data'      => $data_fmt,
            'tipo'      => 'Entrada',
            'hora'      => substr($reg->entrada, 0, 5),
            'ip'        => $reg->entrada_ip ?? '—',
            'latitude'  => $reg->entrada_latitude ?? '—',
            'longitude' => $reg->entrada_longitude ?? '—',
            'fonte'     => ($reg->entrada_geo_fonte ?? null) === 'gps' ? 'GPS' : (!empty($reg->entrada_latitude) ? 'IP' : '—'),
            'mapa_url'  => (!empty($reg->entrada_latitude) && !empty($reg->entrada_longitude))
                ? 'https://www.openstreetmap.org/?mlat=' . $reg->entrada_latitude . '&mlon=' . $reg->entrada_longitude . '#map=16/' . $reg->entrada_latitude . '/' . $reg->entrada_longitude
                : null,
        ];
    }

    if (!empty($reg->saida)) {
        $rows[] = [
            'data'      => $data_fmt,
            'tipo'      => 'Saída',
            'hora'      => substr($reg->saida, 0, 5),
            'ip'        => $reg->saida_ip ?? '—',
            'latitude'  => $reg->saida_latitude ?? '—',
            'longitude' => $reg->saida_longitude ?? '—',
            'fonte'     => ($reg->saida_geo_fonte ?? null) === 'gps' ? 'GPS' : (!empty($reg->saida_latitude) ? 'IP' : '—'),
            'mapa_url'  => (!empty($reg->saida_latitude) && !empty($reg->saida_longitude))
                ? 'https://www.openstreetmap.org/?mlat=' . $reg->saida_latitude . '&mlon=' . $reg->saida_longitude . '#map=16/' . $reg->saida_latitude . '/' . $reg->saida_longitude
                : null,
        ];
    }
}
?>

<?php
$usuario_reg   = $registros[0]->usuario ?? null;
$local_empresa = $usuario_reg ? ($usuario_reg->local ?? App\Configuracao::valor('NOME_SISTEMA', 'Ponto Eletrônico')) : App\Configuracao::valor('NOME_SISTEMA', 'Ponto Eletrônico');
$cargo_func    = $usuario_reg ? ($usuario_reg->cargo ?? '') : '';
?>
<div class="employee-section">

    <div class="folha-header">
        <h1>Relatório de Geolocalização</h1>
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
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="9%">Data</th>
                <th width="7%">Tipo</th>
                <th width="6%">Hora</th>
                <th width="14%">IP de Registro</th>
                <th width="17%">Latitude</th>
                <th width="17%">Longitude</th>
                <th width="8%">Fonte</th>
                <th width="22%" class="no-print-col">Mapa</th>
            </tr>
        </thead>
        <tbody>
        @foreach($rows as $row)
            <tr>
                <td class="col-data">{{ $row['data'] }}</td>
                <td class="col-center">{{ $row['tipo'] }}</td>
                <td class="col-center">{{ $row['hora'] }}</td>
                <td class="col-center">{{ $row['ip'] }}</td>
                <td class="col-center">{{ $row['latitude'] }}</td>
                <td class="col-center">{{ $row['longitude'] }}</td>
                <td class="col-center">{{ $row['fonte'] }}</td>
                <td class="col-center no-print-col">
                    @if($row['mapa_url'])
                        <a href="{{ $row['mapa_url'] }}" target="_blank">Ver no mapa</a>
                    @else
                        —
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="legenda">
        <span><strong>Legenda:</strong></span>
        <span>— = sem informação disponível</span>
        <span>Fonte GPS = coordenada real do dispositivo (alta precisão)</span>
        <span>Fonte IP = estimativa pelo provedor de internet (precisão de cidade/região)</span>
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
