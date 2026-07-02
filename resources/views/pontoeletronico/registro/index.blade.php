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
                          <input type='submit' value='ENTRADA' class="btn btn-success" style="width: 100%;">
                      </form>
                  </div>
                  <div class='col-md-6 col-xs-6'>
                      <form method="post" action="registrar" name="form-saida" id="form-saida">
                          {{ csrf_field() }}
                          <input type="hidden" name="area" value="saida">
                          <input type="hidden" name="hora" value="<?=$hora?>">
                          <input type='submit' value='SAÍDA' class="btn btn-danger" style="width: 100%;">
                      </form>
                  </div>
              </div>

              <div class="box box-info" style="margin-top:20px;">
                <div class="box-header with-border">
                  <h3 class="box-title"><i class="fa fa-map-marker"></i> Localização do Ponto</h3>
                </div>
                <div class="box-body">
                  @if($habilitarLocalizacao == '1' && $latitudeConfigurada && $longitudeConfigurada)
                  <p class="text-muted" style="font-size:12px; margin-top:10px;">
                    Local do ponto configurado: <strong>{{ $latitudeConfigurada }}, {{ $longitudeConfigurada }}</strong> (raio {{ $raioConfigurado }}m).
                    O registro será validado pelo IP do usuário. Coordenadas manuais não serão usadas.
                  </p>
                  @else
                  <p class="text-muted" style="font-size:12px; margin-top:10px;">
                    O registro será validado pelo IP do usuário. Coordenadas manuais não serão usadas.
                  </p>
                  @endif
                  <button type="button" id="btn-detect-location" class="btn btn-primary btn-block">Verificar localização por IP</button>
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
window.addEventListener('load', function() {
    var btnDetect = document.getElementById('btn-detect-location');
    if (btnDetect) {
        btnDetect.addEventListener('click', function() {
            alert('A validação agora usa somente a localização por IP do usuário. O botão está aqui apenas para disparar a tentativa de verificação.');
        });
    }
});
</script>

@endsection