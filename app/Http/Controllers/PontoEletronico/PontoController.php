<?php namespace App\Http\Controllers\PontoEletronico;


use Illuminate\Support\Facades\DB;
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

        $registro_ip = $this->obterIpCliente();
        $localizacao_ip = null;
        if ($habilitar_localizacao == '1') {
            $localizacao_ip = $this->obterLocalizacaoIp();
        }

        if ($habilitar_localizacao == '1') {
            if ($latitude_cadastrada === '' || $longitude_cadastrada === '') {
                Session::put('status.msg', 'A configuração de localização não está completa. Solicite ao administrador.');
                Session::put('status.error_redirect', $url_base.'/dashboard');
                return redirect($url_base.'/dashboard');
            }

            if (!$registro_ip) {
                Session::put('status.msg', 'Não foi possível determinar seu IP para registro. Registro negado.');
                Session::put('status.error_redirect', $url_base.'/dashboard');
                return redirect($url_base.'/dashboard');
            }

            if (!$localizacao_ip) {
                Session::put('status.msg', 'Não foi possível obter localização por IP. Registro negado.');
                Session::put('status.error_redirect', $url_base.'/dashboard');
                return redirect($url_base.'/dashboard');
            }

            $distancia_ip = $this->calcularDistanciaEmMetros($localizacao_ip['latitude'], $localizacao_ip['longitude'], $latitude_cadastrada, $longitude_cadastrada);
            if ($distancia_ip > $raio_cadastrado) {
                Session::put('status.msg', 'Sua localização pela rede (IP) está fora da área permitida.');
                Session::put('status.error_redirect', $url_base.'/dashboard');
                return redirect($url_base.'/dashboard');
            }
        }

        if($area == 'entrada'):
            
            if(!empty($registro_saida) AND !empty($registro_entrada)):
                
                $ponto = new Ponto();
                $ponto->usuario_id = $usuario_id;
                $ponto->data = $hoje;
                $ponto->entrada = $hora_registrada;
                $ponto->entrada_status = 0;
                $ponto->entrada_ip = $registro_ip;
                if ($localizacao_ip) {
                    $ponto->entrada_latitude = $localizacao_ip['latitude'];
                    $ponto->entrada_longitude = $localizacao_ip['longitude'];
                }
                $ponto->status = 0;
                $ponto->save();
                
                Session::put('status.msg', 'Entrada registrada com sucesso! Até breve!');
                Session::put('status.error_redirect', $url_base.'/sair');
                
                return redirect(getenv('APP_URL').'/dashboard');
            
            elseif(!empty($registro_saida) AND empty($registro_entrada)):    
                
                $ponto = new Ponto();
                $ponto->usuario_id = $usuario_id;
                $ponto->data = $hoje;
                $ponto->entrada = $hora_registrada;
                $ponto->entrada_status = 0;
                $ponto->entrada_ip = $registro_ip;
                if ($localizacao_ip) {
                    $ponto->entrada_latitude = $localizacao_ip['latitude'];
                    $ponto->entrada_longitude = $localizacao_ip['longitude'];
                }
                $ponto->status = 0;
                $ponto->save();
                
                Session::put('status.msg', 'Entrada registrada com sucesso! Até breve!');
                Session::put('status.error_redirect', $url_base.'/sair');
                
                return redirect(getenv('APP_URL').'/dashboard');
                
            elseif(empty($registro_saida) AND !empty($registro_entrada)):
                
                Session::put('status.hora_registrada', $hora_registrada);
                Session::put('status.area', $area);
                
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
                if ($localizacao_ip) {
                    $ponto->entrada_latitude = $localizacao_ip['latitude'];
                    $ponto->entrada_longitude = $localizacao_ip['longitude'];
                }
                $ponto->status = 0;
                $ponto->save();
                
                Session::put('status.msg', 'Entrada registrada com sucesso! Até breve!');
                Session::put('status.error_redirect', $url_base.'/sair');
                
                return redirect(getenv('APP_URL').'/dashboard');
                
            endif;
            
        endif;
        
        
        
        if($area == 'saida'):
            
            if(!empty($registro_saida) AND !empty($registro_entrada)):

                Session::put('status.hora_registrada', $hora_registrada);
                Session::put('status.area', $area);
                
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
                if ($localizacao_ip) {
                    $ponto->saida_latitude = $localizacao_ip['latitude'];
                    $ponto->saida_longitude = $localizacao_ip['longitude'];
                }
                $ponto->status = 0;
                $ponto->save();
                
                Session::put('status.msg', 'Saída registrada com sucesso! Até breve!');
                Session::put('status.error_redirect', $url_base.'/sair');
                
                return redirect(getenv('APP_URL').'/dashboard');
                
            elseif(empty($registro_saida) AND !empty($registro_entrada)):
                
                $ponto = Ponto::find($ultimo_registro->id);
                $ponto->saida = $hora_registrada;
                $ponto->saida_status = 0;
                $ponto->saida_ip = $registro_ip;
                if ($localizacao_ip) {
                    $ponto->saida_latitude = $localizacao_ip['latitude'];
                    $ponto->saida_longitude = $localizacao_ip['longitude'];
                }
                $ponto->save();
                
                Session::put('status.msg', 'Saída registrada com sucesso! Até breve!');
                Session::put('status.error_redirect', $url_base.'/sair');  
                
                return redirect(getenv('APP_URL').'/dashboard');
            
            else:

                Session::put('status.hora_registrada', $hora_registrada);
                Session::put('status.area', $area);
                
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

    private function obterIpCliente()
    {
        $ip = Request::server('HTTP_X_FORWARDED_FOR');
        if ($ip) {
            $ips = explode(',', $ip);
            $ip = trim(end($ips));
        }

        if (!$ip) {
            $ip = Request::server('HTTP_CLIENT_IP');
        }

        if (!$ip) {
            $ip = Request::server('REMOTE_ADDR');
        }

        if (!$ip) {
            $ip = Request::ip();
        }

        if (!$ip) {
            return null;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return null;
        }

        return $ip;
    }

    private function obterLocalizacaoIp()
    {
        $ip = $this->obterIpCliente();
        if (!$ip) {
            return null;
        }

        $url = 'https://ipapi.co/' . $ip . '/json/';
        $resultado = null;

        if (ini_get('allow_url_fopen')) {
            $resultado = @file_get_contents($url);
        }

        if (!$resultado && function_exists('curl_version')) {
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, 5);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
            $resultado = curl_exec($curl);
            curl_close($curl);
        }

        if (!$resultado) {
            return null;
        }

        $dados = json_decode($resultado, true);
        if (!is_array($dados) || empty($dados['latitude']) || empty($dados['longitude'])) {
            return null;
        }

        return [
            'latitude' => $dados['latitude'],
            'longitude' => $dados['longitude'],
        ];
    }

    public function registrar(){
        
        $url_base = getenv('APP_URL');
        
        $usuario_id = Session::get('login.ponto.usuario_id');
        
        $hoje = Date("Y-m-d");
        
        $hora_registrada = Session::get('status.hora_registrada');
        $area = Session::get('status.area');

               
        if($area == 'entrada'):
        
            $ponto = new Ponto();
            $ponto->usuario_id = $usuario_id;
            $ponto->data = $hoje;
            $ponto->entrada = $hora_registrada;
            $ponto->entrada_status = 0;
            $ponto->status = 0;
            $ponto->save();

            Session::put('status.msg', 'Entrada registrada com sucesso! Até breve!');
            Session::put('status.error_redirect', $url_base.'/sair');
            
        endif;
        
        if($area == 'saida'):
            
            $ponto = new Ponto();
            $ponto->usuario_id = $usuario_id;
            $ponto->data = $hoje;
            $ponto->saida = $hora_registrada;
            $ponto->saida_status = 0;
            $ponto->status = 0;
            $ponto->save();    
            
            Session::put('status.msg', 'Saída registrada com sucesso! Até breve!');
            Session::put('status.error_redirect', $url_base.'/sair');
            
        endif;
        
        return redirect(getenv('APP_URL').'/dashboard');
        
        
    }
    
}