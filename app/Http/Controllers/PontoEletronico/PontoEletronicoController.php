<?php namespace App\Http\Controllers\PontoEletronico;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests;
use App\Http\Controllers\Controller;

use Request;
use Session;
use App\PontoAjuste;

abstract class PontoEletronicoController extends Controller
{

    public function upload($local, $arquivo){

        if(empty($arquivo['name']) || empty($arquivo['tmp_name'])):
            return false;
        endif;

        if(!is_uploaded_file($arquivo['tmp_name'])):
            return false;
        endif;

        if(isset($arquivo['error']) && $arquivo['error'] !== UPLOAD_ERR_OK):
            return false;
        endif;

        $extensoes_permitidas = array('jpg', 'jpeg', 'png', 'pdf');

        $arquivo_nome = $arquivo['name'];
        $extensao = strtolower(pathinfo($arquivo_nome, PATHINFO_EXTENSION));

        if(!in_array($extensao, $extensoes_permitidas)):
            return false;
        endif;

        // Garante que o diretório destino existe
        if(!is_dir($local)):
            mkdir($local, 0755, true);
        endif;

        $nome_final = md5(uniqid(rand())).'.'.$extensao;

        if(move_uploaded_file($arquivo['tmp_name'], $local . $nome_final)):
            return $nome_final;
        else:
            return false;
        endif;

    }

    protected function obterIpCliente()
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

    protected function obterLocalizacaoIp()
    {
        $ip = $this->obterIpCliente();
        if (!$ip) {
            Log::warning('Geolocalização por IP: IP do cliente indisponível ou privado (não é possível geolocalizar IPs de rede local).');
            return null;
        }

        $localizacao = $this->consultarIpapiCo($ip);
        if ($localizacao) {
            return $localizacao;
        }

        $localizacao = $this->consultarIpApiCom($ip);
        if ($localizacao) {
            return $localizacao;
        }

        Log::warning("Geolocalização por IP: nenhum provedor retornou latitude/longitude para o IP {$ip}.");
        return null;
    }

    private function httpGetJson($url)
    {
        if (function_exists('curl_version')) {
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($curl, CURLOPT_TIMEOUT, 5);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($curl, CURLOPT_USERAGENT, 'ponto-eletronico');
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            $resultado = curl_exec($curl);
            $erro = curl_error($curl);
            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($resultado !== false && $status >= 200 && $status < 300) {
                return $resultado;
            }

            Log::warning("Geolocalização por IP: falha ao consultar {$url} via cURL (status={$status}, erro={$erro}).");
        }

        if (ini_get('allow_url_fopen')) {
            $contexto = stream_context_create([
                'http' => ['timeout' => 5, 'header' => "User-Agent: ponto-eletronico\r\n"],
                'ssl'  => ['timeout' => 5],
            ]);
            $resultado = @file_get_contents($url, false, $contexto);
            if ($resultado !== false) {
                return $resultado;
            }
            $erro = error_get_last();
            Log::warning("Geolocalização por IP: falha ao consultar {$url} via file_get_contents (" . ($erro['message'] ?? 'erro desconhecido') . ').');
        }

        return null;
    }

    private function consultarIpapiCo($ip)
    {
        $resultado = $this->httpGetJson('https://ipapi.co/' . $ip . '/json/');
        if (!$resultado) {
            return null;
        }

        $dados = json_decode($resultado, true);
        if (!is_array($dados) || empty($dados['latitude']) || empty($dados['longitude'])) {
            return null;
        }

        return [
            'latitude' => $this->formatarCoordenada($dados['latitude']),
            'longitude' => $this->formatarCoordenada($dados['longitude']),
        ];
    }

    private function consultarIpApiCom($ip)
    {
        $resultado = $this->httpGetJson('http://ip-api.com/json/' . $ip . '?fields=status,lat,lon');
        if (!$resultado) {
            return null;
        }

        $dados = json_decode($resultado, true);
        if (!is_array($dados) || ($dados['status'] ?? null) !== 'success' || empty($dados['lat']) || empty($dados['lon'])) {
            return null;
        }

        return [
            'latitude' => $this->formatarCoordenada($dados['lat']),
            'longitude' => $this->formatarCoordenada($dados['lon']),
        ];
    }

    /**
     * Converte a coordenada para string com ponto decimal fixo, sem depender do
     * cast implícito de float para string (que segue o locale do servidor e pode
     * trocar o "." por "," em PT-BR, corrompendo o valor salvo). 7 casas decimais
     * cobre toda a precisão que os provedores de geolocalização por IP oferecem.
     */
    protected function formatarCoordenada($valor)
    {
        return sprintf('%.7F', (float) $valor);
    }

    /**
     * Valida e formata a latitude/longitude reais do dispositivo, enviadas pelo
     * navegador via HTML5 Geolocation (muito mais precisas que a estimativa por
     * IP). Retorna null se os valores não vierem, forem inválidos ou fora do
     * intervalo geográfico possível — nesse caso o chamador deve cair para a
     * localização por IP.
     */
    protected function obterLocalizacaoGpsRequest($latitude, $longitude, $precisao = null)
    {
        if ($latitude === null || $longitude === null || $latitude === '' || $longitude === '') {
            return null;
        }

        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            return null;
        }

        $lat = (float) $latitude;
        $lon = (float) $longitude;

        if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
            return null;
        }

        $resultado = [
            'latitude' => $this->formatarCoordenada($lat),
            'longitude' => $this->formatarCoordenada($lon),
            'precisao' => null,
        ];

        if ($precisao !== null && $precisao !== '' && is_numeric($precisao)) {
            $resultado['precisao'] = (int) round((float) $precisao);
        }

        return $resultado;
    }

    protected function parseIpsPermitidos($valor)
    {
        $entradas = preg_split('/[\r\n,;]+/', (string) $valor);
        $ips = [];

        foreach ($entradas as $entrada) {
            $entrada = trim($entrada);
            if ($entrada === '') {
                continue;
            }

            if (strpos($entrada, '/') !== false) {
                $ips[] = $entrada;
                continue;
            }

            if (filter_var($entrada, FILTER_VALIDATE_IP)) {
                $ips[] = $entrada;
            }
        }

        return $ips;
    }

    protected function ipEmCidr($ip, $cidr)
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        list($range, $netmask) = explode('/', $cidr, 2);
        if (!filter_var($range, FILTER_VALIDATE_IP) || !is_numeric($netmask)) {
            return false;
        }

        $netmask = (int) $netmask;
        if ($netmask < 0 || $netmask > 32) {
            return false;
        }

        $ipLong = ip2long($ip);
        $rangeLong = ip2long($range);
        $mask = -1 << (32 - $netmask);

        return ($ipLong & $mask) === ($rangeLong & $mask);
    }

    protected function ipPermitido($ip, array $permitidos)
    {
        if (empty($permitidos)) {
            return true;
        }

        if (!$ip || !filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        foreach ($permitidos as $entrada) {
            if (strpos($entrada, '/') !== false) {
                if ($this->ipEmCidr($ip, $entrada)) {
                    return true;
                }
                continue;
            }

            if ($ip === $entrada) {
                return true;
            }
        }

        return false;
    }

}
