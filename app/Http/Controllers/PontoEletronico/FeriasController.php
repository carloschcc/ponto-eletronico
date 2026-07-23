<?php namespace App\Http\Controllers\PontoEletronico;

use Request;
use Session;
use App\Ferias;
use App\TipoFerias;
use App\Configuracao;

class FeriasController extends PontoEletronicoController {

    public function __construct()
    {
        $this->middleware('authPainelMiddleware');
    }

    public function index()
    {
        $usuario_id = Session::get('login.ponto.painel.usuario_id');

        if($this->painelPodeCertificar()):

            // status 0 = pendente; status 3 = pré-aprovado pelo gerente (ainda
            // pode ser revertido/excluído por RH/admin até a aprovação final).
            $solicitacoes = Ferias::whereIn('status', [0, 3])
                ->with(['usuario', 'tipoFerias'])
                ->orderBy('data_inicio', 'ASC')
                ->get();

            $aprovadas = Ferias::where('status', 1)
                ->with(['usuario', 'tipoFerias'])
                ->orderBy('data_inicio', 'ASC')
                ->get();

            $totais = ['agendada' => 0, 'em_ferias' => 0, 'retornando' => 0];
            $timelines = [];

            foreach($aprovadas as $f):
                $tl = $f->statusLinhaDoTempo();
                $timelines[$f->id] = $tl;

                if(in_array($tl['chave'], ['agendada', 'alerta_saida'])):
                    $totais['agendada']++;
                elseif(in_array($tl['chave'], ['em_ferias', 'alerta_retorno'])):
                    $totais['em_ferias']++;
                else:
                    $totais['retornando']++;
                endif;
            endforeach;

            return view('pontoeletronico/ferias/index-admin')
                ->with('solicitacoes', $solicitacoes)
                ->with('aprovadas', $aprovadas)
                ->with('timelines', $timelines)
                ->with('totais', $totais);

        else:

            $solicitacoes = Ferias::where('usuario_id', $usuario_id)
                ->with('tipoFerias')
                ->orderBy('data_inicio', 'DESC')
                ->get();

            $timelines = [];
            foreach($solicitacoes as $f):
                if($f->status == 1):
                    $timelines[$f->id] = $f->statusLinhaDoTempo();
                endif;
            endforeach;

            return view('pontoeletronico/ferias/index')
                ->with('solicitacoes', $solicitacoes)
                ->with('timelines', $timelines);

        endif;
    }

    public function solicitar()
    {
        $usuario_id = Session::get('login.ponto.painel.usuario_id');

        $data_inicio = $this->_converterData(Request::input('data_inicio'));
        $data_fim    = $this->_converterData(Request::input('data_fim'));
        $observacao  = trim(Request::input('observacao', ''));
        $tipo_ferias_id = Request::input('tipo_ferias_id');
        $ano_referencia = Request::input('ano_referencia');

        if(!$data_inicio OR !$data_fim):
            Session::put('status.msg', 'Informe datas de início e fim válidas.');
            return redirect(getenv('APP_URL').'/painel/ferias');
        endif;

        if($data_fim < $data_inicio):
            Session::put('status.msg', 'A data de fim não pode ser anterior à data de início.');
            return redirect(getenv('APP_URL').'/painel/ferias');
        endif;

        if(!TipoFerias::where('id', $tipo_ferias_id)->where('ativo', 1)->exists()):
            Session::put('status.msg', 'Selecione um tipo de solicitação válido.');
            return redirect(getenv('APP_URL').'/painel/ferias');
        endif;

        $anoAtual = (int) date('Y');
        $ano_referencia = (int) $ano_referencia;
        if($ano_referencia < ($anoAtual - 5) OR $ano_referencia > ($anoAtual + 1)):
            Session::put('status.msg', 'Informe um ano de referência válido.');
            return redirect(getenv('APP_URL').'/painel/ferias');
        endif;

        // Evita duas solicitações (pendentes ou aprovadas) do mesmo
        // colaborador com períodos que se sobrepõem.
        $conflito = Ferias::where('usuario_id', $usuario_id)
            ->whereIn('status', [0, 1, 3])
            ->where('data_inicio', '<=', $data_fim)
            ->where('data_fim', '>=', $data_inicio)
            ->exists();

        if($conflito):
            Session::put('status.msg', 'Já existe uma solicitação de férias pendente ou aprovada nesse período.');
            return redirect(getenv('APP_URL').'/painel/ferias');
        endif;

        $ferias = new Ferias();
        $ferias->usuario_id      = $usuario_id;
        $ferias->tipo_ferias_id  = $tipo_ferias_id;
        $ferias->ano_referencia  = $ano_referencia;
        $ferias->data_inicio     = $data_inicio;
        $ferias->data_fim        = $data_fim;
        $ferias->status          = 0;
        $ferias->observacao      = $observacao !== '' ? $observacao : null;
        $ferias->save();

        Session::put('status.msg', 'Solicitação de férias enviada com sucesso!');
        return redirect(getenv('APP_URL').'/painel/ferias');
    }

    public function excluir($id)
    {
        $usuario_id = Session::get('login.ponto.painel.usuario_id');
        $ferias = Ferias::find($id);

        if(!$ferias OR $ferias->usuario_id != $usuario_id):
            Session::put('status.msg', 'Exclusão não permitida.');
            return redirect(getenv('APP_URL').'/painel/ferias');
        endif;

        if($ferias->status != 0):
            Session::put('status.msg', 'Somente solicitações pendentes podem ser excluídas.');
            return redirect(getenv('APP_URL').'/painel/ferias');
        endif;

        $ferias->delete();
        Session::put('status.msg', 'Solicitação excluída com sucesso!');
        return redirect(getenv('APP_URL').'/painel/ferias');
    }

    public function certificar()
    {
        if(!$this->painelPodeCertificar()):
            Session::put('status.msg', 'Certificação não permitida.');
            return redirect(getenv('APP_URL').'/painel/ferias');
        endif;

        $usuario_logado_id = Session::get('login.ponto.painel.usuario_id');
        $solicitacao_id = Request::input('solicitacao_id');
        $botao = Request::input('botao');
        $obs = Request::input('obs');

        $ferias = Ferias::find($solicitacao_id);

        if(!$ferias):
            Session::put('status.msg', 'Solicitação não encontrada.');
            return redirect(getenv('APP_URL').'/painel/ferias');
        endif;

        // Ninguém aprova/rejeita a própria solicitação.
        if($ferias->usuario_id == $usuario_logado_id):
            Session::put('status.msg', 'Você não pode aprovar/rejeitar a própria solicitação.');
            return redirect(getenv('APP_URL').'/painel/ferias');
        endif;

        // Gerente só pré-aprova (status 3) — ainda pode ser revertido por
        // RH/admin. Aprovação de RH/admin já é definitiva (status 1).
        $status_aprovado = $this->painelAcessoTotal() ? 1 : 3;

        if($botao == 'sim'):
            $ferias->status = $status_aprovado;
        elseif($botao == 'nao'):
            $ferias->status = 2;
        endif;

        $ferias->aprovado_por   = $usuario_logado_id;
        $ferias->obs_supervisor = $obs;
        $ferias->save();

        Session::put('status.msg', 'Solicitação de férias atualizada com sucesso!');
        return redirect(getenv('APP_URL').'/painel/ferias');
    }

    public function certificarBulk()
    {
        if(!$this->painelPodeCertificar()):
            Session::put('status.msg', 'Certificação não permitida.');
            return redirect(getenv('APP_URL').'/painel/ferias');
        endif;

        $ids  = Request::input('solicitacoes', []);
        $acao = Request::input('acao'); // 'aprovar' ou 'rejeitar'
        $obs  = Request::input('obs_bulk', '');
        $usuario_logado_id = Session::get('login.ponto.painel.usuario_id');

        $status_aprovado = $this->painelAcessoTotal() ? 1 : 3;

        if(empty($ids)):
            Session::put('status.msg', 'Nenhuma solicitação selecionada.');
            return redirect(getenv('APP_URL').'/painel/ferias');
        endif;

        $count = 0;
        $ignoradas_proprias = 0;

        foreach($ids as $id):

            $ferias = Ferias::find($id);
            if(!$ferias OR !in_array($ferias->status, [0, 3])) continue;

            if($ferias->usuario_id == $usuario_logado_id):
                $ignoradas_proprias++;
                continue;
            endif;

            if($acao === 'aprovar'):
                $ferias->status = $status_aprovado;
            elseif($acao === 'rejeitar'):
                $ferias->status = 2;
            else:
                continue;
            endif;

            $ferias->aprovado_por   = $usuario_logado_id;
            $ferias->obs_supervisor = $obs;
            $ferias->save();
            $count++;

        endforeach;

        $msg = "$count solicitação(ões) processada(s) com sucesso!";
        if($ignoradas_proprias > 0):
            $msg .= " $ignoradas_proprias solicitação(ões) sua(s) foram ignoradas — você não pode aprovar/rejeitar a própria solicitação.";
        endif;
        Session::put('status.msg', $msg);
        return redirect(getenv('APP_URL').'/painel/ferias');
    }

    public function excluirAdmin($id)
    {
        if(!$this->painelAcessoTotal()):
            Session::put('status.msg', 'Exclusão não permitida.');
            return redirect(getenv('APP_URL').'/painel/ferias');
        endif;

        $ferias = Ferias::find($id);

        if(!$ferias):
            Session::put('status.msg', 'Solicitação não encontrada.');
            return redirect(getenv('APP_URL').'/painel/ferias');
        endif;

        // Aprovada definitivamente (status 1) só pode ser excluída se ainda
        // não começou (agendada) — uma vez em andamento ou já concluída,
        // apagar sem outra ação administrativa bagunça o histórico.
        if($ferias->status == 1):
            $tl = $ferias->statusLinhaDoTempo();
            if(!in_array($tl['chave'], ['agendada', 'alerta_saida'])):
                Session::put('status.msg', 'Só é possível excluir férias aprovadas que ainda não começaram.');
                return redirect(getenv('APP_URL').'/painel/ferias');
            endif;
        endif;

        $ferias->delete();
        Session::put('status.msg', 'Solicitação excluída com sucesso!');
        return redirect(getenv('APP_URL').'/painel/ferias');
    }

    /**
     * Admin/gerente/RH podem corrigir as datas de uma férias já aprovada,
     * mas só enquanto ela ainda não começou (agendada) — depois disso a
     * data de início já virou fato consumado.
     */
    public function editar()
    {
        if(!$this->painelPodeCertificar()):
            Session::put('status.msg', 'Edição não permitida.');
            return redirect(getenv('APP_URL').'/painel/ferias');
        endif;

        $ferias = Ferias::find(Request::input('ferias_id'));

        if(!$ferias OR $ferias->status != 1):
            Session::put('status.msg', 'Somente férias aprovadas podem ser editadas.');
            return redirect(getenv('APP_URL').'/painel/ferias');
        endif;

        $tl = $ferias->statusLinhaDoTempo();
        if(!in_array($tl['chave'], ['agendada', 'alerta_saida'])):
            Session::put('status.msg', 'Só é possível editar férias que ainda não começaram.');
            return redirect(getenv('APP_URL').'/painel/ferias');
        endif;

        $data_inicio = $this->_converterData(Request::input('data_inicio'));
        $data_fim    = $this->_converterData(Request::input('data_fim'));

        if(!$data_inicio OR !$data_fim):
            Session::put('status.msg', 'Informe datas de início e fim válidas.');
            return redirect(getenv('APP_URL').'/painel/ferias');
        endif;

        if($data_fim < $data_inicio):
            Session::put('status.msg', 'A data de fim não pode ser anterior à data de início.');
            return redirect(getenv('APP_URL').'/painel/ferias');
        endif;

        if($data_inicio < date('Y-m-d')):
            Session::put('status.msg', 'A nova data de início não pode estar no passado.');
            return redirect(getenv('APP_URL').'/painel/ferias');
        endif;

        $conflito = Ferias::where('usuario_id', $ferias->usuario_id)
            ->where('id', '!=', $ferias->id)
            ->whereIn('status', [0, 1, 3])
            ->where('data_inicio', '<=', $data_fim)
            ->where('data_fim', '>=', $data_inicio)
            ->exists();

        if($conflito):
            Session::put('status.msg', 'Já existe outra solicitação de férias desse colaborador nesse período.');
            return redirect(getenv('APP_URL').'/painel/ferias');
        endif;

        $ferias->data_inicio = $data_inicio;
        $ferias->data_fim    = $data_fim;
        $ferias->save();

        Session::put('status.msg', 'Datas de férias atualizadas com sucesso!');
        return redirect(getenv('APP_URL').'/painel/ferias');
    }

    /**
     * Relatório de impressão dos colaboradores com férias aprovadas,
     * separado pelas duas situações que fazem sentido pra imprimir/afixar:
     * quem ainda vai sair (Agendada) e quem já está fora (Em Férias).
     * "Retornando" (já voltou) fica de fora de propósito — não é o público
     * desse relatório.
     */
    public function imprimir()
    {
        if(!$this->painelPodeCertificar()):
            Session::put('status.msg', 'Acesso não permitido.');
            return redirect(getenv('APP_URL').'/painel/ferias');
        endif;

        $aprovadas = Ferias::where('status', 1)
            ->with(['usuario', 'tipoFerias'])
            ->orderBy('data_inicio', 'ASC')
            ->get();

        $agendadas = [];
        $emFerias  = [];

        foreach($aprovadas as $f):
            $tl = $f->statusLinhaDoTempo();

            if(in_array($tl['chave'], ['agendada', 'alerta_saida'])):
                $agendadas[] = ['ferias' => $f, 'tl' => $tl];
            elseif(in_array($tl['chave'], ['em_ferias', 'alerta_retorno'])):
                $emFerias[] = ['ferias' => $f, 'tl' => $tl];
            endif;
        endforeach;

        return view('pontoeletronico/ferias/imprimir')
            ->with('agendadas', $agendadas)
            ->with('emFerias', $emFerias)
            ->with('app_name', Configuracao::valor('NOME_SISTEMA', 'Ponto Eletrônico'));
    }

    private function _converterData($data)
    {
        $data = trim((string) $data);
        if($data === '') return null;

        $partes = explode('/', $data);
        if(count($partes) !== 3) return null;

        [$d, $m, $y] = $partes;
        if(!checkdate((int) $m, (int) $d, (int) $y)) return null;

        return $y . '-' . str_pad($m, 2, '0', STR_PAD_LEFT) . '-' . str_pad($d, 2, '0', STR_PAD_LEFT);
    }

}
