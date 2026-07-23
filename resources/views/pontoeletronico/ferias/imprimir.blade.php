<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Colaboradores em Férias</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #000; background: #f5f5f5; }

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

        .folha {
            background: #fff;
            max-width: 1000px;
            margin: 20px auto;
            padding: 25px 30px;
            box-shadow: 0 0 8px rgba(0,0,0,.15);
        }

        .folha-header { text-align: center; margin-bottom: 6px; }
        .folha-header h1 { font-size: 18px; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 4px; }
        .folha-header p { font-size: 11px; color: #555; }

        .secao { margin-top: 22px; }
        .secao-titulo {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 6px 10px;
            color: #fff;
            margin-bottom: 8px;
        }
        .secao-titulo.agendada  { background: #3c8dbc; }
        .secao-titulo.em-ferias { background: #00a65a; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th {
            background: #e0e0e0;
            border: 1px solid #999;
            padding: 6px 5px;
            text-align: left;
            font-size: 11px;
        }
        td {
            border: 1px solid #ccc;
            padding: 5px;
            font-size: 11px;
        }
        .texto-vermelho { color: #dd4b39; font-weight: bold; }
        .vazio { color: #888; font-style: italic; padding: 10px; }

        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
            .folha { box-shadow: none; margin: 0; padding: 0; max-width: 100%; }
            @page { size: A4 portrait; margin: 1.5cm; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <a href="/painel/ferias" class="btn-voltar">&larr; Voltar</a>
    <button onclick="window.print()" class="btn-imprimir">&#9113; Imprimir / Salvar PDF</button>
    <span>Colaboradores em Férias</span>
</div>

<div class="folha">
    <div class="folha-header">
        <h1>{{ $app_name }}</h1>
        <p>Colaboradores em Férias — Emitido em {{ date('d/m/Y H:i') }}</p>
    </div>

    <div class="secao">
        <div class="secao-titulo agendada">Agendada ({{ count($agendadas) }})</div>
        @if(count($agendadas) > 0)
        <table>
            <thead>
                <tr>
                    <th>Colaborador</th>
                    <th>Tipo</th>
                    <th>Ano Ref.</th>
                    <th>Início</th>
                    <th>Fim</th>
                    <th>Dias</th>
                    <th>Situação</th>
                </tr>
            </thead>
            <tbody>
                @foreach($agendadas as $item)
                <tr>
                    <td>{{ utf8_decode($item['ferias']->usuario->nome) }}</td>
                    <td>{{ $item['ferias']->tipoLabel() }}</td>
                    <td>{{ $item['ferias']->ano_referencia }}</td>
                    <td>{{ $item['ferias']->data_inicio->format('d/m/Y') }}</td>
                    <td>{{ $item['ferias']->data_fim->format('d/m/Y') }}</td>
                    <td>{{ $item['ferias']->dias() }}</td>
                    <td>
                        @if($item['tl']['chave'] === 'alerta_saida')
                            <span class="texto-vermelho">Sai em {{ $item['tl']['dias'] }} dia{{ $item['tl']['dias'] == 1 ? '' : 's' }}</span>
                        @else
                            Agendada
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p class="vazio">Nenhum colaborador com férias agendadas.</p>
        @endif
    </div>

    <div class="secao">
        <div class="secao-titulo em-ferias">Em Férias ({{ count($emFerias) }})</div>
        @if(count($emFerias) > 0)
        <table>
            <thead>
                <tr>
                    <th>Colaborador</th>
                    <th>Tipo</th>
                    <th>Ano Ref.</th>
                    <th>Início</th>
                    <th>Fim</th>
                    <th>Dias</th>
                    <th>Situação</th>
                </tr>
            </thead>
            <tbody>
                @foreach($emFerias as $item)
                <tr>
                    <td>{{ utf8_decode($item['ferias']->usuario->nome) }}</td>
                    <td>{{ $item['ferias']->tipoLabel() }}</td>
                    <td>{{ $item['ferias']->ano_referencia }}</td>
                    <td>{{ $item['ferias']->data_inicio->format('d/m/Y') }}</td>
                    <td>{{ $item['ferias']->data_fim->format('d/m/Y') }}</td>
                    <td>{{ $item['ferias']->dias() }}</td>
                    <td>
                        @if($item['tl']['chave'] === 'alerta_retorno')
                            <span class="texto-vermelho">Retorna em {{ $item['tl']['dias'] }} dia{{ $item['tl']['dias'] == 1 ? '' : 's' }}</span>
                        @else
                            Em Férias
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p class="vazio">Nenhum colaborador em férias no momento.</p>
        @endif
    </div>
</div>

</body>
</html>
