@extends('pontoeletronico.painel')

@section('conteudo')
<section class="content-header">
  <h1>Configurações do Sistema</h1>
</section>

<section class="content">
  <div class="row">

    {{-- Logo Principal (sistema) --}}
    <div class="col-md-4">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title"><i class="fa fa-image"></i> Logo do Sistema</h3>
        </div>
        <div class="box-body">
          <div class="text-center" style="margin-bottom:15px; background:#f4f4f4; padding:12px; border-radius:4px; min-height:80px; display:flex; align-items:center; justify-content:center;">
            <img src="{{ $logo_url }}?t={{ time() }}" alt="Logo" style="max-height:60px; max-width:100%;">
          </div>
          <p class="text-muted" style="font-size:11px; margin-bottom:10px;">
            Aparece no menu lateral do painel de gestão.
          </p>
          <form method="POST" action="{{ getenv('APP_URL') }}/painel/configuracao/logo" enctype="multipart/form-data">
            {{ csrf_field() }}
            <div class="form-group">
              <label>Nova logo <small class="text-muted">(PNG · max 2 MB)</small></label>
              <input type="file" name="logo" accept="image/png,image/jpeg,image/gif,image/svg+xml" required>
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-upload"></i> Enviar</button>
          </form>
        </div>
      </div>
    </div>

    {{-- Logo Espelho V2 --}}
    <div class="col-md-4">
      <div class="box box-warning">
        <div class="box-header with-border">
          <h3 class="box-title"><i class="fa fa-file-text-o"></i> Logo do Espelho V2</h3>
        </div>
        <div class="box-body">
          <div class="text-center" style="margin-bottom:15px; background:#f9f6ee; padding:12px; border-radius:4px; border:1px dashed #f39c12; min-height:80px; display:flex; align-items:center; justify-content:center;">
            @if($logo_espelho_existe)
              <img src="{{ $logo_espelho_url }}?t={{ time() }}" alt="Logo Espelho" style="max-height:65px; max-width:100%;">
            @else
              <span class="text-muted" style="font-size:12px;"><i class="fa fa-image"></i><br>Nenhuma logo cadastrada.<br>Será exibido o brasão circular.</span>
            @endif
          </div>
          <p class="text-muted" style="font-size:11px; margin-bottom:10px;">
            Aparece no cabeçalho da <strong>Folha de Frequência V2</strong> (impressão). Use uma imagem <strong>horizontal</strong>, fundo branco ou transparente, PNG recomendado.
          </p>
          <form method="POST" action="{{ getenv('APP_URL') }}/painel/configuracao/logo-espelho" enctype="multipart/form-data">
            {{ csrf_field() }}
            <div class="form-group">
              <label>Logo horizontal <small class="text-muted">(PNG · max 2 MB)</small></label>
              <input type="file" name="logo_espelho" accept="image/png,image/jpeg,image/gif,image/svg+xml" required>
            </div>
            <button type="submit" class="btn btn-warning btn-sm"><i class="fa fa-upload"></i> Enviar</button>
            @if($logo_espelho_existe)
              <a href="{{ $logo_espelho_url }}?t={{ time() }}" target="_blank" class="btn btn-default btn-sm">
                <i class="fa fa-eye"></i> Ver atual
              </a>
            @endif
          </form>
        </div>
      </div>
    </div>

    {{-- Configuração de Localização para Registro de Ponto --}}
    <div class="col-md-4">
      <div class="box box-info">
        <div class="box-header with-border">
          <h3 class="box-title"><i class="fa fa-map-marker"></i> Localização para Registro</h3>
        </div>
        <div class="box-body">
          <form method="POST" action="{{ getenv('APP_URL') }}/painel/configuracao/localizacao">
            {{ csrf_field() }}
            <div class="form-group">
              <label>
                <input type="checkbox" name="habilitar_localizacao" value="1" {{ $configuracao_localizacao == '1' ? 'checked' : '' }}>
                Exigir localização ao registrar ponto
              </label>
            </div>
            <div class="form-group">
              <label>Latitude</label>
              <input type="text" name="latitude" class="form-control" value="{{ $localizacao_latitude }}" placeholder="-23.550520">
            </div>
            <div class="form-group">
              <label>Longitude</label>
              <input type="text" name="longitude" class="form-control" value="{{ $localizacao_longitude }}" placeholder="-46.633308">
            </div>
            <div class="form-group">
              <label>Raio permitido (metros)</label>
              <input type="number" name="raio" class="form-control" min="1" value="{{ $localizacao_raio }}" placeholder="50">
            </div>
            <div class="form-group">
              <label>IPs permitidos</label>
              <textarea name="ips_permitidos" class="form-control" rows="4" placeholder="192.168.0.10\n10.0.0.0/24\n172.16.1.5">{{ $ips_permitidos ?? '' }}</textarea>
              <p class="text-muted" style="font-size:11px; margin-top:6px;">
                Informe um IP por linha, ou CIDR para um intervalo. Use vírgula, ponto e vírgula ou quebra de linha como separador.
                <strong>Deixe em branco para permitir que qualquer IP registre o ponto</strong> (nenhuma restrição de IP será aplicada).
              </p>
            </div>
            <button type="submit" class="btn btn-info btn-sm"><i class="fa fa-save"></i> Salvar</button>
          </form>
          <p class="text-muted" style="margin-top:12px; font-size:12px;">
            <i class="fa fa-info-circle"></i>
            Quando habilitado, o sistema exige que o dispositivo esteja dentro da área configurada para registrar ponto.
          </p>
        </div>
      </div>
    </div>

    {{-- Fuso Horário (informativo) --}}
    <div class="col-md-4">
      <div class="box box-info">
        <div class="box-header with-border">
          <h3 class="box-title"><i class="fa fa-clock-o"></i> Hora do Servidor</h3>
        </div>
        <div class="box-body">
          <table class="table table-condensed" style="margin-bottom:0;">
            <tr>
              <td><strong>Hora atual:</strong></td>
              <td style="font-size:1.2em; font-weight:bold;">{{ $hora_atual }}</td>
            </tr>
            <tr>
              <td><strong>Fuso horário:</strong></td>
              <td>{{ $timezone_atual }}</td>
            </tr>
          </table>
          <p class="text-muted" style="margin-top:12px; font-size:12px;">
            <i class="fa fa-info-circle"></i>
            Fuso detectado automaticamente do sistema operacional do servidor.
          </p>
        </div>
      </div>
    </div>

  </div>
</section>
@endsection
