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
                          <input type="hidden" name="gps_latitude" class="campo-gps-latitude">
                          <input type="hidden" name="gps_longitude" class="campo-gps-longitude">
                          <input type="hidden" name="gps_precisao" class="campo-gps-precisao">
                          <input type='submit' value='ENTRADA' class="btn btn-success" style="width: 100%;" {{ isset($ipPermitido) && !$ipPermitido ? 'disabled' : '' }}>
                      </form>
                  </div>
                  <div class='col-md-6 col-xs-6'>
                      <form method="post" action="registrar" name="form-saida" id="form-saida">
                          {{ csrf_field() }}
                          <input type="hidden" name="area" value="saida">
                          <input type="hidden" name="hora" value="<?=$hora?>">
                          <input type="hidden" name="gps_latitude" class="campo-gps-latitude">
                          <input type="hidden" name="gps_longitude" class="campo-gps-longitude">
                          <input type="hidden" name="gps_precisao" class="campo-gps-precisao">
                          <input type='submit' value='SAÍDA' class="btn btn-danger" style="width: 100%;" {{ isset($ipPermitido) && !$ipPermitido ? 'disabled' : '' }}>
                      </form>
                  </div>
              </div>

              <div class="box box-info" style="margin-top:20px;">
                <div class="box-header with-border">
                  <h3 class="box-title"><i class="fa fa-map-marker"></i> Localização do Ponto</h3>
                </div>
                <div class="box-body">
                  <p class="text-muted" style="font-size:12px; margin-top:10px;">
                    A validação do registro será feita pelo IP do usuário e GPS.
                  </p>
                  <button type="button" id="btn-detect-location" class="btn btn-primary btn-block">Verificar localização por IP</button>
                  @if(isset($ipPermitido) && !$ipPermitido)
                    <div class="alert alert-danger" style="margin-top:15px; padding:10px;">
                      Seu IP atual não está na lista de IPs permitidos para bater ponto.
                    </div>
                  @endif
                  <div class="box box-default" style="margin-top:15px; padding:15px; background:#f5f5f5;">
                    <p style="margin:0; font-size:14px;"><strong>IP de registro atual:</strong> {{ $registroIp ?: 'não disponível' }}</p>
                    @if($localizacaoIp && $localizacaoIp['latitude'] && $localizacaoIp['longitude'])
                      <p style="margin:0; font-size:14px;"><strong>Localização do IP:</strong> {{ $localizacaoIp['latitude'] }}, {{ $localizacaoIp['longitude'] }}</p>
                    @endif
                  </div>
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
                                <td>
                                    {{ $registro->entrada }}<br>
                                    <small class="text-muted">
                                        @if($registro->entrada_ip)
                                            IP: {{ $registro->entrada_ip }}<br>
                                        @endif
                                        @if($registro->entrada_latitude && $registro->entrada_longitude)
                                            Localização{{ ($registro->entrada_geo_fonte ?? null) === 'gps' ? ' (GPS)' : ' (por IP)' }}: {{ $registro->entrada_latitude }}, {{ $registro->entrada_longitude }}
                                        @endif
                                    </small>
                                </td>
                                <td>
                                    {{ $registro->saida }}<br>
                                    <small class="text-muted">
                                        @if($registro->saida_ip)
                                            IP: {{ $registro->saida_ip }}<br>
                                        @endif
                                        @if($registro->saida_latitude && $registro->saida_longitude)
                                            Localização{{ ($registro->saida_geo_fonte ?? null) === 'gps' ? ' (GPS)' : ' (por IP)' }}: {{ $registro->saida_latitude }}, {{ $registro->saida_longitude }}
                                        @endif
                                    </small>
                                </td>
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
window.addEventListener('load', function() {
    var btnDetect = document.getElementById('btn-detect-location');
    if (btnDetect) {
        btnDetect.addEventListener('click', function() {
            window.location.reload();
        });
    }

    var statusTexto = document.getElementById('status-gps-texto');
    var gpsCoords = null; // { latitude, longitude, precisao } quando capturado com sucesso

    function atualizarStatus(texto) {
        if (statusTexto) {
            statusTexto.textContent = texto;
        }
    }

    function contextoSeguro() {
        // A Geolocation API só funciona em HTTPS (ou localhost). Em HTTP puro o
        // navegador nem chega a pedir permissão — cai direto para o IP.
        return window.isSecureContext === true || location.hostname === 'localhost' || location.hostname === '127.0.0.1';
    }

    function capturarGps() {
        if (!navigator.geolocation) {
            atualizarStatus('não suportada neste navegador — usando localização por IP.');
            return;
        }

        if (!contextoSeguro()) {
            atualizarStatus('indisponível neste protocolo (HTTP) — o navegador exige HTTPS para GPS. Usando localização por IP.');
            return;
        }

        atualizarStatus('solicitando permissão...');

        navigator.geolocation.getCurrentPosition(function(pos) {
            gpsCoords = {
                latitude: pos.coords.latitude,
                longitude: pos.coords.longitude,
                precisao: pos.coords.accuracy
            };
            atualizarStatus('capturada (precisão de ' + Math.round(pos.coords.accuracy) + 'm).');
        }, function(erro) {
            var motivo = 'indisponível';
            if (erro && erro.code === erro.PERMISSION_DENIED) motivo = 'permissão negada pelo usuário';
            else if (erro && erro.code === erro.POSITION_UNAVAILABLE) motivo = 'posição indisponível';
            else if (erro && erro.code === erro.TIMEOUT) motivo = 'tempo esgotado';
            atualizarStatus(motivo + ' — usando localização por IP.');
        }, { enableHighAccuracy: true, timeout: 8000, maximumAge: 60000 });
    }

    function prepararFormulario(form) {
        if (!form) return;
        form.addEventListener('submit', function(ev) {
            if (form.dataset.gpsPreenchido === '1') {
                return; // segunda passagem: já preenchido, deixa submeter
            }

            if (gpsCoords) {
                form.querySelector('.campo-gps-latitude').value = gpsCoords.latitude;
                form.querySelector('.campo-gps-longitude').value = gpsCoords.longitude;
                form.querySelector('.campo-gps-precisao').value = gpsCoords.precisao;
            }
            // Sem GPS disponível: envia os campos vazios e o backend usa o IP como fallback.
            form.dataset.gpsPreenchido = '1';
        });
    }

    capturarGps();
    prepararFormulario(document.getElementById('form-entrada'));
    prepararFormulario(document.getElementById('form-saida'));
});
</script>

@endsection