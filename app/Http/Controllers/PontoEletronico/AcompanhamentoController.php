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

class AcompanhamentoController extends PontoEletronicoController {
    
    public function __construct()
    {
        $this->middleware('authPainelMiddleware');
        
    }
    
    public function index(){
        
        $admin = Session::get('login.ponto.painel.admin');
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
                // Admin sempre busca todos os colaboradores ativos no período
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
        
        $justificativas = PontoRazao::where(['ativo' => 1])->orderBy("descricao", "ASC")->get();

        $periodos_lista = PeriodoFechamento::orderBy('data_inicio', 'DESC')->get();

        if(!isset($periodo_ativo)) $periodo_ativo = null;

        if($admin == 1):
            return view('pontoeletronico/acompanhamento/index-admin')
                ->with('usuario', $usuario)
                ->with('registros', $registros)
                ->with('usuarios', $usuarios)
                ->with('data_inicio', $data_inicio)
                ->with('data_fim', $data_fim)
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
        
        $usuario_admin = Session::get('login.ponto.painel.admin');
        $usuario_id = Session::get('login.ponto.painel.usuario_id');
        
        if($usuario_admin != 1):
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

        $usuario_admin = Session::get('login.ponto.painel.admin');

        if($usuario_admin != 1):
            Session::put('status.msg', 'Acesso não permitido.');
            return redirect(getenv('APP_URL').'/painel/acompanhamento');
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

        $linhas = [];
        foreach($registros as $r):
            if(!$r->usuario) continue;

            $cpf  = preg_replace('/\D/', '', $r->usuario->cpf);
            $nome = utf8_decode($r->usuario->nome);
            $data = \Carbon\Carbon::parse($r->data)->format('d/m/Y');

            if(!empty($r->entrada)):
                $linhas[] = $cpf . ',' . $nome . ',' . $data . ',' . substr($r->entrada, 0, 5);
            endif;

            if(!empty($r->saida)):
                $linhas[] = $cpf . ',' . $nome . ',' . $data . ',' . substr($r->saida, 0, 5);
            endif;
        endforeach;

        $conteudo  = implode("\r\n", $linhas);
        $nome_arquivo = 'ponto_' . $inicio . '_' . $fim . '.txt';

        return response($conteudo, 200, [
            'Content-Type'        => 'text/plain; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $nome_arquivo . '"',
        ]);
    }

    public function espelhoV2($usuario_id, $inicio, $fim){

        $usuario_admin = Session::get('login.ponto.painel.admin');

        if($usuario_admin != 1):
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
            ->with('app_name', getenv('APP_NAME'))
            ->with('todos', $usuario_id == 'all')
            ->with('feriados_set', $feriados_set);
    }

    public function relatorio($usuario_id, $inicio, $fim){

        $usuario_admin = Session::get('login.ponto.painel.admin');

        if($usuario_admin != 1):
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
            ->with('app_name', getenv('APP_NAME'))
            ->with('todos', $usuario_id == 'all');

    }

    public function geolocalizacao($usuario_id, $inicio, $fim){

        $usuario_admin = Session::get('login.ponto.painel.admin');

        if($usuario_admin != 1):
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
            ->with('app_name', getenv('APP_NAME'))
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