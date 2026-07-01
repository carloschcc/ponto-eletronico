<?php

namespace App\Http\Middleware;

use Closure;
use Request;
use Session;

class AutorizacaoMiddleware
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
        
        $usuario_logado = Session::get('login.ponto.usuario_id');
        if(empty($usuario_logado)){
            $url_base = getenv('APP_URL');
            return redirect($url_base);
        }

        return $next($request);
    }
}
