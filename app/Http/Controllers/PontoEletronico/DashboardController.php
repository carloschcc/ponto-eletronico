<?php namespace App\Http\Controllers\PontoEletronico;


use Illuminate\Support\Facades\DB;
use App\Configuracao;
use App\Http\Requests;
use App\Http\Controllers\Controller;

use Request;
use Session;
use App\Usuario;
use App\Ponto;

class DashboardController extends PontoEletronicoController {
    
    public function __construct()
    {
        $this->middleware('authMiddleware');
        
    }
    
    public function index(){
        
        $hoje = Date('Y-m-d');
        
        $usuario_id = Session::get('login.ponto.usuario_id');
        
        $usuario = Usuario::find($usuario_id);
        
        $registros = Ponto::where(['usuario_id' => $usuario_id, 'data' => $hoje])->orderBy('id', 'ASC')->get();

        $habilitarLocalizacao = Configuracao::valor('PONTO_LOCALIZACAO_HABILITAR', '0');
        $latitudeConfigurada = Configuracao::valor('PONTO_LOCALIZACAO_LATITUDE', '');
        $longitudeConfigurada = Configuracao::valor('PONTO_LOCALIZACAO_LONGITUDE', '');
        $raioConfigurado = Configuracao::valor('PONTO_LOCALIZACAO_RAIO', '50');

        return view('pontoeletronico/registro/index')
            ->with('usuario', $usuario)
            ->with('registros', $registros)
            ->with('habilitarLocalizacao', $habilitarLocalizacao)
            ->with('latitudeConfigurada', $latitudeConfigurada)
            ->with('longitudeConfigurada', $longitudeConfigurada)
            ->with('raioConfigurado', $raioConfigurado);
        
            
    }
    
}