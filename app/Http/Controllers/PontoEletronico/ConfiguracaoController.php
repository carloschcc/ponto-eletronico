<?php namespace App\Http\Controllers\PontoEletronico;

use App\Http\Controllers\Controller;
use Request;
use Session;

class ConfiguracaoController extends PontoEletronicoController {

    public function __construct()
    {
        $this->middleware('authPainelMiddleware');
    }

    public function index()
    {
        $admin = Session::get('login.ponto.painel.admin');
        if ($admin != 1) {
            return redirect(getenv('APP_URL').'/painel/dashboard');
        }

        $logo_url              = getenv('APP_URL') . '/img/ilab4_logo_pontoeletronico.png';
        $logo_espelho_url      = getenv('APP_URL') . '/img/logo_espelho_v2.png';
        $logo_espelho_existe   = file_exists(public_path('img/logo_espelho_v2.png'));
        $timezone_atual        = date_default_timezone_get();
        $hora_atual            = date('d/m/Y H:i:s');
        $configuracao_localizacao = getenv('PONTO_LOCALIZACAO_HABILITAR') ?: env('PONTO_LOCALIZACAO_HABILITAR', '0');
        $localizacao_latitude = getenv('PONTO_LOCALIZACAO_LATITUDE') ?: env('PONTO_LOCALIZACAO_LATITUDE', '');
        $localizacao_longitude = getenv('PONTO_LOCALIZACAO_LONGITUDE') ?: env('PONTO_LOCALIZACAO_LONGITUDE', '');
        $localizacao_raio = getenv('PONTO_LOCALIZACAO_RAIO') ?: env('PONTO_LOCALIZACAO_RAIO', '50');

        return view('pontoeletronico/configuracao/index', compact(
            'logo_url', 'logo_espelho_url', 'logo_espelho_existe',
            'timezone_atual', 'hora_atual', 'configuracao_localizacao',
            'localizacao_latitude', 'localizacao_longitude', 'localizacao_raio'
        ));
    }

    public function salvarLocalizacao()
    {
        $admin = Session::get('login.ponto.painel.admin');
        if ($admin != 1) {
            return redirect(getenv('APP_URL').'/painel/dashboard');
        }

        $habilitar = trim(Request::input('habilitar_localizacao', '0'));
        $latitude = trim(Request::input('latitude', ''));
        $longitude = trim(Request::input('longitude', ''));
        $raio = trim(Request::input('raio', '50'));

        $this->persistirValorEnv('PONTO_LOCALIZACAO_HABILITAR', $habilitar);
        $this->persistirValorEnv('PONTO_LOCALIZACAO_LATITUDE', $latitude);
        $this->persistirValorEnv('PONTO_LOCALIZACAO_LONGITUDE', $longitude);
        $this->persistirValorEnv('PONTO_LOCALIZACAO_RAIO', $raio);

        Session::put('status.msg', 'Configuração de localização atualizada com sucesso!');
        return redirect(getenv('APP_URL').'/painel/configuracao');
    }

    private function persistirValorEnv($chave, $valor)
    {
        $caminhoEnv = base_path('.env');
        $arquivoExiste = file_exists($caminhoEnv);

        if (!$arquivoExiste) {
            $modelo = file_exists(base_path('.env.example')) ? base_path('.env.example') : null;
            if ($modelo) {
                copy($modelo, $caminhoEnv);
            } else {
                file_put_contents($caminhoEnv, "");
            }
        }

        $conteudo = file_get_contents($caminhoEnv);
        $linhas = preg_split("/\r\n|\n|\r/", $conteudo);
        $encontrado = false;

        foreach ($linhas as &$linha) {
            if (preg_match('/^' . preg_quote($chave, '/') . '=/u', $linha)) {
                $linha = $chave . '=' . $valor;
                $encontrado = true;
                break;
            }
        }

        if (!$encontrado) {
            $linhas[] = $chave . '=' . $valor;
        }

        file_put_contents($caminhoEnv, implode(PHP_EOL, $linhas) . PHP_EOL);
        putenv($chave . '=' . $valor);
        $_ENV[$chave] = $valor;
        $_SERVER[$chave] = $valor;
    }

    public function salvarLogo()
    {
        $admin = Session::get('login.ponto.painel.admin');
        if ($admin != 1) {
            return redirect(getenv('APP_URL').'/painel/dashboard');
        }

        if (!isset($_FILES['logo']) || empty($_FILES['logo']['name'])) {
            Session::put('status.msg', 'Nenhum arquivo enviado.');
            return redirect(getenv('APP_URL').'/painel/configuracao');
        }

        $arquivo = $_FILES['logo'];
        $ext     = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'svg'])) {
            Session::put('status.msg', 'Formato inválido. Use PNG, JPG, GIF ou SVG.');
            return redirect(getenv('APP_URL').'/painel/configuracao');
        }

        $destino = public_path('img/ilab4_logo_pontoeletronico.png');

        if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
            Session::put('status.msg', 'Falha ao salvar o arquivo. Verifique as permissões da pasta public/img.');
            return redirect(getenv('APP_URL').'/painel/configuracao');
        }

        Session::put('status.msg', 'Logo atualizada com sucesso!');
        return redirect(getenv('APP_URL').'/painel/configuracao');
    }

    public function salvarLogoEspelho()
    {
        $admin = Session::get('login.ponto.painel.admin');
        if ($admin != 1) {
            return redirect(getenv('APP_URL').'/painel/dashboard');
        }

        if (!isset($_FILES['logo_espelho']) || empty($_FILES['logo_espelho']['name'])) {
            Session::put('status.msg', 'Nenhum arquivo enviado.');
            return redirect(getenv('APP_URL').'/painel/configuracao');
        }

        $arquivo = $_FILES['logo_espelho'];
        $ext     = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'svg'])) {
            Session::put('status.msg', 'Formato inválido. Use PNG, JPG, GIF ou SVG.');
            return redirect(getenv('APP_URL').'/painel/configuracao');
        }

        $destino = public_path('img/logo_espelho_v2.png');

        if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
            Session::put('status.msg', 'Falha ao salvar. Verifique as permissões da pasta public/img.');
            return redirect(getenv('APP_URL').'/painel/configuracao');
        }

        Session::put('status.msg', 'Logo do Espelho V2 atualizada com sucesso!');
        return redirect(getenv('APP_URL').'/painel/configuracao');
    }

}
