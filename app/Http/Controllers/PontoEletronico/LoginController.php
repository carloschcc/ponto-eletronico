<?php namespace App\Http\Controllers\PontoEletronico;


use Illuminate\Support\Facades\DB;
use App\Http\Requests;
use App\Http\Controllers\Controller;

use Request;
use Session;
use App\Usuario;

class LoginController extends PontoEletronicoController {


    public function __construct()
    {
        $this->middleware('authMiddleware', ['except' => ['login']]);

    }


    public function login(){

        $cpf = Request::input('cpf');
        $senha = Request::input('senha');

        $cpf = str_replace(".", "", $cpf);
        $cpf = str_replace("-", "", $cpf);

        if(empty($cpf) OR empty($senha)):
            return redirect(getenv('APP_URL'));
        endif;

        $login = Usuario::where(['cpf' => $cpf, 'admin' => 0])->first();

        if($login AND $login->autenticar($senha)):

            // Só revela "desabilitado" depois de confirmar a senha — evita
            // que alguém sem a senha descubra se um CPF está ativo ou não.
            if($login->ativo != 1):
                $erro = "Usuário desabilitado. Entre em contato com o RH.";
                Session::put('status.msg', $erro);
                return redirect(getenv('APP_URL'));
            endif;

            Session::put('login.ponto.usuario_id', $login->id);
            Session::put('login.ponto.admin', $login->admin);
            Session::put('login.ponto.usuario_nome', $login->nome);

            return redirect(getenv('APP_URL').'/dashboard');

        else:

            $erro = "Dados inválidos, tente novamente!";
            Session::put('status.msg', $erro);

            return redirect(getenv('APP_URL'));

        endif;


    }


    public function sair(){

        Session::forget('login.ponto.usuario_id');
        Session::forget('login.ponto.admin');
        Session::forget('login.ponto.usuario_nome');

        return redirect(getenv('APP_URL'));

    }

}