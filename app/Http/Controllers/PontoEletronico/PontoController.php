<?php namespace App\Http\Controllers\PontoEletronico;


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Configuracao;
use App\Http\Requests;
use App\Http\Controllers\Controller;

use Request;
use Session;
use App\Usuario;
use App\Ponto;


class PontoController extends PontoEletronicoController {
    
    
    public function __construct()
    {
        $this->middleware('authMiddleware');
        
    }
    
    public function registrar_validando(){
        
        $url_base = getenv('APP_URL');

        $this->garantirColunaObservacoes();
        
        $usuario_id = Session::get('login.ponto.usuario_id');
        
        $hoje = Date("Y-m-d");
        
        $ultimo_registro = Ponto::where(['usuario_id' => $usuario_id, 'data' => $hoje])->orderBy('id', 'DESC')->first();
        
        if($ultimo_registro):
            $registro_entrada = $ultimo_registro->entrada;
            $registro_saida = $ultimo_registro->saida;
        else:
            $registro_entrada = '';
            $registro_saida = '';
        endif;
        
        
        $area = Request::input('area');
        $hora_registrada = Request::input('hora');

        $habilitar_localizacao = Configuracao::valor('PONTO_LOCALIZACAO_HABILITAR', '0');
        $latitude_cadastrada = Configuracao::valor('PONTO_LOCALIZACAO_LATITUDE', '');
        $longitude_cadastrada = Configuracao::valor('PONTO_LOCALIZACAO_LONGITUDE', '');
        $raio_cadastrado = (float) Configuracao::valor('PONTO_LOCALIZACAO_RAIO', '50');
        $ips_permitidos = Configuracao::valor('PONTO_IPS_PERMITIDOS', '');

        $registro_ip = $this->obterIpCliente();

        $gps_lat_raw = Request::input('gps_latitude');
        $gps_lon_raw = Request::input('gps_longitude');
        $gps_precisao_raw = Request::input('gps_precisao');
        $localizacao_gps = $this->obterLocalizacaoGpsRequest($gps_lat_raw, $gps_lon_raw, $gps_precisao_raw);

        if ($localizacao_gps) {
            $localizacao_registro = $localizacao_gps;
            $fonte_localizacao = 'gps';
        } else {
            $localizacao_registro = $this->obterLocalizacaoIp();
            $fonte_localizacao = $localizacao_registro ? 'ip' : null;
        }

        if (!$this->ipPermitido($registro_ip, $this->parseIpsPermitidos($ips_permitidos))) {
            Session::put('status.msg', 'Seu IP não está na lista de IPs permitidos para registrar ponto.');
            return redirect(getenv('APP_URL').'/dashboard');
        }

        $observacoes_registro = $this->montarObservacoesRegistro($registro_ip, $localizacao_registro, $fonte_localizacao, $habilitar_localizacao, $latitude_cadastrada, $longitude_cadastrada);

        if($area == 'entrada'):
            
            if(!empty($registro_saida) AND !empty($registro_entrada)):
                
                $ponto = new Ponto();
                $ponto->usuario_id = $usuario_id;
                $ponto->data = $hoje;
                $ponto->entrada = $hora_registrada;
                $ponto->entrada_status = 0;
                $ponto->entrada_ip = $registro_ip;
                $ponto->entrada_latitude = $localizacao_registro['latitude'] ?? null;
                $ponto->entrada_longitude = $localizacao_registro['longitude'] ?? null;
                $ponto->entrada_geo_fonte = $fonte_localizacao;
                $ponto->observacoes = 'Entrada - ' . $observacoes_registro;
                $ponto->status = 0;
                $ponto->save();
                
                Session::put('status.msg', 'Entrada registrada com sucesso! Registro por IP válido.');
                Session::put('status.error_redirect', $url_base.'/sair');
                
                return redirect(getenv('APP_URL').'/dashboard');
            
            elseif(!empty($registro_saida) AND empty($registro_entrada)):    
                
                $ponto = new Ponto();
                $ponto->usuario_id = $usuario_id;
                $ponto->data = $hoje;
                $ponto->entrada = $hora_registrada;
                $ponto->entrada_status = 0;
                $ponto->entrada_ip = $registro_ip;
                $ponto->entrada_latitude = $localizacao_registro['latitude'] ?? null;
                $ponto->entrada_longitude = $localizacao_registro['longitude'] ?? null;
                $ponto->entrada_geo_fonte = $fonte_localizacao;
                $ponto->observacoes = 'Entrada - ' . $observacoes_registro;
                $ponto->status = 0;
                $ponto->save();
                
                Session::put('status.msg', 'Entrada registrada com sucesso! Registro por IP válido.');
                Session::put('status.error_redirect', $url_base.'/sair');
                
                return redirect(getenv('APP_URL').'/dashboard');
                
            elseif(empty($registro_saida) AND !empty($registro_entrada)):

                Session::put('status.hora_registrada', $hora_registrada);
                Session::put('status.area', $area);
                Session::put('status.gps_latitude', $gps_lat_raw);
                Session::put('status.gps_longitude', $gps_lon_raw);
                Session::put('status.gps_precisao', $gps_precisao_raw);

                Session::put('status.msg_confirm', 'Você está fazendo um registro de entrada sem um registro prévio de saída. Confirma?');
                Session::put('status.redir_confirm', $url_base.'/registrar');

                return redirect(getenv('APP_URL').'/dashboard');
            
            else:
                
                $ponto = new Ponto();
                $ponto->usuario_id = $usuario_id;
                $ponto->data = $hoje;
                $ponto->entrada = $hora_registrada;
                $ponto->entrada_status = 0;
                $ponto->entrada_ip = $registro_ip;
                $ponto->entrada_latitude = $localizacao_registro['latitude'] ?? null;
                $ponto->entrada_longitude = $localizacao_registro['longitude'] ?? null;
                $ponto->entrada_geo_fonte = $fonte_localizacao;
                $ponto->observacoes = 'Entrada - ' . $observacoes_registro;
                $ponto->status = 0;
                $ponto->save();
                
                Session::put('status.msg', 'Entrada registrada com sucesso! Registro por IP válido.');
                Session::put('status.error_redirect', $url_base.'/sair');
                
                return redirect(getenv('APP_URL').'/dashboard');
                
            endif;
            
        endif;
        
        
        
        if($area == 'saida'):
            
            if(!empty($registro_saida) AND !empty($registro_entrada)):

                Session::put('status.hora_registrada', $hora_registrada);
                Session::put('status.area', $area);
                Session::put('status.gps_latitude', $gps_lat_raw);
                Session::put('status.gps_longitude', $gps_lon_raw);
                Session::put('status.gps_precisao', $gps_precisao_raw);

                Session::put('status.msg_confirm', 'Você está fazendo um registro de saída sem um registro prévio de entrada. Confirma?');
                Session::put('status.redir_confirm', $url_base.'/registrar');

                return redirect(getenv('APP_URL').'/dashboard');

            elseif(!empty($registro_saida) AND empty($registro_entrada)):
                
                $ponto = new Ponto();
                $ponto->usuario_id = $usuario_id;
                $ponto->data = $hoje;
                $ponto->saida = $hora_registrada;
                $ponto->saida_status = 0;
                $ponto->saida_ip = $registro_ip;
                $ponto->saida_latitude = $localizacao_registro['latitude'] ?? null;
                $ponto->saida_longitude = $localizacao_registro['longitude'] ?? null;
                $ponto->saida_geo_fonte = $fonte_localizacao;
                $ponto->observacoes = 'Saída - ' . $observacoes_registro;
                $ponto->status = 0;
                $ponto->save();

                Session::put('status.msg', 'Saída registrada com sucesso! Registro por IP válido.');
                Session::put('status.error_redirect', $url_base.'/sair');

                return redirect(getenv('APP_URL').'/dashboard');

            elseif(empty($registro_saida) AND !empty($registro_entrada)):

                $ponto = Ponto::find($ultimo_registro->id);
                $ponto->saida = $hora_registrada;
                $ponto->saida_status = 0;
                $ponto->saida_ip = $registro_ip;
                $ponto->saida_latitude = $localizacao_registro['latitude'] ?? null;
                $ponto->saida_longitude = $localizacao_registro['longitude'] ?? null;
                $ponto->saida_geo_fonte = $fonte_localizacao;
                $saida_observacoes = 'Saída - ' . $observacoes_registro;
                $ponto->observacoes = trim(($ponto->observacoes ?: '') . ' | ' . $saida_observacoes, ' |');
                $ponto->save();
                
                Session::put('status.msg', 'Saída registrada com sucesso! Registro por IP válido.');
                Session::put('status.error_redirect', $url_base.'/sair');  
                
                return redirect(getenv('APP_URL').'/dashboard');
            
            else:

                Session::put('status.hora_registrada', $hora_registrada);
                Session::put('status.area', $area);
                Session::put('status.gps_latitude', $gps_lat_raw);
                Session::put('status.gps_longitude', $gps_lon_raw);
                Session::put('status.gps_precisao', $gps_precisao_raw);

                Session::put('status.msg_confirm', 'Você está fazendo um registro de saída sem um registro prévio de entrada. Confirma?');
                Session::put('status.redir_confirm', $url_base.'/registrar');

                return redirect(getenv('APP_URL').'/dashboard');

            endif;

        endif;

        Session::put('status.msg', 'Falha no registro. Por favor, tente novamente.');
        return redirect(getenv('APP_URL').'/dashboard');
        
    }
    
    private function calcularDistanciaEmMetros($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000;
        $lat1Rad = deg2rad((float) $lat1);
        $lon1Rad = deg2rad((float) $lon1);
        $lat2Rad = deg2rad((float) $lat2);
        $lon2Rad = deg2rad((float) $lon2);

        $deltaLat = $lat2Rad - $lat1Rad;
        $deltaLon = $lon2Rad - $lon1Rad;

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) + cos($lat1Rad) * cos($lat2Rad) * sin($deltaLon / 2) * sin($deltaLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function montarObservacoesRegistro($registro_ip, $localizacao, $fonte_localizacao, $habilitar_localizacao, $latitude_cadastrada, $longitude_cadastrada)
    {
        $observacoes_registro = 'IP: ' . ($registro_ip ?: 'não disponível');

        if ($localizacao) {
            $rotulo = ($fonte_localizacao === 'gps') ? 'Localização GPS' : 'Localização IP';
            $observacoes_registro .= ' | ' . $rotulo . ': ' . $localizacao['latitude'] . ', ' . $localizacao['longitude'];

            if ($fonte_localizacao === 'gps' && !empty($localizacao['precisao'])) {
                $observacoes_registro .= ' (precisão: ' . $localizacao['precisao'] . 'm)';
            }

            if ($habilitar_localizacao == '1' && $latitude_cadastrada !== '' && $longitude_cadastrada !== '') {
                $distancia = $this->calcularDistanciaEmMetros($localizacao['latitude'], $localizacao['longitude'], $latitude_cadastrada, $longitude_cadastrada);
                $observacoes_registro .= ' | Distância do ponto configurado: ' . round($distancia) . 'm';
            }
        }

        return $observacoes_registro;
    }

    private function garantirColunaObservacoes()
    {
        if (!Schema::hasColumn('ponto', 'observacoes')) {
            Schema::table('ponto', function (Blueprint $table) {
                $table->text('observacoes')->nullable()->after('status');
            });
        }

        if (!Schema::hasColumn('ponto', 'entrada_ip')) {
            Schema::table('ponto', function (Blueprint $table) {
                $table->string('entrada_ip', 45)->nullable()->after('entrada_status');
                $table->string('entrada_latitude', 50)->nullable()->after('entrada_ip');
                $table->string('entrada_longitude', 50)->nullable()->after('entrada_latitude');
            });
        }

        if (!Schema::hasColumn('ponto', 'saida_ip')) {
            Schema::table('ponto', function (Blueprint $table) {
                $table->string('saida_ip', 45)->nullable()->after('saida_status');
                $table->string('saida_latitude', 50)->nullable()->after('saida_ip');
                $table->string('saida_longitude', 50)->nullable()->after('saida_latitude');
            });
        }

        if (!Schema::hasColumn('ponto', 'entrada_geo_fonte')) {
            Schema::table('ponto', function (Blueprint $table) {
                $table->string('entrada_geo_fonte', 10)->nullable()->after('entrada_longitude');
            });
        }

        if (!Schema::hasColumn('ponto', 'saida_geo_fonte')) {
            Schema::table('ponto', function (Blueprint $table) {
                $table->string('saida_geo_fonte', 10)->nullable()->after('saida_longitude');
            });
        }
    }

    public function registrar(){
        
        $url_base = getenv('APP_URL');
        
        $this->garantirColunaObservacoes();
        
        $usuario_id = Session::get('login.ponto.usuario_id');
        
        $hoje = Date("Y-m-d");
        
        $hora_registrada = Session::get('status.hora_registrada');
        $area = Session::get('status.area');

               
        $habilitar_localizacao = Configuracao::valor('PONTO_LOCALIZACAO_HABILITAR', '0');
        $latitude_cadastrada = Configuracao::valor('PONTO_LOCALIZACAO_LATITUDE', '');
        $longitude_cadastrada = Configuracao::valor('PONTO_LOCALIZACAO_LONGITUDE', '');

        $localizacao_gps = $this->obterLocalizacaoGpsRequest(
            Session::get('status.gps_latitude'),
            Session::get('status.gps_longitude'),
            Session::get('status.gps_precisao')
        );
        Session::forget(['status.gps_latitude', 'status.gps_longitude', 'status.gps_precisao']);

        if($area == 'entrada'):

            $registro_ip = $this->obterIpCliente();
            if ($localizacao_gps) {
                $localizacao_registro = $localizacao_gps;
                $fonte_localizacao = 'gps';
            } else {
                $localizacao_registro = $this->obterLocalizacaoIp();
                $fonte_localizacao = $localizacao_registro ? 'ip' : null;
            }
            $observacoes_registro = $this->montarObservacoesRegistro($registro_ip, $localizacao_registro, $fonte_localizacao, $habilitar_localizacao, $latitude_cadastrada, $longitude_cadastrada);

            $ponto = new Ponto();
            $ponto->usuario_id = $usuario_id;
            $ponto->data = $hoje;
            $ponto->entrada = $hora_registrada;
            $ponto->entrada_status = 0;
            $ponto->entrada_ip = $registro_ip;
            $ponto->entrada_latitude = $localizacao_registro['latitude'] ?? null;
            $ponto->entrada_longitude = $localizacao_registro['longitude'] ?? null;
            $ponto->entrada_geo_fonte = $fonte_localizacao;
            $ponto->observacoes = 'Entrada - ' . $observacoes_registro;
            $ponto->status = 0;
            $ponto->save();

            Session::put('status.msg', 'Entrada registrada com sucesso! Registro por IP válido.');
            Session::put('status.error_redirect', $url_base.'/sair');

        endif;

        if($area == 'saida'):

            $registro_ip = $this->obterIpCliente();
            if ($localizacao_gps) {
                $localizacao_registro = $localizacao_gps;
                $fonte_localizacao = 'gps';
            } else {
                $localizacao_registro = $this->obterLocalizacaoIp();
                $fonte_localizacao = $localizacao_registro ? 'ip' : null;
            }
            $observacoes_registro = $this->montarObservacoesRegistro($registro_ip, $localizacao_registro, $fonte_localizacao, $habilitar_localizacao, $latitude_cadastrada, $longitude_cadastrada);

            $ponto = new Ponto();
            $ponto->usuario_id = $usuario_id;
            $ponto->data = $hoje;
            $ponto->saida = $hora_registrada;
            $ponto->saida_status = 0;
            $ponto->saida_ip = $registro_ip;
            $ponto->saida_latitude = $localizacao_registro['latitude'] ?? null;
            $ponto->saida_longitude = $localizacao_registro['longitude'] ?? null;
            $ponto->saida_geo_fonte = $fonte_localizacao;
            $ponto->observacoes = 'Saída - ' . $observacoes_registro;
            $ponto->status = 0;
            $ponto->save();
            
            Session::put('status.msg', 'Saída registrada com sucesso! Registro por IP válido.');
            Session::put('status.error_redirect', $url_base.'/sair');
            
        endif;
        
        return redirect(getenv('APP_URL').'/dashboard');
        
        
    }
    
}