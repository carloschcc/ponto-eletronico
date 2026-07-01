<?php namespace App\Http\Controllers\PontoEletronico;


use Illuminate\Support\Facades\DB;
use App\Http\Requests;
use App\Http\Controllers\Controller;

use Request;
use Session;
use App\Usuario;
use App\Ponto;
use App\PontoAjuste;


class DashboardPainelController extends PontoEletronicoController {

    public function __construct()
    {
        $this->middleware('authPainelMiddleware');
    }

    public function index(){

        $admin      = Session::get('login.ponto.painel.admin');
        $usuario_id = Session::get('login.ponto.painel.usuario_id');

        $hoje       = Date("Y-m-d");
        $mes_inicio = Date("Y-m-01");

        // Exclui registros onde entrada e saída foram ambos removidos (excluídos via painel)
        $com_batida = function($q){ $q->whereNotNull('entrada')->orWhereNotNull('saida'); };

        if($admin == 1):
            $total_colaboradores = Usuario::where('ativo', 1)->where('admin', '!=', 1)->count();
            $ajustes_pendentes   = PontoAjuste::where('status', 0)->count();
            $entradas_hoje       = Ponto::where('data', $hoje)->whereNotNull('entrada')->count();
            $saidas_hoje         = Ponto::where('data', $hoje)->whereNotNull('saida')->count();
            $ultimas_batidas     = Ponto::where('data', $hoje)->where($com_batida)->with('usuario')->orderBy('updated_at', 'DESC')->limit(15)->get();
            $batidas_mes         = Ponto::where('data', '>=', $mes_inicio)->where('data', '<=', $hoje)->where($com_batida)->with('usuario')->orderBy('data', 'DESC')->limit(20)->get();
        else:
            $total_colaboradores = 0;
            $ajustes_pendentes   = PontoAjuste::where('usuario_id', $usuario_id)->where('status', 0)->count();
            $entradas_hoje       = Ponto::where('usuario_id', $usuario_id)->where('data', $hoje)->whereNotNull('entrada')->count();
            $saidas_hoje         = Ponto::where('usuario_id', $usuario_id)->where('data', $hoje)->whereNotNull('saida')->count();
            $ultimas_batidas     = Ponto::where('usuario_id', $usuario_id)->where('data', $hoje)->where($com_batida)->orderBy('entrada', 'DESC')->limit(15)->get();
            $batidas_mes         = Ponto::where('usuario_id', $usuario_id)->where('data', '>=', $mes_inicio)->where('data', '<=', $hoje)->where($com_batida)->orderBy('data', 'DESC')->limit(20)->get();
        endif;

        return view('pontoeletronico/dashboard/dashboard-painel')
            ->with('admin', $admin)
            ->with('total_colaboradores', $total_colaboradores)
            ->with('ajustes_pendentes', $ajustes_pendentes)
            ->with('entradas_hoje', $entradas_hoje)
            ->with('saidas_hoje', $saidas_hoje)
            ->with('ultimas_batidas', $ultimas_batidas)
            ->with('batidas_mes', $batidas_mes);
    }

}