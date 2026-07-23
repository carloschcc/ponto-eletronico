<?php namespace App\Http\Controllers\PontoEletronico;


use Illuminate\Support\Facades\DB;
use App\Http\Requests;
use App\Http\Controllers\Controller;

use Request;
use Session;
use App\Usuario;
use App\Ponto;
use App\PontoRazao;
use App\PeriodoFechamento;
use App\Feriado;
use App\Configuracao;
use App\Empregador;

class AcompanhamentoController extends PontoEletronicoController {

    public function __construct()
    {
        $this->middleware('authPainelMiddleware');

    }

    public function index(){

        $admin = $this->painelAcessoTotal() ? 1 : 0;
        $usuario_id = Session::get('login.ponto.painel.usuario_id');

        $data = array();

        if($_POST):

            $data_inicio = Request::input('data_inicio');
            $data_inicio_arr = explode("/", $data_inicio);
            $data_inicio_db = $data_inicio_arr[2].'-'.$data_inicio_arr[1].'-'.$data_inicio_arr[0];

            $data_fim = Request::input('data_fim');
            $data_fim_arr = explode("/", $data_fim);
            $data_fim_db = $data_fim_arr[2].'-'.$data_fim_arr[1].'-'.$data_fim_arr[0];

            $semExcluidos = function($q){ $q->whereNotNull('entrada')->orWhereNotNull('saida'); };

            if($admin == 1):
                // Admin/RH sempre busca todos os colaboradores ativos no período
                $ids_ativos = Usuario::where('ativo', 1)->pluck('id');
                $registros = Ponto::whereIn('usuario_id', $ids_ativos)
                    ->where('data', '>=', $data_inicio_db)
                    ->where('data', '<=', $data_fim_db)
                    ->where($semExcluidos)
                    ->with('usuario')
                    ->orderBy('data', 'ASC')
                    ->orderBy('entrada', 'ASC')
                    ->get();
                $usuario  = array();
                $usuarios = array();
            else:
                $registros = Ponto::where(['usuario_id' => $usuario_id])
                    ->where('data', '>=', $data_inicio_db)
                    ->where('data', '<=', $data_fim_db)
                    ->where($semExcluidos)
                    ->with('usuario')
                    ->orderBy('data', 'ASC')
                    ->orderBy('entrada', 'ASC')
                    ->get();
                $usuario  = Usuario::find($usuario_id);
                $usuarios = array();
            endif;

        else:

            // Usa o período ativo mais recente (cuja data_fim >= hoje) se existir
            $periodo_ativo = PeriodoFechamento::where('ativo', 1)->first();

            if($periodo_ativo):
                $data_inicio_db = $periodo_ativo->data_inicio->format('Y-m-d');
                $data_fim_db    = $periodo_ativo->data_fim->format('Y-m-d');
                $data_inicio    = $periodo_ativo->data_inicio->format('d/m/Y');
                $data_fim       = $periodo_ativo->data_fim->format('d/m/Y');
            else:
                $data_inicio_db = Date('Y') . '-' . Date('m') . '-01';
                $data_fim_db    = Date('Y-m-d');
                $data_inicio    = '01/' . Date('m') . '/' . Date('Y');
                $data_fim       = Date('d/m/Y');
            endif;

            $semExcluidos = function($q){ $q->whereNotNull('entrada')->orWhereNotNull('saida'); };

            if($admin == 1):
                $ids_ativos = Usuario::where('ativo', 1)->pluck('id');
                $registros = Ponto::whereIn('usuario_id', $ids_ativos)
                    ->where('data', '>=', $data_inicio_db)
                    ->where('data', '<=', $data_fim_db)
                    ->where($semExcluidos)
                    ->with('usuario')
                    ->orderBy('data', 'ASC')
                    ->orderBy('entrada', 'ASC')
                    ->get();
                $usuario  = array();
                $usuarios = array();
            else:
                $registros = Ponto::where(['usuario_id' => $usuario_id])
                    ->where('data', '>=', $data_inicio_db)
                    ->where('data', '<=', $data_fim_db)
                    ->where($semExcluidos)
                    ->with('usuario')
                    ->orderBy('data', 'ASC')
                    ->orderBy('entrada', 'ASC')
                    ->get();
                $usuario = Usuario::find($usuario_id);
                $usuarios = array();
            endif;

        endif;


        foreach($registros as $registro):

            $data[$registro->usuario->nome][] = $registro;

        endforeach;

        $justificativas = PontoRazao::where(['ativo' => 1])->orderBy("ordem", "ASC")->get();

        $periodos_lista = PeriodoFechamento::orderBy('data_inicio', 'DESC')->get();

        if(!isset($periodo_ativo)) $periodo_ativo = null;

        if($admin == 1):
            return view('pontoeletronico/acompanhamento/index-admin')
                ->with('usuario', $usuario)
                ->with('registros', $registros)
                ->with('usuarios', $usuarios)
                ->with('data_inicio', $data_inicio)
                ->with('data_fim', $data_fim)
                ->with('admin', 1)
                ->with('justificativas', $justificativas)
                ->with('data', $data)
                ->with('periodos_lista', $periodos_lista)
                ->with('periodo_ativo', $periodo_ativo);
        else:
            return view('pontoeletronico/acompanhamento/index')
                ->with('usuario', $usuario)
                ->with('registros', $registros)
                ->with('usuarios', $usuarios)
                ->with('data_inicio', $data_inicio)
                ->with('data_fim', $data_fim)
                ->with('justificativas', $justificativas)
                ->with('data', $data)
                ->with('periodos_lista', $periodos_lista)
                ->with('periodo_ativo', $periodo_ativo);
        endif;


    }

    public function index_download($usuario, $inicio, $fim){

        $usuario_id = Session::get('login.ponto.painel.usuario_id');

        if(!$this->painelAcessoTotal()):
            $msg = "Download não permitido.";
            Session::put('status.msg', $msg);
            return redirect(getenv('APP_URL').'/painel/acompanhamento');
            die();
        endif;


        $data = array();

        $data_inicio_db = $inicio;
        $data_inicio = $inicio;
        $data_fim_db = $fim;
        $data_fim = $fim;


        $semExcluidos = function($q){ $q->whereNotNull('entrada')->orWhereNotNull('saida'); };

        if($usuario == 'all'):
            $registros = Ponto::where('data', '>=', $data_inicio_db)->where('data', '<=', $data_fim_db)->where($semExcluidos)->with('usuario')->orderBy('data', 'ASC')->orderBy('entrada', 'ASC')->get();
        else:
            $registros = Ponto::where(['usuario_id' => $usuario])->where('data', '>=', $data_inicio_db)->where('data', '<=', $data_fim_db)->where($semExcluidos)->with('usuario')->orderBy('data', 'ASC')->orderBy('entrada', 'ASC')->get();
        endif;

        foreach($registros as $registro):
            $data[$registro->usuario->nome][] = $registro;
        endforeach;

        return view('pontoeletronico/acompanhamento/index-download')->with('data_inicio', $data_inicio)->with('data_fim', $data_fim)->with('data', $data);

    }

    public function exportarTxt($usuario, $inicio, $fim){

        if(!$this->painelAcessoTotal()):
            Session::put('status.msg', 'Acesso não permitido.');
            return redirect(getenv('APP_URL').'/painel/acompanhamento');
        endif;

        // Portaria MTP nº 671/2021 (271/2021) exige a identificação do
        // empregador no arquivo — sem CNPJ/CPF e nome cadastrados, o arquivo
        // não pode ser gerado em conformidade.
        if(!Empregador::cadastroCompleto()):
            Session::put('status.msg', 'Cadastre os dados do empregador (menu Empregador) antes de exportar o arquivo de ponto.');
            return redirect(getenv('APP_URL').'/painel/empregador');
        endif;

        $semExcluidos = function($q){ $q->whereNotNull('entrada')->orWhereNotNull('saida'); };

        if($usuario == 'all'):
            $ids_ativos = Usuario::where('ativo', 1)->pluck('id');
            $registros = Ponto::whereIn('usuario_id', $ids_ativos)
                ->where('data', '>=', $inicio)
                ->where('data', '<=', $fim)
                ->where($semExcluidos)
                ->with('usuario')
                ->orderBy('usuario_id', 'ASC')
                ->orderBy('data', 'ASC')
                ->orderBy('entrada', 'ASC')
                ->get();
        else:
            $registros = Ponto::where('usuario_id', $usuario)
                ->where('data', '>=', $inicio)
                ->where('data', '<=', $fim)
                ->where($semExcluidos)
                ->with('usuario')
                ->orderBy('data', 'ASC')
                ->orderBy('entrada', 'ASC')
                ->get();
        endif;

        // Cada marcação (entrada e saída são eventos distintos) vira um
        // registro Tipo 3 próprio, identificado pelo NSR — nunca pelo
        // nome/CPF sozinho.
        $eventos = [];
        foreach($registros as $r):
            if(!$r->usuario) continue;

            $cpf = preg_replace('/\D/', '', $r->usuario->cpf);

            if(!empty($r->entrada)):
                // Auto-cura: marcação sem NSR (gravada antes da coluna
                // existir, ou inserida por fora do fluxo normal) recebe um
                // agora, para nunca sair do arquivo sem identificação.
                if(empty($r->entrada_nsr)):
                    $r->entrada_nsr = $this->gerarNsr('entrada_backfill_export');
                    $r->save();
                endif;
                $eventos[] = [
                    'nsr' => (int) $r->entrada_nsr, 'cpf' => $cpf, 'data' => $r->data,
                    'hora' => $r->entrada, 'tipo_operacao' => '1', 'terminal' => $r->entrada_ip,
                ];
            endif;

            if(!empty($r->saida)):
                if(empty($r->saida_nsr)):
                    $r->saida_nsr = $this->gerarNsr('saida_backfill_export');
                    $r->save();
                endif;
                $eventos[] = [
                    'nsr' => (int) $r->saida_nsr, 'cpf' => $cpf, 'data' => $r->data,
                    'hora' => $r->saida, 'tipo_operacao' => '2', 'terminal' => $r->saida_ip,
                ];
            endif;
        endforeach;

        usort($eventos, function($a, $b){ return $a['nsr'] <=> $b['nsr']; });

        $linhaHeader = $this->linhaHeaderAfd($inicio, $fim);
        $linhas = [$linhaHeader];
        $hashAnterior = hash('sha256', $linhaHeader);

        foreach($eventos as $e):
            $linha = $this->linhaRegistroAfd($e, $hashAnterior);
            $linhas[] = $linha;
            $hashAnterior = hash('sha256', $linha);
        endforeach;

        $linhas[] = $this->linhaRodapeAfd(count($eventos));

        $conteudo = implode("\r\n", $linhas);
        $nome_arquivo = 'ponto_' . $inicio . '_' . $fim . '.txt';

        return response($conteudo, 200, [
            'Content-Type'        => 'text/plain; charset=us-ascii',
            'Content-Disposition' => 'attachment; filename="' . $nome_arquivo . '"',
        ]);
    }

    /**
     * Registro Tipo 1 (cabeçalho, 100 caracteres) — identificação do
     * empregador exigida pela Portaria MTP nº 671/2021 (271/2021).
     * Layout posicional fixo definido pela especificação do arquivo.
     */
    private function linhaHeaderAfd($inicio, $fim)
    {
        $emp = Empregador::dados();

        $tipoEmpregador = $emp['tipo_pessoa'] === 'fisica' ? '2' : '1';
        $documento      = $this->campoNumerico($emp['documento'], 14);
        $ceiCaepfCno    = str_repeat('0', 15); // não coletado no cadastro do empregador
        $razaoSocial    = $this->campoTextoAfd($emp['nome'], 40);
        $dataInicio     = \Carbon\Carbon::parse($inicio)->format('dmY');
        $dataFim        = \Carbon\Carbon::parse($fim)->format('dmY');
        $horaEmissao    = \Carbon\Carbon::now()->format('Hi');

        return '000000001'      // 001-009: NSR fixo do cabeçalho
            . '1'                // 010-010: tipo de registro
            . $tipoEmpregador    // 011-011: 1=CNPJ, 2=CPF
            . $documento         // 012-025: CNPJ/CPF (14)
            . $ceiCaepfCno       // 026-040: CEI/CAEPF/CNO (15)
            . $razaoSocial       // 041-080: razão social (40)
            . $dataInicio        // 081-088: data início (8)
            . $dataFim           // 089-096: data fim (8)
            . $horaEmissao;      // 097-100: hora emissão (4)
    }

    /**
     * Registro Tipo 3 (marcação, 310 caracteres). O NSR é o contador global
     * persistente (nunca reinicia, nunca se repete — ver gerarNsr()), não um
     * número reiniciado a cada arquivo. A assinatura (216-310) encadeia o
     * hash SHA-256 do registro anterior (cabeçalho ou marcação), formando
     * uma trilha de integridade entre os registros do arquivo.
     */
    private function linhaRegistroAfd(array $evento, $hashAnterior)
    {
        $nsr      = $this->campoNumerico($evento['nsr'], 9);
        $data     = \Carbon\Carbon::parse($evento['data'])->format('dmY');
        $hora     = str_replace(':', '', substr($evento['hora'], 0, 5));
        $cpf      = $this->campoNumerico($evento['cpf'], 11);
        $terminal = $this->campoTextoAfd($evento['terminal'], 16);

        // Fuso horário fixo (-03:00/Brasília) — não há campo de fuso por
        // usuário/registro cadastrado no sistema hoje.
        $fuso = '3';

        $corpo = $nsr
            . '3'                    // 010-010: tipo de registro
            . $data                  // 011-018: data da marcação (8)
            . $hora                  // 019-022: hora da marcação (4)
            . $cpf                   // 023-033: CPF do trabalhador (11)
            . $evento['tipo_operacao'] // 034-034: 1=entrada, 2=saída
            . '0000'                 // 035-038: código do evento/motivo (não utilizado)
            . $terminal              // 039-054: terminal/dispositivo (16)
            . $fuso                  // 055-055: tipo de fuso horário
            . str_repeat('0', 160);  // 056-215: reservado

        $assinatura = str_pad(substr($hashAnterior, 0, 95), 95, '0', STR_PAD_RIGHT);

        return $corpo . $assinatura; // 216-310: assinatura (95)
    }

    /**
     * Registro Tipo 9 (rodapé, 100 caracteres) — marca o fim do arquivo e
     * informa a quantidade de registros Tipo 3 gerados.
     */
    private function linhaRodapeAfd($totalRegistrosTipo3)
    {
        return '999999999'
            . str_repeat(' ', 87)
            . str_pad((string) $totalRegistrosTipo3, 4, ' ', STR_PAD_LEFT);
    }

    /**
     * Campo numérico de tamanho fixo: mantém só dígitos e completa com
     * zeros à esquerda (ou trunca os dígitos mais significativos, se o
     * valor já vier maior que o campo).
     */
    private function campoNumerico($valor, $tamanho)
    {
        $valor = preg_replace('/\D/', '', (string) $valor);
        $valor = substr($valor, -$tamanho);
        return str_pad($valor, $tamanho, '0', STR_PAD_LEFT);
    }

    /**
     * Campo texto de tamanho fixo: transliterado para ASCII 7-bit antes de
     * truncar/completar, para o corte no tamanho exato do campo não cair no
     * meio de um caractere acentuado nem alterar a largura da linha.
     */
    private function campoTextoAfd($texto, $tamanho)
    {
        $texto = str_replace(["\r", "\n"], ' ', (string) $texto);
        $texto = $this->paraAscii($texto);
        $texto = substr($texto, 0, $tamanho);
        return str_pad($texto, $tamanho, ' ', STR_PAD_RIGHT);
    }

    /**
     * Converte para ASCII 7-bit "de verdade" (ex: "é" -> "e"), diferente de
     * utf8_decode/Latin-1, que ainda usa bytes fora da faixa 0-127 para
     * caracteres acentuados. Usa um mapa de substituição próprio em vez de
     * iconv('...//TRANSLIT') porque a imagem Docker deste projeto só tem a
     * locale POSIX/C instalada — sem tabela de transliteração, o iconv
     * troca cada acento por "?" ao invés de convertê-lo de verdade.
     */
    private function paraAscii($texto)
    {
        static $mapa = [
            'á'=>'a','à'=>'a','â'=>'a','ã'=>'a','ä'=>'a','å'=>'a',
            'Á'=>'A','À'=>'A','Â'=>'A','Ã'=>'A','Ä'=>'A','Å'=>'A',
            'é'=>'e','è'=>'e','ê'=>'e','ë'=>'e','É'=>'E','È'=>'E','Ê'=>'E','Ë'=>'E',
            'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i','Í'=>'I','Ì'=>'I','Î'=>'I','Ï'=>'I',
            'ó'=>'o','ò'=>'o','ô'=>'o','õ'=>'o','ö'=>'o','Ó'=>'O','Ò'=>'O','Ô'=>'O','Õ'=>'O','Ö'=>'O',
            'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u','Ú'=>'U','Ù'=>'U','Û'=>'U','Ü'=>'U',
            'ç'=>'c','Ç'=>'C','ñ'=>'n','Ñ'=>'N','ý'=>'y','ÿ'=>'y','Ý'=>'Y',
            '—'=>'-','–'=>'-','‘'=>"'",'’'=>"'",'“'=>'"','”'=>'"','…'=>'...',
        ];

        $convertido = strtr($texto, $mapa);

        return preg_replace('/[^\x20-\x7E\r\n]/', '', $convertido);
    }

    public function espelhoV2($usuario_id, $inicio, $fim){

        $meu_id = Session::get('login.ponto.painel.usuario_id');
        $eh_proprio = ($usuario_id != 'all' AND (string) $usuario_id === (string) $meu_id);

        // Colaborador comum só imprime o próprio espelho — nunca de outros
        // nem o consolidado ("all"). Admin/RH imprimem qualquer um.
        if(!$this->painelAcessoTotal() AND !$eh_proprio):
            Session::put('status.msg', 'Acesso não permitido.');
            return redirect(getenv('APP_URL').'/painel/acompanhamento');
        endif;

        $data = [];
        $semExcluidos = function($q){ $q->whereNotNull('entrada')->orWhereNotNull('saida'); };

        if($usuario_id == 'all'):
            $ids_ativos = Usuario::where('ativo', 1)->pluck('id');
            $registros = Ponto::whereIn('usuario_id', $ids_ativos)
                ->where('data', '>=', $inicio)
                ->where('data', '<=', $fim)
                ->where($semExcluidos)
                ->with('usuario')
                ->orderBy('usuario_id', 'ASC')
                ->orderBy('data', 'ASC')
                ->orderBy('entrada', 'ASC')
                ->get();
        else:
            $registros = Ponto::where('usuario_id', $usuario_id)
                ->where('data', '>=', $inicio)
                ->where('data', '<=', $fim)
                ->where($semExcluidos)
                ->with('usuario')
                ->orderBy('data', 'ASC')
                ->orderBy('entrada', 'ASC')
                ->get();
        endif;

        foreach($registros as $registro):
            if($registro->usuario):
                $data[$registro->usuario->nome][] = $registro;
            endif;
        endforeach;

        ksort($data);

        // ── Lookup de feriados para o intervalo ──
        $feriados_set = [];
        $year_ini = (int) (new \DateTime($inicio))->format('Y');
        $year_fim = (int) (new \DateTime($fim))->format('Y');

        // Feriados com data exata dentro do intervalo
        $diretos = Feriado::where('recorrente', 0)
            ->whereBetween('data', [$inicio, $fim])
            ->get();
        foreach ($diretos as $f):
            $feriados_set[$f->data->format('Y-m-d')] = $f->descricao ?? 'FERIADO';
        endforeach;

        // Feriados recorrentes — projeta para todos os anos do intervalo
        $recorrentes = Feriado::where('recorrente', 1)->get();
        foreach ($recorrentes as $r):
            $mes_dia = $r->data->format('m-d');
            for ($y = $year_ini; $y <= $year_fim; $y++):
                $nova = $y.'-'.$mes_dia;
                if ($nova >= $inicio && $nova <= $fim && !isset($feriados_set[$nova])):
                    $feriados_set[$nova] = $r->descricao ?? 'FERIADO';
                endif;
            endfor;
        endforeach;

        return view('pontoeletronico/acompanhamento/espelho-v2')
            ->with('data', $data)
            ->with('data_inicio', $inicio)
            ->with('data_fim', $fim)
            ->with('app_name', Configuracao::valor('NOME_SISTEMA', 'Ponto Eletrônico'))
            ->with('todos', $usuario_id == 'all')
            ->with('feriados_set', $feriados_set);
    }

    public function relatorio($usuario_id, $inicio, $fim){

        if(!$this->painelAcessoTotal()):
            $msg = "Acesso não permitido.";
            Session::put('status.msg', $msg);
            return redirect(getenv('APP_URL').'/painel/acompanhamento');
        endif;

        $data = array();

        $semExcluidos = function($q){ $q->whereNotNull('entrada')->orWhereNotNull('saida'); };

        if($usuario_id == 'all'):
            // Apenas colaboradores ativos e não-admin
            $ids_ativos = Usuario::where('ativo', 1)->pluck('id');
            $registros = Ponto::whereIn('usuario_id', $ids_ativos)
                ->where('data', '>=', $inicio)
                ->where('data', '<=', $fim)
                ->where($semExcluidos)
                ->with('usuario')
                ->orderBy('usuario_id', 'ASC')
                ->orderBy('data', 'ASC')
                ->orderBy('entrada', 'ASC')
                ->get();
        else:
            $registros = Ponto::where(['usuario_id' => $usuario_id])
                ->where('data', '>=', $inicio)
                ->where('data', '<=', $fim)
                ->where($semExcluidos)
                ->with('usuario')
                ->orderBy('data', 'ASC')
                ->orderBy('entrada', 'ASC')
                ->get();
        endif;

        foreach($registros as $registro):
            if($registro->usuario):
                $data[$registro->usuario->nome][] = $registro;
            endif;
        endforeach;

        // Ordena por nome do colaborador
        ksort($data);

        $arr = explode("-", $inicio);
        $data_inicio_fmt = $arr[2].'/'.$arr[1].'/'.$arr[0];

        $arr = explode("-", $fim);
        $data_fim_fmt = $arr[2].'/'.$arr[1].'/'.$arr[0];

        return view('pontoeletronico/acompanhamento/relatorio')
            ->with('data', $data)
            ->with('data_inicio', $data_inicio_fmt)
            ->with('data_fim', $data_fim_fmt)
            ->with('app_name', Configuracao::valor('NOME_SISTEMA', 'Ponto Eletrônico'))
            ->with('todos', $usuario_id == 'all');

    }

    public function geolocalizacao($usuario_id, $inicio, $fim){

        if(!$this->painelAcessoTotal()):
            Session::put('status.msg', 'Acesso não permitido.');
            return redirect(getenv('APP_URL').'/painel/acompanhamento');
        endif;

        $data = array();

        $semExcluidos = function($q){ $q->whereNotNull('entrada')->orWhereNotNull('saida'); };

        if($usuario_id == 'all'):
            $ids_ativos = Usuario::where('ativo', 1)->pluck('id');
            $registros = Ponto::whereIn('usuario_id', $ids_ativos)
                ->where('data', '>=', $inicio)
                ->where('data', '<=', $fim)
                ->where($semExcluidos)
                ->with('usuario')
                ->orderBy('usuario_id', 'ASC')
                ->orderBy('data', 'ASC')
                ->orderBy('entrada', 'ASC')
                ->get();
        else:
            $registros = Ponto::where(['usuario_id' => $usuario_id])
                ->where('data', '>=', $inicio)
                ->where('data', '<=', $fim)
                ->where($semExcluidos)
                ->with('usuario')
                ->orderBy('data', 'ASC')
                ->orderBy('entrada', 'ASC')
                ->get();
        endif;

        foreach($registros as $registro):
            $this->preencherGeolocalizacaoDeObservacoes($registro);
            if($registro->usuario):
                $data[$registro->usuario->nome][] = $registro;
            endif;
        endforeach;

        ksort($data);

        $arr = explode("-", $inicio);
        $data_inicio_fmt = $arr[2].'/'.$arr[1].'/'.$arr[0];

        $arr = explode("-", $fim);
        $data_fim_fmt = $arr[2].'/'.$arr[1].'/'.$arr[0];

        return view('pontoeletronico/acompanhamento/geolocalizacao')
            ->with('data', $data)
            ->with('data_inicio', $data_inicio_fmt)
            ->with('data_fim', $data_fim_fmt)
            ->with('app_name', Configuracao::valor('NOME_SISTEMA', 'Ponto Eletrônico'))
            ->with('todos', $usuario_id == 'all');

    }

    /**
     * Registros antigos (gravados antes das colunas entrada_ip/saida_ip existirem,
     * ou em ambientes onde a migration ainda não rodou) só têm IP/latitude/longitude
     * dentro do texto livre de "observacoes". Preenche os atributos em memória a
     * partir desse texto quando as colunas estruturadas estiverem vazias.
     */
    private function preencherGeolocalizacaoDeObservacoes($registro)
    {
        $obs = $registro->observacoes ?? null;
        if (!$obs) {
            return;
        }

        $entradaSeg = null;
        $saidaSeg = null;

        if (preg_match('/^(.*?)\s*\|\s*(Sa[ií]da\s*-.*)$/us', $obs, $mm)) {
            $entradaSeg = $mm[1];
            $saidaSeg = $mm[2];
        } elseif (preg_match('/^Sa[ií]da\s*-/u', trim($obs))) {
            $saidaSeg = $obs;
        } else {
            $entradaSeg = $obs;
        }

        if (empty($registro->entrada_ip) && $entradaSeg && preg_match('/IP:\s*([^\|]+)/u', $entradaSeg, $m)) {
            $ip = trim($m[1]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                $registro->entrada_ip = $ip;
            }
            if (preg_match('/Localiza[çc][ãa]o (GPS|IP):\s*([\-0-9\.]+)\s*,\s*([\-0-9\.]+)/u', $entradaSeg, $ml)) {
                $registro->entrada_latitude = trim($ml[2]);
                $registro->entrada_longitude = trim($ml[3]);
                if (empty($registro->entrada_geo_fonte)) {
                    $registro->entrada_geo_fonte = strtoupper($ml[1]) === 'GPS' ? 'gps' : 'ip';
                }
            }
        }

        if (empty($registro->saida_ip) && $saidaSeg && preg_match('/IP:\s*([^\|]+)/u', $saidaSeg, $m)) {
            $ip = trim($m[1]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                $registro->saida_ip = $ip;
            }
            if (preg_match('/Localiza[çc][ãa]o (GPS|IP):\s*([\-0-9\.]+)\s*,\s*([\-0-9\.]+)/u', $saidaSeg, $ml)) {
                $registro->saida_latitude = trim($ml[2]);
                $registro->saida_longitude = trim($ml[3]);
                if (empty($registro->saida_geo_fonte)) {
                    $registro->saida_geo_fonte = strtoupper($ml[1]) === 'GPS' ? 'gps' : 'ip';
                }
            }
        }
    }

}
