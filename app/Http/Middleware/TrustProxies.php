<?php

namespace App\Http\Middleware;

use Fideloper\Proxy\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * O container "app" só recebe tráfego do container "caddy" (o Apache não
     * é exposto diretamente ao host/internet — só o Caddy publica 80/443).
     * Por isso é seguro confiar em qualquer proxy aqui: sem isso, o Laravel
     * não enxerga que a requisição chegou por HTTPS (o Caddy fala HTTP com o
     * Apache internamente) e recursos como cookie de sessão segura quebram.
     *
     * @var array|string
     */
    protected $proxies = '*';

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers = Request::HEADER_X_FORWARDED_ALL;
}
