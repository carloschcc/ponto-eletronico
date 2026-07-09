<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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

    /**
     * Verifica a senha informada contra o hash armazenado. Senhas antigas
     * (SHA1, de antes da migração para bcrypt) ainda são aceitas e, quando
     * corretas, são automaticamente re-hasheadas para bcrypt neste momento —
     * assim a base é migrada aos poucos, a cada login, sem exigir reset em massa.
     */
    public function autenticar($senhaPlana)
    {
        if (empty($this->senha) || empty($senhaPlana)) {
            return false;
        }

        if (Str::startsWith($this->senha, ['$2y$', '$2a$', '$2b$'])) {
            return Hash::check($senhaPlana, $this->senha);
        }

        if (hash_equals($this->senha, hash('sha1', $senhaPlana))) {
            $this->senha = Hash::make($senhaPlana);
            $this->save();
            return true;
        }

        return false;
    }
}
