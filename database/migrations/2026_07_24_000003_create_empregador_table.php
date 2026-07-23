<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Antes os dados do empregador ficavam em storage/app/ponto-configuracoes.json
 * (mesmo mecanismo de app/Configuracao.php), mas o salvamento podia falhar
 * silenciosamente (ex: permissão de escrita) sem o controller perceber —
 * o formulário confirmava "sucesso" mesmo quando nada tinha sido gravado.
 * Uma tabela de verdade no banco elimina essa classe de bug.
 */
class CreateEmpregadorTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('empregador')) {
            Schema::create('empregador', function (Blueprint $table) {
                $table->increments('id');
                $table->string('tipo_pessoa', 10)->default('juridica');
                $table->string('nome', 150)->default('');
                $table->string('documento', 20)->default('');
                $table->string('endereco', 150)->default('');
                $table->string('numero', 20)->default('');
                $table->string('complemento', 100)->default('');
                $table->string('bairro', 100)->default('');
                $table->string('cidade', 100)->default('');
                $table->string('uf', 2)->default('');
                $table->string('cep', 10)->default('');
                $table->timestamps();
            });
        }

        $this->migrarDadosLegadosDoJson();
    }

    /**
     * Se havia algo salvo no JSON antigo (chaves EMPREGADOR_*), traz pra
     * tabela nova — mas só se a tabela ainda estiver vazia, pra não
     * sobrescrever um cadastro já feito através da tela nova.
     */
    private function migrarDadosLegadosDoJson()
    {
        if (DB::table('empregador')->exists()) {
            return;
        }

        $arquivo = storage_path('app/ponto-configuracoes.json');
        if (!file_exists($arquivo)) {
            return;
        }

        $config = json_decode(file_get_contents($arquivo), true);
        if (!is_array($config)) {
            return;
        }

        $campos = ['tipo_pessoa', 'nome', 'documento', 'endereco', 'numero', 'complemento', 'bairro', 'cidade', 'uf', 'cep'];
        $dados = [
            'tipo_pessoa' => 'juridica', 'nome' => '', 'documento' => '',
            'endereco' => '', 'numero' => '', 'complemento' => '',
            'bairro' => '', 'cidade' => '', 'uf' => '', 'cep' => '',
        ];
        $encontrouAlgo = false;

        foreach ($campos as $campo) {
            $chave = 'EMPREGADOR_' . strtoupper($campo);
            if (isset($config[$chave]) && $config[$chave] !== '') {
                $dados[$campo] = $config[$chave];
                $encontrouAlgo = true;
            }
        }

        if ($encontrouAlgo) {
            DB::table('empregador')->insert(array_merge($dados, [
                'created_at' => now(), 'updated_at' => now(),
            ]));
        }
    }

    public function down()
    {
        Schema::dropIfExists('empregador');
    }
}
