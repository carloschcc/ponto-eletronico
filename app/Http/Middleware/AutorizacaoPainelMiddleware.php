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
        if(empty($usuario_logado)){
            $url_base = getenv('APP_URL').'/painel';
            return redirect($url_base);
        }

        return $next($request);
    }
}
