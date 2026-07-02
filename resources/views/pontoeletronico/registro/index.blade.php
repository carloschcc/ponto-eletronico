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
var localizacaoPermitida = false;
var latitudeAtual = '';
var longitudeAtual = '';

function preencherLocalizacao() {
    if (!navigator.geolocation) {
        return;
    }

    navigator.geolocation.getCurrentPosition(function(position) {
        latitudeAtual = position.coords.latitude;
        longitudeAtual = position.coords.longitude;
        localizacaoPermitida = true;

        document.getElementById('latitude-entrada').value = latitudeAtual;
        document.getElementById('longitude-entrada').value = longitudeAtual;
        document.getElementById('latitude-saida').value = latitudeAtual;
        document.getElementById('longitude-saida').value = longitudeAtual;
    }, function() {
        localizacaoPermitida = false;
        document.getElementById('latitude-entrada').value = '';
        document.getElementById('longitude-entrada').value = '';
        document.getElementById('latitude-saida').value = '';
        document.getElementById('longitude-saida').value = '';
    }, {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0
    });
}

function calcularDistanciaEmMetros(lat1, lon1, lat2, lon2) {
    var earthRadius = 6371000;
    var dLat = (lat2 - lat1) * Math.PI / 180;
    var dLon = (lon2 - lon1) * Math.PI / 180;
    var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLon / 2) * Math.sin(dLon / 2);
    var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return earthRadius * c;
}

function validarAntesEnviar(evento) {
    var habilitarLocalizacao = '{{ env("PONTO_LOCALIZACAO_HABILITAR", "0") }}';
    if (habilitarLocalizacao !== '1') {
        return true;
    }

    if (!localizacaoPermitida || latitudeAtual === '' || longitudeAtual === '') {
        evento.preventDefault();
        alert('É necessário permitir o acesso ao GPS para registrar o ponto.');
        return false;
    }

    var latitudeConfigurada = parseFloat('{{ env("PONTO_LOCALIZACAO_LATITUDE", "") }}');
    var longitudeConfigurada = parseFloat('{{ env("PONTO_LOCALIZACAO_LONGITUDE", "") }}');
    var raioConfigurado = parseFloat('{{ env("PONTO_LOCALIZACAO_RAIO", "50") }}');

    if (isNaN(latitudeConfigurada) || isNaN(longitudeConfigurada) || isNaN(raioConfigurado)) {
        evento.preventDefault();
        alert('A configuração de localização está incompleta.');
        return false;
    }

    var distancia = calcularDistanciaEmMetros(latitudeAtual, longitudeAtual, latitudeConfigurada, longitudeConfigurada);
    if (distancia > raioConfigurado) {
        evento.preventDefault();
        alert('Você está fora da área permitida para registrar o ponto.');
        return false;
    }

    return true;
}

window.addEventListener('load', function() {
    preencherLocalizacao();
    document.getElementById('form-entrada').addEventListener('submit', validarAntesEnviar);
    document.getElementById('form-saida').addEventListener('submit', validarAntesEnviar);
});
</script>

@endsection