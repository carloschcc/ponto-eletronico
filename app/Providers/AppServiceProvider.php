<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        $tz = $this->resolverTimezoneDoNegocio();
        date_default_timezone_set($tz);
        config(['app.timezone' => $tz]);
    }

    /**
     * O horário de registro de ponto deve sempre ser o de Brasília/São Paulo,
     * independente do timezone do SO onde o PHP roda. Não detectamos mais o
     * timezone do sistema operacional: em containers Docker a imagem base
     * normalmente vem com "Etc/UTC" em /etc/timezone, que é um timezone
     * válido porém errado para o negócio, e a detecção anterior aceitava
     * esse valor achando que era uma configuração intencional do servidor.
     * Só é possível sobrepor via env APP_TIMEZONE, para casos excepcionais.
     */
    private function resolverTimezoneDoNegocio(): string
    {
        $tz = env('APP_TIMEZONE', config('app.timezone', 'America/Sao_Paulo'));

        if (!$tz || @timezone_open($tz) === false) {
            $tz = 'America/Sao_Paulo';
        }

        return $tz;
    }
}
