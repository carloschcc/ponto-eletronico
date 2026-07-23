<?php namespace App\Http\Controllers\PontoEletronico;

use App\Http\Controllers\Controller;
use Session;
use App\PeriodoFechamento;
use App\Feriado;

class PeriodoController extends PontoEletronicoController {

    public function __construct()
    {
        $this->middleware('authPainelMiddleware');
    }

    /* ─── PERÍODOS ─── */

    public function index()
    {
        if (!$this->painelAcessoTotal()):
            return redirect(getenv('APP_URL').'/painel/');
        endif;

        $periodos = PeriodoFechamento::orderBy('data_inicio', 'DESC')->get();

        $ano_cal = max(2020, min(2100, (int) request()->input('ano', date('Y'))));

        // Feriados exatos para o ano selecionado
        $feriados_lista = Feriado::whereYear('data', $ano_cal)
            ->orderBy('data')
            ->get();

        // Monta lookup para o grid do calendário
        $feriados_lookup = [];
        foreach ($feriados_lista as $f) {
            $feriados_lookup[$f->data->format('Y-m-d')] = [
                'id'  => $f->id,
                'desc'=> $f->descricao ?? '',
                'rec' => (bool) $f->recorrente,
            ];
        }

        // Recorrentes de outros anos → projeta para $ano_cal
        $recorrentes_outros = Feriado::where('recorrente', 1)
            ->whereYear('data', '!=', $ano_cal)
            ->get();
        foreach ($recorrentes_outros as $r) {
            $k = $ano_cal.'-'.$r->data->format('m-d');
            if (!isset($feriados_lookup[$k])):
                $feriados_lookup[$k] = [
                    'id'  => $r->id,
                    'desc'=> ($r->descricao ?? '').' ★',
                    'rec' => true,
                ];
            endif;
        }

        return view('pontoeletronico/periodo/index', compact(
            'periodos', 'ano_cal', 'feriados_lista', 'feriados_lookup'
        ));
    }

    public function salvar()
    {
        if (!$this->painelAcessoTotal()):
            return redirect(getenv('APP_URL').'/painel/');
        endif;

        $id          = request()->input('id');
        $descricao   = request()->input('descricao');
        $data_inicio = request()->input('data_inicio');
        $data_fim    = request()->input('data_fim');

        if (strpos($data_inicio, '/') !== false):
            $a = explode('/', $data_inicio);
            $data_inicio = $a[2].'-'.$a[1].'-'.$a[0];
        endif;
        if (strpos($data_fim, '/') !== false):
            $a = explode('/', $data_fim);
            $data_fim = $a[2].'-'.$a[1].'-'.$a[0];
        endif;

        $periodo = $id ? PeriodoFechamento::find($id) : new PeriodoFechamento();
        $periodo->descricao   = $descricao;
        $periodo->data_inicio = $data_inicio;
        $periodo->data_fim    = $data_fim;
        $periodo->save();

        Session::put('status.msg', 'Período salvo com sucesso!');
        return redirect(getenv('APP_URL').'/painel/periodo');
    }

    public function excluir($id)
    {
        if (!$this->painelAcessoTotal()):
            return redirect(getenv('APP_URL').'/painel/');
        endif;

        PeriodoFechamento::find($id)->delete();

        Session::put('status.msg', 'Período excluído.');
        return redirect(getenv('APP_URL').'/painel/periodo');
    }

    public function ativar($id)
    {
        if (!$this->painelAcessoTotal()):
            return redirect(getenv('APP_URL').'/painel/');
        endif;

        PeriodoFechamento::query()->update(['ativo' => 0]);
        PeriodoFechamento::find($id)->update(['ativo' => 1]);

        Session::put('status.msg', 'Período ativado com sucesso!');
        return redirect(getenv('APP_URL').'/painel/periodo');
    }

    public function desativar($id)
    {
        if (!$this->painelAcessoTotal()):
            return redirect(getenv('APP_URL').'/painel/');
        endif;

        PeriodoFechamento::find($id)->update(['ativo' => 0]);

        Session::put('status.msg', 'Período desativado.');
        return redirect(getenv('APP_URL').'/painel/periodo');
    }

    /* ─── FERIADOS ─── */

    public function salvarFeriado()
    {
        if (!$this->painelAcessoTotal()):
            return redirect(getenv('APP_URL').'/painel/');
        endif;

        $data_raw   = request()->input('data', '');
        $descricao  = request()->input('descricao', '');
        $recorrente = request()->input('recorrente', 0) ? 1 : 0;

        if (strpos($data_raw, '/') !== false):
            $a = explode('/', $data_raw);
            $data_raw = $a[2].'-'.$a[1].'-'.$a[0];
        endif;

        if (empty($data_raw)):
            Session::put('status.msg', 'Data inválida.');
            return redirect(getenv('APP_URL').'/painel/periodo');
        endif;

        // Upsert — atualiza se a data já existir
        $feriado = Feriado::firstOrNew(['data' => $data_raw]);
        $feriado->descricao  = $descricao;
        $feriado->recorrente = $recorrente;
        $feriado->save();

        $ano = substr($data_raw, 0, 4);
        Session::put('status.msg', 'Feriado salvo!');
        return redirect(getenv('APP_URL').'/painel/periodo?ano='.$ano);
    }

    public function excluirFeriado($id)
    {
        if (!$this->painelAcessoTotal()):
            return redirect(getenv('APP_URL').'/painel/');
        endif;

        $feriado = Feriado::find($id);
        $ano = $feriado ? substr($feriado->data->format('Y-m-d'), 0, 4) : date('Y');
        if ($feriado) $feriado->delete();

        Session::put('status.msg', 'Feriado removido.');
        return redirect(getenv('APP_URL').'/painel/periodo?ano='.$ano);
    }
}
