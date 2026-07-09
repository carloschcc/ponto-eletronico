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

        $usuario_id = Session::get('login.ponto.usuario_id');

        $hoje = Date("Y-m-d");

        $ultimo_registro = Ponto::where(['usuario_id' => $usuario_id, 'data' => $hoje])->orderBy('id', 'DESC')->first();

        $registro_entrada = $ultimo_registro->entrada ?? '';
        $registro_saida = $ultimo_registro->saida ?? '';

        $area = Request::input('area');
        $hora_registrada = Request::input('hora');

        $habilitar_localizacao = Configuracao::valor('PONTO_LOCALIZACAO_HABILITAR', '0');
        $latitude_cadastrada = Configuracao::valor('PONTO_LOCALIZACAO_LATITUDE', '');
        $longitude_cadastrada = Configuracao::valor('PONTO_LOCALIZACAO_LONGITUDE', '');
        $ips_permitidos = Configuracao::valor('PONTO_IPS_PERMITIDOS', '');

        $registro_ip = $this->obterIpCliente();

        $gps_lat_raw = Request::input('gps_latitude');
        $gps_lon_raw = Request::input('gps_longitude');
        $gps_precisao_raw = Request::input('gps_precisao');

        if (!$this->ipPermitido($registro_ip, $this->parseIpsPermitidos($ips_permitidos))) {
            Session::put('status.msg', 'Seu IP não está na lista de IPs permitidos para registrar ponto.');
            return redirect(getenv('APP_URL').'/dashboard');
        }

        [$localizacao_registro, $fonte_localizacao] = $this->resolverLocalizacao($gps_lat_raw, $gps_lon_raw, $gps_precisao_raw);
        $observacoes_registro = $this->montarObservacoesRegistro($registro_ip, $localizacao_registro, $fonte_localizacao, $habilitar_localizacao, $latitude_cadastrada, $longitude_cadastrada);

        if ($area === 'entrada') {
            // Só pede confirmação quando há uma entrada em aberto (sem saída) hoje.
            $entradaEmAberto = !empty($registro_entrada) && empty($registro_saida);

            if ($entradaEmAberto) {
                return $this->pedirConfirmacao($area, $hora_registrada, $gps_lat_raw, $gps_lon_raw, $gps_precisao_raw, 'Você está fazendo um registro de entrada sem um registro prévio de saída. Confirma?');
            }

            $this->salvarNovoRegistro($usuario_id, $hoje, 'entrada', $hora_registrada, $registro_ip, $localizacao_registro, $fonte_localizacao, $observacoes_registro);

            return $this->sucessoRegistro('Entrada registrada com sucesso! Registro por IP válido.');
        }

        if ($area === 'saida') {
            if (empty($registro_saida) && !empty($registro_entrada)) {
                $this->atualizarSaidaExistente($ultimo_registro, $hora_registrada, $registro_ip, $localizacao_registro, $fonte_localizacao, $observacoes_registro);
                return $this->sucessoRegistro('Saída registrada com sucesso! Registro por IP válido.');
            }

            if (!empty($registro_saida) && empty($registro_entrada)) {
                $this->salvarNovoRegistro($usuario_id, $hoje, 'saida', $hora_registrada, $registro_ip, $localizacao_registro, $fonte_localizacao, $observacoes_registro);
                return $this->sucessoRegistro('Saída registrada com sucesso! Registro por IP válido.');
            }

            // Dia já fechado (ambos preenchidos) ou nada em aberto (ambos vazios): exige confirmação.
            return $this->pedirConfirmacao($area, $hora_registrada, $gps_lat_raw, $gps_lon_raw, $gps_precisao_raw, 'Você está fazendo um registro de saída sem um registro prévio de entrada. Confirma?');
        }

        Session::put('status.msg', 'Falha no registro. Por favor, tente novamente.');
        return redirect(getenv('APP_URL').'/dashboard');

    }

    public function registrar(){

        $usuario_id = Session::get('login.ponto.usuario_id');
        $hoje = Date("Y-m-d");
        $hora_registrada = Session::get('status.hora_registrada');
        $area = Session::get('status.area');

        $habilitar_localizacao = Configuracao::valor('PONTO_LOCALIZACAO_HABILITAR', '0');
        $latitude_cadastrada = Configuracao::valor('PONTO_LOCALIZACAO_LATITUDE', '');
        $longitude_cadastrada = Configuracao::valor('PONTO_LOCALIZACAO_LONGITUDE', '');

        $gps_lat_raw = Session::get('status.gps_latitude');
        $gps_lon_raw = Session::get('status.gps_longitude');
        $gps_precisao_raw = Session::get('status.gps_precisao');
        Session::forget(['status.gps_latitude', 'status.gps_longitude', 'status.gps_precisao']);

        if ($area !== 'entrada' && $area !== 'saida') {
            return redirect(getenv('APP_URL').'/dashboard');
        }

        $registro_ip = $this->obterIpCliente();
        [$localizacao_registro, $fonte_localizacao] = $this->resolverLocalizacao($gps_lat_raw, $gps_lon_raw, $gps_precisao_raw);
        $observacoes_registro = $this->montarObservacoesRegistro($registro_ip, $localizacao_registro, $fonte_localizacao, $habilitar_localizacao, $latitude_cadastrada, $longitude_cadastrada);

        $this->salvarNovoRegistro($usuario_id, $hoje, $area, $hora_registrada, $registro_ip, $localizacao_registro, $fonte_localizacao, $observacoes_registro);

        $mensagem = $area === 'entrada'
            ? 'Entrada registrada com sucesso! Registro por IP válido.'
            : 'Saída registrada com sucesso! Registro por IP válido.';

        return $this->sucessoRegistro($mensagem);

    }

    private function resolverLocalizacao($gps_lat_raw, $gps_lon_raw, $gps_precisao_raw)
    {
        $localizacao_gps = $this->obterLocalizacaoGpsRequest($gps_lat_raw, $gps_lon_raw, $gps_precisao_raw);

        if ($localizacao_gps) {
            return [$localizacao_gps, 'gps'];
        }

        $localizacao_ip = $this->obterLocalizacaoIp();
        return [$localizacao_ip, $localizacao_ip ? 'ip' : null];
    }

    private function salvarNovoRegistro($usuario_id, $hoje, $area, $hora_registrada, $registro_ip, $localizacao_registro, $fonte_localizacao, $observacoes_registro)
    {
        $ponto = new Ponto();
        $ponto->usuario_id = $usuario_id;
        $ponto->data = $hoje;
        $ponto->status = 0;

        if ($area === 'entrada') {
            $ponto->entrada = $hora_registrada;
            $ponto->entrada_status = 0;
            $ponto->entrada_ip = $registro_ip;
            $ponto->entrada_latitude = $localizacao_registro['latitude'] ?? null;
            $ponto->entrada_longitude = $localizacao_registro['longitude'] ?? null;
            $ponto->entrada_geo_fonte = $fonte_localizacao;
            $ponto->observacoes = 'Entrada - ' . $observacoes_registro;
        } else {
            $ponto->saida = $hora_registrada;
            $ponto->saida_status = 0;
            $ponto->saida_ip = $registro_ip;
            $ponto->saida_latitude = $localizacao_registro['latitude'] ?? null;
            $ponto->saida_longitude = $localizacao_registro['longitude'] ?? null;
            $ponto->saida_geo_fonte = $fonte_localizacao;
            $ponto->observacoes = 'Saída - ' . $observacoes_registro;
        }

        $ponto->save();

        return $ponto;
    }

    private function atualizarSaidaExistente($ponto, $hora_registrada, $registro_ip, $localizacao_registro, $fonte_localizacao, $observacoes_registro)
    {
        $ponto->saida = $hora_registrada;
        $ponto->saida_status = 0;
        $ponto->saida_ip = $registro_ip;
        $ponto->saida_latitude = $localizacao_registro['latitude'] ?? null;
        $ponto->saida_longitude = $localizacao_registro['longitude'] ?? null;
        $ponto->saida_geo_fonte = $fonte_localizacao;

        $saida_observacoes = 'Saída - ' . $observacoes_registro;
        $ponto->observacoes = trim(($ponto->observacoes ?: '') . ' | ' . $saida_observacoes, ' |');

        $ponto->save();

        return $ponto;
    }

    private function pedirConfirmacao($area, $hora_registrada, $gps_lat_raw, $gps_lon_raw, $gps_precisao_raw, $mensagem)
    {
        $url_base = getenv('APP_URL');

        Session::put('status.hora_registrada', $hora_registrada);
        Session::put('status.area', $area);
        Session::put('status.gps_latitude', $gps_lat_raw);
        Session::put('status.gps_longitude', $gps_lon_raw);
        Session::put('status.gps_precisao', $gps_precisao_raw);

        Session::put('status.msg_confirm', $mensagem);
        Session::put('status.redir_confirm', $url_base.'/registrar');

        return redirect(getenv('APP_URL').'/dashboard');
    }

    private function sucessoRegistro($mensagem)
    {
        $url_base = getenv('APP_URL');

        Session::put('status.msg', $mensagem);
        Session::put('status.error_redirect', $url_base.'/sair');

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

}
