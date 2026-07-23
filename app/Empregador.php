<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Dados cadastrais do empregador (identificação exigida pela legislação de
 * ponto eletrônico no arquivo exportado). Tabela de registro único — sempre
 * a primeira linha (ou nenhuma, antes do primeiro cadastro).
 */
class Empregador extends Model
{
    protected $table = 'empregador';

    protected $fillable = [
        'tipo_pessoa', 'nome', 'documento', 'endereco',
        'numero', 'complemento', 'bairro', 'cidade', 'uf', 'cep',
    ];

    private static $campos = [
        'tipo_pessoa', 'nome', 'documento', 'endereco',
        'numero', 'complemento', 'bairro', 'cidade', 'uf', 'cep',
    ];

    public static function dados()
    {
        $registro = self::first();

        $dados = [];
        foreach (self::$campos as $campo) {
            $dados[$campo] = $registro ? ($registro->$campo ?? '') : ($campo === 'tipo_pessoa' ? 'juridica' : '');
        }

        return $dados;
    }

    /**
     * Retorna true/false de acordo com o sucesso real da gravação — quem
     * chama precisa checar isso e avisar o usuário se falhar (ao contrário
     * do mecanismo antigo em arquivo, que podia falhar sem ninguém notar).
     */
    public static function salvar(array $dados)
    {
        $valores = array_intersect_key($dados, array_flip(self::$campos));

        $registro = self::first();

        if ($registro) {
            $registro->fill($valores);
            return $registro->save();
        }

        return (bool) self::create($valores);
    }

    public static function cadastroCompleto()
    {
        $d = self::dados();
        return trim($d['nome']) !== '' && trim($d['documento']) !== '';
    }
}
