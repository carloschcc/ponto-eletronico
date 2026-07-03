<?php namespace App\Http\Controllers\PontoEletronico;

use Illuminate\Support\Facades\DB;
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
