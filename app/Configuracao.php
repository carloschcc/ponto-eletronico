<?php

namespace App;

class Configuracao
{
    public static function valor($chave, $padrao = null)
    {
        return getenv($chave) ?: env($chave, $padrao);
    }
}
