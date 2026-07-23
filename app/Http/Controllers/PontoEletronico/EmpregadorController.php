<?php namespace App\Http\Controllers\PontoEletronico;

use App\Empregador;
use Request;
use Session;

/**
 * Dados cadastrais do empregador — exigidos pela legislação de ponto
 * eletrônico (identificação do empregador no arquivo exportado).
 */
class EmpregadorController extends PontoEletronicoController {

    public function __construct()
    {
        $this->middleware('authPainelMiddleware');
    }

    public function index()
    {
        if (!$this->painelAcessoTotal()) {
            return redirect(getenv('APP_URL').'/painel/dashboard');
        }

        return view('pontoeletronico/empregador/index', Empregador::dados());
    }

    public function salvar()
    {
        if (!$this->painelAcessoTotal()) {
            return redirect(getenv('APP_URL').'/painel/dashboard');
        }

        $tipo_pessoa = Request::input('tipo_pessoa') === 'fisica' ? 'fisica' : 'juridica';
        $nome        = trim(Request::input('nome', ''));
        $documento   = preg_replace('/\D/', '', Request::input('documento', ''));
        $endereco    = trim(Request::input('endereco', ''));
        $numero      = trim(Request::input('numero', ''));
        $complemento = trim(Request::input('complemento', ''));
        $bairro      = trim(Request::input('bairro', ''));
        $cidade      = trim(Request::input('cidade', ''));
        $uf          = strtoupper(trim(Request::input('uf', '')));
        $cep         = preg_replace('/\D/', '', Request::input('cep', ''));

        if ($nome === '') {
            Session::put('status.msg', 'Informe o nome / razão social do empregador.');
            return redirect(getenv('APP_URL').'/painel/empregador');
        }

        if ($tipo_pessoa === 'juridica' && !$this->_cnpjValido($documento)) {
            Session::put('status.msg', 'CNPJ inválido. Confira os números digitados.');
            return redirect(getenv('APP_URL').'/painel/empregador');
        }

        if ($tipo_pessoa === 'fisica' && !$this->_cpfValido($documento)) {
            Session::put('status.msg', 'CPF inválido. Confira os números digitados.');
            return redirect(getenv('APP_URL').'/painel/empregador');
        }

        if ($uf !== '' && strlen($uf) !== 2) {
            Session::put('status.msg', 'UF deve ter 2 letras.');
            return redirect(getenv('APP_URL').'/painel/empregador');
        }

        $ok = Empregador::salvar([
            'tipo_pessoa' => $tipo_pessoa,
            'nome'        => $nome,
            'documento'   => $documento,
            'endereco'    => $endereco,
            'numero'      => $numero,
            'complemento' => $complemento,
            'bairro'      => $bairro,
            'cidade'      => $cidade,
            'uf'          => $uf,
            'cep'         => $cep,
        ]);

        if (!$ok) {
            Session::put('status.msg', 'Falha ao gravar os dados do empregador no banco. Tente novamente.');
            return redirect(getenv('APP_URL').'/painel/empregador');
        }

        Session::put('status.msg', 'Dados do empregador atualizados com sucesso!');
        return redirect(getenv('APP_URL').'/painel/empregador');
    }

    private function _cpfValido($cpf)
    {
        if (strlen($cpf) != 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            $soma = 0;
            for ($c = 0; $c < $t; $c++) {
                $soma += $cpf[$c] * (($t + 1) - $c);
            }
            $digito = ((10 * $soma) % 11) % 10;
            if ((int) $cpf[$t] !== $digito) {
                return false;
            }
        }

        return true;
    }

    private function _cnpjValido($cnpj)
    {
        if (strlen($cnpj) != 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        $pesos1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $pesos2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        $soma = 0;
        for ($i = 0; $i < 12; $i++) {
            $soma += $cnpj[$i] * $pesos1[$i];
        }
        $resto = $soma % 11;
        $dv1 = $resto < 2 ? 0 : 11 - $resto;
        if ((int) $cnpj[12] !== $dv1) {
            return false;
        }

        $soma = 0;
        for ($i = 0; $i < 13; $i++) {
            $soma += $cnpj[$i] * $pesos2[$i];
        }
        $resto = $soma % 11;
        $dv2 = $resto < 2 ? 0 : 11 - $resto;
        if ((int) $cnpj[13] !== $dv2) {
            return false;
        }

        return true;
    }

}
