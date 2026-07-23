<?php namespace App\Http\Controllers\PontoEletronico;


use Illuminate\Support\Facades\DB;
use App\Http\Requests;
use App\Http\Controllers\Controller;

use Request;
use Session;
use App\Usuario;

class LoginPainelController extends PontoEletronicoController {

    public function __construct()
    {
        $this->middleware('authPainelMiddleware', ['except' => ['login']]);

    }

    public function login(){

        $cpf = Request::input('cpf');
        $senha = Request::input('senha');

        $cpf = str_replace(".", "", $cpf);
        $cpf = str_replace("-", "", $cpf);

        if(empty($cpf) OR empty($senha)):
            return redirect(getenv('APP_URL').'/painel');
        endif;

        // O painel recebe qualquer colaborador autenticado: administradores,
        // gerentes e RH têm telas de gestão extras; um colaborador comum só
        // vê o próprio dashboard e a tela de solicitação de ajuste — o
        // controller de cada tela decide o que mostrar/permitir conforme o
        // papel (ver painelAcessoTotal()/painelPodeCertificar()).
        $login = Usuario::where('cpf', $cpf)->first();

        if($login AND $login->autenticar($senha)):

            // Só revela "desabilitado" depois de confirmar a senha — evita
            // que alguém sem a senha descubra se um CPF está ativo ou não.
            if($login->ativo != 1):
                $erro = "Usuário desabilitado. Entre em contato com o RH.";
                Session::put('status.msg', $erro);
                return redirect(getenv('APP_URL').'/painel');
            endif;

            Session::put('login.ponto.painel.usuario_id', $login->id);
            Session::put('login.ponto.painel.admin', $login->admin);
            Session::put('login.ponto.painel.gerente', $login->gerente);
            Session::put('login.ponto.painel.rh', $login->rh);
            Session::put('login.ponto.painel.usuario_nome', $login->nome);

            return redirect(getenv('APP_URL').'/painel/dashboard');

        else:

            $erro = "Dados inválidos, tente novamente!";
            Session::put('status.msg', $erro);

            return redirect(getenv('APP_URL').'/painel');

        endif;


    }


    public function sair(){

        Session::forget('login.ponto.painel.usuario_id');
        Session::forget('login.ponto.painel.admin');
        Session::forget('login.ponto.painel.gerente');
        Session::forget('login.ponto.painel.rh');
        Session::forget('login.ponto.painel.usuario_nome');

        return redirect(getenv('APP_URL').'/painel');

    }

}