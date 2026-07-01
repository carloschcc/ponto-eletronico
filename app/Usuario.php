<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    protected $table = 'usuario';
    
    public function pontoAjuste(){
    	return $this->hasMany('App\PontoAjuste');
    }
    
    public function ponto(){
    	return $this->hasMany('App\Ponto');
    }
    
  
    protected $fillable = ['nome', 'cpf', 'matricula', 'email', 'senha', 'cargo', 'admin', 'ativo', 'local', 'regime', 'foto'];
    
}
