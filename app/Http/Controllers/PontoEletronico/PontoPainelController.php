<?php namespace App\Http\Controllers\PontoEletronico;


use Illuminate\Support\Facades\DB;
use App\Http\Requests;
use App\Http\Controllers\Controller;

use Request;
use Session;
use App\Usuario;
use App\Ponto;
use App\PontoAjuste;


class PontoPainelController extends PontoEletronicoController {
    
    
    public function __construct()
    {
        $this->middleware('authPainelMiddleware');
        
    }
    
    public function ajuste(){
        
        $url_base = getenv('APP_URL');
        
        $usuario_id = Session::get('login.ponto.painel.usuario_id');
        
        $ponto_id = Request::input('ponto_id');
        $tipo = Request::input('tipo');
        $data = Request::input('data');
        $hora = Request::input('hora');
        $hora_entrada = Request::input('hora_entrada');
        $hora_saida = Request::input('hora_saida');
        $justificativa = Request::input('justificativa');
        $anexo = Request::input('anexo');
        
        $data_arr = explode("/",$data);
        $data = $data_arr[2].'-'.$data_arr[1].'-'.$data_arr[0];
        
        
        $arquivo_anexo = $_FILES["anexo"];
        $varArquivo_anexo = $arquivo_anexo["name"];
        if($varArquivo_anexo != ''):
            $arquivo_nome_final_anexo = $this->upload(public_path('upload/razao') . DIRECTORY_SEPARATOR, $_FILES['anexo']);
        endif;

               
        if($tipo == 'entrada' OR $tipo == 'saida'):
        
            $ajuste = new PontoAjuste();
            $ajuste->ponto_id = $ponto_id;
            $ajuste->usuario_id = $usuario_id;
            $ajuste->ponto_ajuste_id = 0;
            $ajuste->tipo = $tipo;
            $ajuste->data = $data;
            $ajuste->hora = $hora;
            $ajuste->ponto_razao_id = $justificativa;
            $ajuste->status = 0;
            if($varArquivo_anexo != ''):
                $ajuste->anexo = $arquivo_nome_final_anexo;
            endif;

            $ajuste->save();
            
            
            if($tipo == 'entrada'):
                $ponto = Ponto::find($ponto_id);
                $ponto->entrada = $hora;
                $ponto->entrada_status = 1;
                $ponto->save();
            endif;
            
            if($tipo == 'saida'):
                $ponto = Ponto::find($ponto_id);
                $ponto->saida = $hora;
                $ponto->saida_status = 1;
                $ponto->save();
            endif;
            
        endif;
        
        
        $msg = "Registro salvo com sucesso!";
        Session::put('status.msg', $msg);

        return redirect(getenv('APP_URL').'/painel/ajuste');


    }

    public function excluirCampo($ponto_id, $tipo){

        $usuario_admin = Session::get('login.ponto.painel.admin');

        if($usuario_admin != 1):
            $msg = "Exclusão não permitida.";
            Session::put('status.msg', $msg);
            return redirect(getenv('APP_URL').'/painel/acompanhamento');
        endif;

        $ponto = Ponto::find($ponto_id);

        if(!$ponto):
            $msg = "Registro não encontrado.";
            Session::put('status.msg', $msg);
            return redirect(getenv('APP_URL').'/painel/acompanhamento');
        endif;

        if($tipo == 'entrada'):
            PontoAjuste::where(['ponto_id' => $ponto_id, 'tipo' => 'entrada'])->where('status', 0)->delete();
            $ponto->entrada = NULL;
            $ponto->entrada_status = NULL;
            $ponto->save();
        endif;

        if($tipo == 'saida'):
            PontoAjuste::where(['ponto_id' => $ponto_id, 'tipo' => 'saida'])->where('status', 0)->delete();
            $ponto->saida = NULL;
            $ponto->saida_status = NULL;
            $ponto->save();
        endif;

        $msg = "Batida excluída com sucesso!";
        Session::put('status.msg', $msg);

        return redirect(getenv('APP_URL').'/painel/acompanhamento');

    }


}