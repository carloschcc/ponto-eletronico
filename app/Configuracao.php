<?php

namespace App;

class Configuracao
{
    private static $arquivoConfig = null;

    public static function valor($chave, $padrao = null)
    {
        $valor = getenv($chave);
        if ($valor !== false && $valor !== null && $valor !== '') {
            return $valor;
        }

        $config = self::carregarConfig();
        if (isset($config[$chave])) {
            return $config[$chave];
        }

        return env($chave, $padrao);
    }

    public static function salvar($chave, $valor)
    {
        $config = self::carregarConfig();
        $config[$chave] = (string) $valor;
        self::gravarConfig($config);

        putenv($chave . '=' . $valor);
        $_ENV[$chave] = $valor;
        $_SERVER[$chave] = $valor;

        return true;
    }

    private static function carregarConfig()
    {
        $arquivo = self::obterCaminhoArquivo();
        if (!file_exists($arquivo)) {
            return [];
        }

        $conteudo = file_get_contents($arquivo);
        if ($conteudo === false || trim($conteudo) === '') {
            return [];
        }

        $array = json_decode($conteudo, true);
        return is_array($array) ? $array : [];
    }

    private static function gravarConfig($config)
    {
        $arquivo = self::obterCaminhoArquivo();
        $diretorio = dirname($arquivo);

        if (!is_dir($diretorio)) {
            @mkdir($diretorio, 0755, true);
        }

        $conteudo = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        @file_put_contents($arquivo, $conteudo);
    }

    private static function obterCaminhoArquivo()
    {
        if (self::$arquivoConfig !== null) {
            return self::$arquivoConfig;
        }

        self::$arquivoConfig = storage_path('app/ponto-configuracoes.json');
        return self::$arquivoConfig;
    }
}
