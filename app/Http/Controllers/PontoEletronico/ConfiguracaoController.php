<?php namespace App\Http\Controllers\PontoEletronico;

use App\Configuracao;
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
        if (!$this->painelEhAdmin()) {
            return redirect(getenv('APP_URL').'/painel/dashboard');
        }

        $logo_url              = getenv('APP_URL') . '/img/ilab4_logo_pontoeletronico.png';
        $logo_espelho_url      = getenv('APP_URL') . '/img/logo_espelho_v2.png';
        $logo_espelho_existe   = file_exists(public_path('img/logo_espelho_v2.png'));
        $timezone_atual        = date_default_timezone_get();
        $hora_atual            = date('d/m/Y H:i:s');
        $configuracao_localizacao = Configuracao::valor('PONTO_LOCALIZACAO_HABILITAR', '0');
        $localizacao_latitude = Configuracao::valor('PONTO_LOCALIZACAO_LATITUDE', '');
        $localizacao_longitude = Configuracao::valor('PONTO_LOCALIZACAO_LONGITUDE', '');
        $localizacao_raio = Configuracao::valor('PONTO_LOCALIZACAO_RAIO', '50');
        $ips_permitidos = Configuracao::valor('PONTO_IPS_PERMITIDOS', '');
        $nome_sistema = Configuracao::valor('NOME_SISTEMA', 'Ponto EletrÃ´nico');

        return view('pontoeletronico/configuracao/index', compact(
            'logo_url', 'logo_espelho_url', 'logo_espelho_existe',
            'timezone_atual', 'hora_atual', 'configuracao_localizacao',
            'localizacao_latitude', 'localizacao_longitude', 'localizacao_raio',
            'ips_permitidos', 'nome_sistema'
        ));
    }

    public function salvarLocalizacao()
    {
        if (!$this->painelEhAdmin()) {
            return redirect(getenv('APP_URL').'/painel/dashboard');
        }

        $habilitar = trim(Request::input('habilitar_localizacao', '0'));
        $latitude = trim(Request::input('latitude', ''));
        $longitude = trim(Request::input('longitude', ''));
        $raio = trim(Request::input('raio', '50'));

        $sucesso = true;
        $sucesso = $this->persistirValorEnv('PONTO_LOCALIZACAO_HABILITAR', $habilitar) && $sucesso;
        $sucesso = $this->persistirValorEnv('PONTO_LOCALIZACAO_LATITUDE', $latitude) && $sucesso;
        $sucesso = $this->persistirValorEnv('PONTO_LOCALIZACAO_LONGITUDE', $longitude) && $sucesso;
        $sucesso = $this->persistirValorEnv('PONTO_LOCALIZACAO_RAIO', $raio) && $sucesso;
        $sucesso = $this->persistirValorEnv('PONTO_IPS_PERMITIDOS', trim(Request::input('ips_permitidos', ''))) && $sucesso;

        if (!$sucesso) {
            Session::put('status.msg', 'Falha ao gravar a configuraÃ§Ã£o em disco. Verifique as permissÃµes de escrita da pasta storage/app.');
            return redirect(getenv('APP_URL').'/painel/configuracao');
        }

        Session::put('status.msg', 'ConfiguraÃ§Ã£o de localizaÃ§Ã£o e IPs permitidos atualizada com sucesso!');
        return redirect(getenv('APP_URL').'/painel/configuracao');
    }

    public function salvarNome()
    {
        if (!$this->painelEhAdmin()) {
            return redirect(getenv('APP_URL').'/painel/dashboard');
        }

        $nome = trim(Request::input('nome_sistema', ''));

        if ($nome === '') {
            Session::put('status.msg', 'Informe um nome para o sistema.');
            return redirect(getenv('APP_URL').'/painel/configuracao');
        }

        $this->persistirValorEnv('NOME_SISTEMA', $nome);

        Session::put('status.msg', 'Nome do sistema atualizado com sucesso!');
        return redirect(getenv('APP_URL').'/painel/configuracao');
    }

    private function persistirValorEnv($chave, $valor)
    {
        return \App\Configuracao::salvar($chave, $valor);
    }

    /**
     * Confere se o arquivo enviado Ã©, de fato, uma imagem rasterizada vÃ¡lida
     * (JPG/PNG/GIF) â€” nÃ£o sÃ³ confia na extensÃ£o do nome do arquivo.
     */
    private function _validarImagem($arquivo)
    {
        $ext = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'gif'])) {
            return 'Formato invÃ¡lido. Use PNG, JPG ou GIF.';
        }

        $infoImagem = @getimagesize($arquivo['tmp_name']);
        $tiposValidos = ['image/jpeg', 'image/png', 'image/gif'];
        if ($infoImagem === false || !in_array($infoImagem['mime'], $tiposValidos)) {
            return 'Arquivo invÃ¡lido: o conteÃºdo nÃ£o corresponde a uma imagem vÃ¡lida.';
        }

        return null;
    }

    public function salvarLogo()
    {
        if (!$this->painelEhAdmin()) {
            return redirect(getenv('APP_URL').'/painel/dashboard');
        }

        if (!isset($_FILES['logo']) || empty($_FILES['logo']['name'])) {
            Session::put('status.msg', 'Nenhum arquivo enviado.');
            return redirect(getenv('APP_URL').'/painel/configuracao');
        }

        $arquivo = $_FILES['logo'];
        $erro = $this->_validarImagem($arquivo);
        if ($erro) {
            Session::put('status.msg', $erro);
            return redirect(getenv('APP_URL').'/painel/configuracao');
        }

        $destino = public_path('img/ilab4_logo_pontoeletronico.png');

        if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
            Session::put('status.msg', 'Falha ao salvar o arquivo. Verifique as permissÃµes da pasta public/img.');
            return redirect(getenv('APP_URL').'/painel/configuracao');
        }

        Session::put('status.msg', 'Logo atualizada com sucesso!');
        return redirect(getenv('APP_URL').'/painel/configuracao');
    }

    public function salvarLogoEspelho()
    {
        if (!$this->painelEhAdmin()) {
            return redirect(getenv('APP_URL').'/painel/dashboard');
        }

        if (!isset($_FILES['logo_espelho']) || empty($_FILES['logo_espelho']['name'])) {
            Session::put('status.msg', 'Nenhum arquivo enviado.');
            return redirect(getenv('APP_URL').'/painel/configuracao');
        }

        $arquivo = $_FILES['logo_espelho'];
        $erro = $this->_validarImagem($arquivo);
        if ($erro) {
            Session::put('status.msg', $erro);
            return redirect(getenv('APP_URL').'/painel/configuracao');
        }

        $destino = public_path('img/logo_espelho_v2.png');

        if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
            Session::put('status.msg', 'Falha ao salvar. Verifique as permissÃµes da pasta public/img.');
            return redirect(getenv('APP_URL').'/painel/configuracao');
        }

        Session::put('status.msg', 'Logo do Espelho V2 atualizada com sucesso!');
        return redirect(getenv('APP_URL').'/painel/configuracao');
    }

}

