@extends('pontoeletronico.admin')

@section('conteudo')

<?php

$dia_da_semana[0] = 'Domingo';
$dia_da_semana[1] = 'Segunda-feira';
$dia_da_semana[2] = 'Terça-feira';
$dia_da_semana[3] = 'Quarta-feira';
$dia_da_semana[4] = 'Quinta-feira';
$dia_da_semana[5] = 'Sexta-feira';
$dia_da_semana[6] = 'Sábado';

$mes[1] = 'Janeiro';
$mes[2] = 'Fevereiro';
$mes[3] = 'Março';
$mes[4] = 'Abril';
$mes[5] = 'Maio';
$mes[6] = 'Junho';
$mes[7] = 'Julho';
$mes[8] = 'Agosto';
$mes[9] = 'Setembro';
$mes[10] = 'Outubro';
$mes[11] = 'Novembro';
$mes[12] = 'Dezembro';

$dia_extenso = $dia_da_semana[Date('w')];
$mes_extenso = $mes[Date('n')];

$hora = Date('H:i');
?>
<section class="content">

    <div class="row">
        <div class="col-md-12">

          <!-- About Me Box -->
          <div class="box box-primary">
            <div class="box-header with-border">
              <h3 class="box-title">{{ utf8_decode(Session::get('login.ponto.usuario_nome')) }}</h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
              <strong><i class="fas fa-user-tie margin-r-5"></i> Cargo:</strong> {{ $usuario->cargo }} <br>
              <strong><i class="fas fa-map-marker-alt margin-r-5"></i> Local:</strong> {{ $usuario->local }}
            </div>
            <!-- /.box-body -->
          </div>
          <!-- /.box -->
        </div>
      </div>
    
      <div class="row">
        <div class="col-md-6">

          <!-- Profile Image -->
          <div class="box box-primary">
            <div class="box-body box-profile text-center">
              <?php
              $url_base_reg = getenv('APP_URL');
              $foto_reg = ($usuario && $usuario->foto)
                  ? $url_base_reg . '/img/foto/' . $usuario->foto
                  : $url_base_reg . '/img/avatar.png';
              ?>
              <img src="<?=$foto_reg?>" class="profile-user-img img-responsive img-circle" style="width:100px;height:100px;object-fit:cover;" alt="Foto de Perfil">

              <h3 class="profile-username text-center"><?=$dia_extenso?>, <?=Date('d')?> de <?=$mes_extenso?> de <?=Date('Y')?></h3>

              <p class="text-muted text-center" style='font-size: 50px;'><?=Date("H:i")?></p>

              <div class="row">
                  <div class='col-md-6 col-xs-6'>
                      <form method="post" action="registrar" name="form-entrada" id="form-entrada">
                          {{ csrf_field() }}
                          <input type="hidden" name="area" value="entrada">
                          <input type="hidden" name="hora" value="<?=$hora?>">
                          <input type="hidden" name="latitude" id="latitude-entrada" value="">
                          <input type="hidden" name="longitude" id="longitude-entrada" value="">
                          <input type='submit' value='ENTRADA' class="btn btn-success" style="width: 100%;">
                      </form>
                  </div>
                  <div class='col-md-6 col-xs-6'>
                      <form method="post" action="registrar" name="form-saida" id="form-saida">
                          {{ csrf_field() }}
                          <input type="hidden" name="area" value="saida">
                          <input type="hidden" name="hora" value="<?=$hora?>">
                          <input type="hidden" name="latitude" id="latitude-saida" value="">
                          <input type="hidden" name="longitude" id="longitude-saida" value="">
                          <input type='submit' value='SAÍDA' class="btn btn-danger" style="width: 100%;">
                      </form>
                  </div>
              </div>

              <div class="box box-info" style="margin-top:20px;">
                <div class="box-header with-border">
                  <h3 class="box-title"><i class="fa fa-map-marker"></i> Localização do Ponto</h3>
                </div>
                <div class="box-body">
                  <div id="location-status" class="alert alert-info" style="font-size:13px;">
                    <strong>Status:</strong> aguardando tentativa de obtenção da localização.
                  </div>
                  <div class="row">
                    <div class="col-md-6">
                      <div class="form-group">
                        <label>Latitude atual</label>
                        <input type="text" id="display-latitude" class="form-control" readonly>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <label>Longitude atual</label>
                        <input type="text" id="display-longitude" class="form-control" readonly>
                      </div>
                    </div>
                  </div>
                  <button type="button" id="btn-detect-location" class="btn btn-primary btn-block">Detectar localização automaticamente</button>
                  @if($habilitarLocalizacao == '1' && $latitudeConfigurada && $longitudeConfigurada)
                  <p class="text-muted" style="font-size:12px; margin-top:10px;">
                    Local do ponto configurado: <strong>{{ $latitudeConfigurada }}, {{ $longitudeConfigurada }}</strong> (raio {{ $raioConfigurado }}m).
                    O registro usará a localização do GPS ou do IP. Se essas opções falharem, insira manualmente sua posição.
                  </p>
                  @else
                  <p class="text-muted" style="font-size:12px; margin-top:10px;">
                    O registro usará a localização do GPS ou do IP. Se não for possível obter, informe as coordenadas manualmente.
                  </p>
                  @endif
                  <div class="form-group">
                    <label>Latitude de fallback</label>
                    <input type="text" id="manual-latitude" class="form-control" placeholder="-23.550520">
                  </div>
                  <div class="form-group">
                    <label>Longitude de fallback</label>
                    <input type="text" id="manual-longitude" class="form-control" placeholder="-46.633308">
                  </div>
                  <button type="button" id="btn-set-coords" class="btn btn-default btn-block">Usar coordenadas manuais</button>
                  <a id="maps-link" class="btn btn-info btn-block" href="https://www.google.com/maps" target="_blank">Abrir Google Maps</a>
                </div>
              </div>
            </div>
            <!-- /.box-body -->
          </div>
          <!-- /.box -->

        </div>
        <!-- /.col -->
        <div class="col-md-6">
 
            <div class="box box-primary">
                <div class="box-header">
                    <h3 class="box-title">Acompanhamento da Jornada</h3>
                </div>
                <!-- /.box-header -->
                <div class="box-body no-padding">
                    <table class="table table-striped text-center">
                        <tbody>
                            <tr>
                                <th style="width: 50%">ENTRADA</th>
                                <th style="width: 50%">SAÍDA</th>
                            </tr>
                            @foreach($registros as $registro)
                            <tr>
                                <td>{{ $registro->entrada }}</td>
                                <td>{{ $registro->saida }}</td>
                            </tr>
                            @endforeach
                            
                        </tbody></table>
                </div>
                <!-- /.box-body -->
            </div>            



            
            
        </div>
        <!-- /.col -->
      </div>
      <!-- /.row -->

    </section>

<script>
var localizacaoDetectada = false;
var configLatitude = '{{ $latitudeConfigurada }}';
var configLongitude = '{{ $longitudeConfigurada }}';
var locationRequired = '{{ $habilitarLocalizacao }}' === '1';
function atualizarStatus(mensagem, classe) {
    var status = document.getElementById('location-status');
    if (!status) return;
    status.className = 'alert ' + classe;
    status.innerHTML = '<strong>Status:</strong> ' + mensagem;
}

function atualizarBotoes() {
    var entradas = document.querySelectorAll('#form-entrada input[type=submit], #form-saida input[type=submit]');
    var bloqueado = locationRequired && !localizacaoDetectada;
    entradas.forEach(function(botao) {
        botao.disabled = bloqueado;
        botao.style.opacity = bloqueado ? '0.6' : '1';
        botao.title = bloqueado ? 'Aguardando localização para registrar ponto.' : '';
    });
}

function preencherLocalizacao(lat, lon, origem, marcarCapturada = true) {
    var entradaLat = document.getElementById('latitude-entrada');
    var entradaLon = document.getElementById('longitude-entrada');
    var saidaLat = document.getElementById('latitude-saida');
    var saidaLon = document.getElementById('longitude-saida');
    var displayLat = document.getElementById('display-latitude');
    var displayLon = document.getElementById('display-longitude');

    if (entradaLat) entradaLat.value = lat;
    if (entradaLon) entradaLon.value = lon;
    if (saidaLat) saidaLat.value = lat;
    if (saidaLon) saidaLon.value = lon;
    if (displayLat) displayLat.value = lat;
    if (displayLon) displayLon.value = lon;

    if (marcarCapturada) {
        localizacaoDetectada = true;
        atualizarStatus('Localização capturada via ' + origem + '.', 'alert-success');
    }
    atualizarBotoes();
}

function tentarCapturarGPS() {
    if (!navigator.geolocation) {
        atualizarStatus('Este navegador não suporta GPS. Tentando IP...', 'alert-danger');
        obterLocalizacaoPorIP();
        return;
    }

    atualizarStatus('Tentando capturar GPS...', 'alert-warning');
    navigator.geolocation.getCurrentPosition(function(position) {
        preencherLocalizacao(position.coords.latitude, position.coords.longitude, 'GPS');
    }, function(error) {
        console.warn('Erro ao obter geolocalização:', error);
        atualizarStatus('Falha ao capturar GPS: ' + error.message + '. Tentando localização por IP.', 'alert-danger');
        obterLocalizacaoPorIP();
    }, {
        enableHighAccuracy: true,
        timeout: 20000,
        maximumAge: 0
    });
}

function obterLocalizacaoPorIP() {
    atualizarStatus('Tentando localização por IP...', 'alert-warning');
    fetch('https://ipapi.co/json/')
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data && data.latitude && data.longitude) {
                preencherLocalizacao(data.latitude, data.longitude, 'IP');
            } else {
                atualizarStatus('Não foi possível obter localização por IP. Use coordenadas manuais.', 'alert-danger');
            }
        })
        .catch(function(err) {
            console.warn('Erro IP geolocation:', err);
            atualizarStatus('Falha na localização por IP. Use coordenadas manuais.', 'alert-danger');
        });
}

document.getElementById('btn-detect-location').addEventListener('click', function() {
    tentarCapturarGPS();
});

document.getElementById('btn-set-coords').addEventListener('click', function() {
    var lat = document.getElementById('manual-latitude').value.trim();
    var lon = document.getElementById('manual-longitude').value.trim();

    if (!lat || !lon) {
        alert('Preencha latitude e longitude antes de usar.');
        return;
    }

    preencherLocalizacao(lat, lon, 'fallback manual');
});

window.addEventListener('load', function() {
    atualizarBotoes();
    tentarCapturarGPS();
});
</script>

@endsection