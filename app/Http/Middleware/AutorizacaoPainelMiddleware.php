<?php

namespace App\Http\Middleware;

use Closure;
use Request;
use Session;

class AutorizacaoPainelMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        $usuario_logado = Session::get('login.ponto.painel.usuario_id');
        $url_base = getenv('APP_URL').'/painel';

        if(empty($usuario_logado)){
            return redirect($url_base);
        }

        // O painel aceita qualquer colaborador autenticado — é onde ele
        // solicita ajuste da própria batida. As telas de gestão (Colaboradores,
        // Períodos, Configurações, Acompanhamento, aprovação de ajustes) são
        // restritas a admin/gerente/RH em cada controller (painelAcessoTotal()/
        // painelPodeCertificar()), não aqui.
        return $next($request);
    }
}
