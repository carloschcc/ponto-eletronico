<?php namespace App\Http\Controllers\PontoEletronico;

use Illuminate\Support\Facades\DB;
use App\Http\Requests;
use App\Http\Controllers\Controller;

use Request;
use Session;
use App\PontoAjuste;

abstract class PontoEletronicoController extends Controller
{

    public function upload($local, $arquivo){

        if(empty($arquivo['name']) || empty($arquivo['tmp_name'])):
            return false;
        endif;

        if(!is_uploaded_file($arquivo['tmp_name'])):
            return false;
        endif;

        if(isset($arquivo['error']) && $arquivo['error'] !== UPLOAD_ERR_OK):
            return false;
        endif;

        $extensoes_permitidas = array('jpg', 'jpeg', 'png', 'pdf');

        $arquivo_nome = $arquivo['name'];
        $extensao = strtolower(pathinfo($arquivo_nome, PATHINFO_EXTENSION));

        if(!in_array($extensao, $extensoes_permitidas)):
            return false;
        endif;

        // Garante que o diretório destino existe
        if(!is_dir($local)):
            mkdir($local, 0755, true);
        endif;

        $nome_final = md5(uniqid(rand())).'.'.$extensao;

        if(move_uploaded_file($arquivo['tmp_name'], $local . $nome_final)):
            return $nome_final;
        else:
            return false;
        endif;

    }

}
