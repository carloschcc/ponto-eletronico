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
        $tz = $this->detectarTimezoneDoSistema();
        date_default_timezone_set($tz);
        config(['app.timezone' => $tz]);
    }

    private function detectarTimezoneDoSistema(): string
    {
        // Debian/Ubuntu: /etc/timezone contém "America/Sao_Paulo"
        if (file_exists('/etc/timezone')) {
            $tz = trim(file_get_contents('/etc/timezone'));
            if ($tz !== '' && @timezone_open($tz) !== false) {
                return $tz;
            }
        }

        // RedHat/CentOS/Alpine: /etc/localtime é symlink para /usr/share/zoneinfo/...
        if (is_link('/etc/localtime')) {
            $link = readlink('/etc/localtime');
            if (preg_match('#zoneinfo/(.+)$#', $link, $m)) {
                $tz = $m[1];
                if (@timezone_open($tz) !== false) {
                    return $tz;
                }
            }
        }

        // Fallback garantido: São Paulo (UTC-3)
        return 'America/Sao_Paulo';
    }
}
