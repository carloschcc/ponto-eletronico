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

        $observacoes_registro = $this->montarObservacoesRegistro($registro_ip, $localizacao_ip, $habilitar_localizacao, $latitude_cadastrada, $longitude_cadastrada);

        if($area == 'entrada'):
            
            if(!empty($registro_saida) AND !empty($registro_entrada)):
                
                $ponto = new Ponto();
                $ponto->usuario_id = $usuario_id;
                $ponto->data = $hoje;
                $ponto->entrada = $hora_registrada;
                $ponto->entrada_status = 0;
                $ponto->observacoes = 'Entrada - ' . $observacoes_registro;
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
                $ponto->observacoes = 'Entrada - ' . $observacoes_registro;
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
                $ponto->observacoes = 'Entrada - ' . $observacoes_registro;
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
                $ponto->observacoes = 'Saída - ' . $observacoes_registro;
                $ponto->status = 0;
                $ponto->save();
                
                Session::put('status.msg', 'Saída registrada com sucesso! Até breve!');
                Session::put('status.error_redirect', $url_base.'/sair');
                
                return redirect(getenv('APP_URL').'/dashboard');
                
            elseif(empty($registro_saida) AND !empty($registro_entrada)):
                
                $ponto = Ponto::find($ultimo_registro->id);
                $ponto->saida = $hora_registrada;
                $ponto->saida_status = 0;
                $saida_observacoes = 'Saída - ' . $observacoes_registro;
                $ponto->observacoes = trim(($ponto->observacoes ?: '') . ' | ' . $saida_observacoes, ' |');
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

    private function montarObservacoesRegistro($registro_ip, $localizacao_ip, $habilitar_localizacao, $latitude_cadastrada, $longitude_cadastrada)
    {
        $observacoes_registro = 'IP: ' . ($registro_ip ?: 'não disponível');

        if ($localizacao_ip) {
            $observacoes_registro .= ' | Localização IP: ' . $localizacao_ip['latitude'] . ', ' . $localizacao_ip['longitude'];
        } else {
            $observacoes_registro .= ' | Localização IP: não disponível';
        }

        if ($habilitar_localizacao == '1' && $latitude_cadastrada !== '' && $longitude_cadastrada !== '' && $localizacao_ip) {
            $distancia_ip = $this->calcularDistanciaEmMetros($localizacao_ip['latitude'], $localizacao_ip['longitude'], $latitude_cadastrada, $longitude_cadastrada);
            $observacoes_registro .= ' | Distância do ponto configurado: ' . round($distancia_ip) . 'm';
        }

        return $observacoes_registro;
    }

    public function registrar(){
        
        $url_base = getenv('APP_URL');
        
        $usuario_id = Session::get('login.ponto.usuario_id');
        
        $hoje = Date("Y-m-d");
        
        $hora_registrada = Session::get('status.hora_registrada');
        $area = Session::get('status.area');

               
        $habilitar_localizacao = Configuracao::valor('PONTO_LOCALIZACAO_HABILITAR', '0');
        $latitude_cadastrada = Configuracao::valor('PONTO_LOCALIZACAO_LATITUDE', '');
        $longitude_cadastrada = Configuracao::valor('PONTO_LOCALIZACAO_LONGITUDE', '');

        if($area == 'entrada'):
        
            $registro_ip = $this->obterIpCliente();
            $localizacao_ip = null;
            if ($habilitar_localizacao == '1') {
                $localizacao_ip = $this->obterLocalizacaoIp();
            }
            $observacoes_registro = $this->montarObservacoesRegistro($registro_ip, $localizacao_ip, $habilitar_localizacao, $latitude_cadastrada, $longitude_cadastrada);

            $ponto = new Ponto();
            $ponto->usuario_id = $usuario_id;
            $ponto->data = $hoje;
            $ponto->entrada = $hora_registrada;
            $ponto->entrada_status = 0;
            $ponto->observacoes = 'Entrada - ' . $observacoes_registro;
            $ponto->status = 0;
            $ponto->save();

            Session::put('status.msg', 'Entrada registrada com sucesso! Até breve!');
            Session::put('status.error_redirect', $url_base.'/sair');
            
        endif;
        
        if($area == 'saida'):
            
            $registro_ip = $this->obterIpCliente();
            $localizacao_ip = null;
            if ($habilitar_localizacao == '1') {
                $localizacao_ip = $this->obterLocalizacaoIp();
            }
            $observacoes_registro = $this->montarObservacoesRegistro($registro_ip, $localizacao_ip, $habilitar_localizacao, $latitude_cadastrada, $longitude_cadastrada);

            $ponto = new Ponto();
            $ponto->usuario_id = $usuario_id;
            $ponto->data = $hoje;
            $ponto->saida = $hora_registrada;
            $ponto->saida_status = 0;
            $ponto->observacoes = 'Saída - ' . $observacoes_registro;
            $ponto->status = 0;
            $ponto->save();    
            
            Session::put('status.msg', 'Saída registrada com sucesso! Até breve!');
            Session::put('status.error_redirect', $url_base.'/sair');
            
        endif;
        
        return redirect(getenv('APP_URL').'/dashboard');
        
        
    }
    
}