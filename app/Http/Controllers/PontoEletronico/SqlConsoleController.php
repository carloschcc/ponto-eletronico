<?php namespace App\Http\Controllers\PontoEletronico;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDO;
use PDOException;
use Request;
use Session;

/**
 * Console SQL do painel — acesso direto de leitura/escrita ao banco, restrito
 * a administradores "de verdade" (nem RH nem gerente). Só aceita um único
 * comando SELECT/INSERT/UPDATE/DELETE por execução, sem parametrização: o
 * texto digitado é executado como está, então qualquer validação aqui é uma
 * rede de segurança contra erro de digitação/instrumento errado, não uma
 * barreira contra o próprio admin.
 */
class SqlConsoleController extends PontoEletronicoController {

    private $comandosPermitidos = ['SELECT', 'INSERT', 'UPDATE', 'DELETE'];

    private $palavrasProibidas = [
        'DROP', 'TRUNCATE', 'ALTER', 'CREATE', 'RENAME', 'GRANT', 'REVOKE',
        'LOAD_FILE', 'OUTFILE', 'DUMPFILE', 'CALL', 'EXECUTE', 'PREPARE', 'SHUTDOWN',
    ];

    private $limiteLinhasExibidas = 1000;

    public function __construct()
    {
        $this->middleware('authPainelMiddleware');
    }

    private function _bloqueiaSemAcesso()
    {
        if(Session::get('login.ponto.painel.admin') != 1):
            Session::put('status.msg', 'Console SQL restrito a administradores.');
            return redirect(getenv('APP_URL').'/painel/dashboard');
        endif;
        return null;
    }

    public function index()
    {
        if($redir = $this->_bloqueiaSemAcesso()) return $redir;

        return view('pontoeletronico/sqlconsole/index')
            ->with('tabelas', $this->_listarTabelas())
            ->with('sql', '')
            ->with('resultado', null)
            ->with('erro', null);
    }

    public function executar()
    {
        if($redir = $this->_bloqueiaSemAcesso()) return $redir;

        $sql = trim((string) Request::input('sql', ''));
        $tabelas = $this->_listarTabelas();

        if($sql === ''):
            return view('pontoeletronico/sqlconsole/index')
                ->with('tabelas', $tabelas)
                ->with('sql', $sql)
                ->with('resultado', null)
                ->with('erro', 'Informe um comando SQL.');
        endif;

        $comando = $this->_validarComando($sql);

        if(is_string($comando)):
            return view('pontoeletronico/sqlconsole/index')
                ->with('tabelas', $tabelas)
                ->with('sql', $sql)
                ->with('resultado', null)
                ->with('erro', $comando);
        endif;

        $usuario_id   = Session::get('login.ponto.painel.usuario_id');
        $usuario_nome = Session::get('login.ponto.painel.usuario_nome');

        try {

            $pdo = DB::connection()->getPdo();

            if($comando['tipo'] === 'SELECT'):

                Log::info("Console SQL [SELECT] usuario #{$usuario_id} ({$usuario_nome}): {$comando['sql']}");

                $inicio = microtime(true);
                $stmt   = $pdo->query($comando['sql']);
                $linhas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $tempo_ms = round((microtime(true) - $inicio) * 1000);

                $total    = count($linhas);
                $truncado = $total > $this->limiteLinhasExibidas;
                $colunas  = $total > 0 ? array_keys($linhas[0]) : [];

                $resultado = [
                    'tipo'     => 'select',
                    'colunas'  => $colunas,
                    'linhas'   => $truncado ? array_slice($linhas, 0, $this->limiteLinhasExibidas) : $linhas,
                    'total'    => $total,
                    'truncado' => $truncado,
                    'tempo_ms' => $tempo_ms,
                ];

            else:

                // INSERT/UPDATE/DELETE alteram dados de produção imediatamente.
                Log::warning("Console SQL [{$comando['tipo']}] usuario #{$usuario_id} ({$usuario_nome}): {$comando['sql']}");

                $afetadas = $pdo->exec($comando['sql']);

                $resultado = [
                    'tipo'     => strtolower($comando['tipo']),
                    'afetadas' => $afetadas,
                ];

            endif;

            return view('pontoeletronico/sqlconsole/index')
                ->with('tabelas', $tabelas)
                ->with('sql', $sql)
                ->with('resultado', $resultado)
                ->with('erro', null);

        } catch (PDOException $e) {

            return view('pontoeletronico/sqlconsole/index')
                ->with('tabelas', $tabelas)
                ->with('sql', $sql)
                ->with('resultado', null)
                ->with('erro', 'Erro ao executar: ' . $e->getMessage());

        }
    }

    private function _listarTabelas()
    {
        try {
            $linhas = DB::select('SHOW TABLES');
            return array_map(function($linha) {
                return array_values((array) $linha)[0];
            }, $linhas);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Aceita exatamente um comando SELECT/INSERT/UPDATE/DELETE. Bloqueia
     * comandos estruturais e qualquer tentativa de emendar mais de um
     * comando (via ";") — inclusive escondido atrás de comentário SQL.
     * Retorna ['tipo' => ..., 'sql' => ...] em caso de sucesso, ou uma
     * string com a mensagem de erro.
     */
    private function _validarComando($sql)
    {
        $normalizado = trim($sql);
        $normalizado = rtrim($normalizado, "; \t\n\r\0\x0B");

        if($normalizado === ''):
            return 'Informe um comando SQL.';
        endif;

        // Usado só para detectar comando escondido em comentário / múltiplos
        // comandos — a execução em si usa $normalizado, sem alterações.
        $paraValidar = preg_replace('/--.*$/m', '', $normalizado);
        $paraValidar = preg_replace('#/\*.*?\*/#s', '', $paraValidar);

        if(strpos($paraValidar, ';') !== false):
            return 'Apenas um comando por execução. Remova o ";" no meio do texto.';
        endif;

        $comandosRegex = implode('|', $this->comandosPermitidos);
        if(!preg_match('/^\s*(' . $comandosRegex . ')\b/i', $paraValidar, $m)):
            return 'Somente comandos SELECT, INSERT, UPDATE ou DELETE são permitidos.';
        endif;

        $tipo = strtoupper($m[1]);

        foreach($this->palavrasProibidas as $palavra):
            if(preg_match('/\b' . preg_quote($palavra, '/') . '\b/i', $paraValidar)):
                return "Comando não permitido: contém \"$palavra\".";
            endif;
        endforeach;

        return ['tipo' => $tipo, 'sql' => $normalizado];
    }

}
