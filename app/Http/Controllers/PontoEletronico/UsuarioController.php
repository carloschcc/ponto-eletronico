<?php namespace App\Http\Controllers\PontoEletronico;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Http\Requests;
use App\Http\Controllers\Controller;

use Request;
use Session;
use App\Usuario;

class UsuarioController extends PontoEletronicoController {

    public function __construct()
    {
        $this->middleware('authPainelMiddleware');
    }

    public function index(){
        $usuarios = Usuario::orderBy('nome', 'ASC')->get();
        return view('pontoeletronico/usuario/index')->with('usuarios', $usuarios);
    }

    public function novo(){
        return view('pontoeletronico/usuario/data');
    }

    public function editar($id){
        $usuario = Usuario::find($id);
        return view('pontoeletronico/usuario/data')->with('u', $usuario);
    }

    public function salvar(){

        if(Request::input('id') AND Request::input('id') > 0):
            $usuario = Usuario::find(Request::input('id'));
        else:
            $usuario = new Usuario();
        endif;

        $senha = Request::input('senha');
        if(!empty($senha)):
            $usuario->senha = hash('sha1', $senha);
        endif;

        $cpf_banco   = Request::input('cpf_banco');
        $email_banco = Request::input('email_banco');
        $email       = Request::input('email');
        $cpf         = Request::input('cpf');

        $cpf_banco = str_replace(['.', '-'], '', $cpf_banco);
        $cpf       = str_replace(['.', '-'], '', $cpf);

        $ativo = (NULL !== Request::input('ativo')) ? 1 : 0;

        if($email_banco != $email):
            if($this->_verifica_email($email)):
                Session::put('status.msg', 'Esse email já existe em nosso cadastro!');
                return redirect(getenv('APP_URL').'/painel/usuarios');
            endif;
        endif;

        if($cpf_banco != $cpf):
            if($this->_verifica_cpf($cpf)):
                Session::put('status.msg', 'Esse CPF já existe em nosso cadastro!');
                return redirect(getenv('APP_URL').'/painel/usuarios');
            endif;
        endif;

        $this->garantirColunaMatricula();

        $usuario->nome      = Request::input('nome');
        $usuario->email     = $email;
        $usuario->cpf       = $cpf;
        $usuario->matricula = Request::input('matricula') ?? '';
        $usuario->cargo     = Request::input('cargo');
        $usuario->local     = Request::input('local');
        $usuario->ativo     = $ativo;

        // Upload de foto — mesmo padrão do upload de logo que funciona
        if (isset($_FILES['foto']) && !empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK):
            $arquivo = $_FILES['foto'];
            $ext     = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, ['jpg', 'jpeg', 'png'])):
                Session::put('status.msg', 'Formato de foto inválido. Use JPG ou PNG.');
                return redirect(getenv('APP_URL').'/painel/usuarios');
            endif;

            $pasta = public_path('img/foto');
            if (!is_dir($pasta)):
                mkdir($pasta, 0777, true);
            endif;

            $nome_foto = md5(uniqid(rand())) . '.' . $ext;
            $destino   = $pasta . '/' . $nome_foto;

            if (move_uploaded_file($arquivo['tmp_name'], $destino)):
                // Remove foto antiga ao substituir
                if (!empty($usuario->foto)):
                    $foto_antiga = $pasta . '/' . $usuario->foto;
                    if (file_exists($foto_antiga)) unlink($foto_antiga);
                endif;
                $usuario->foto = $nome_foto;
            else:
                Session::put('status.msg', 'Falha ao mover o arquivo. Verifique as permissões de img/foto.');
                return redirect(getenv('APP_URL').'/painel/usuarios');
            endif;
        endif;

        if($usuario->save()):
            Session::put('status.msg', 'Colaborador salvo com sucesso!');
            return redirect(getenv('APP_URL').'/painel/usuarios');
        else:
            return view('pontoeletronico/usuario/data');
        endif;

    }

    public function minhaSenha(){
        return view('pontoeletronico/usuario/minha-senha');
    }

    public function minhaSenhaSalvar(){

        $usuario_id  = Session::get('login.ponto.painel.usuario_id');
        $senha_atual = Request::input('senha_atual');
        $nova_senha  = Request::input('nova_senha');
        $confirmar   = Request::input('confirmar');

        $usuario = Usuario::find($usuario_id);

        if(hash('sha1', $senha_atual) !== $usuario->senha):
            Session::put('status.msg', 'Senha atual incorreta.');
            return redirect(getenv('APP_URL').'/painel/minha-senha');
        endif;

        if($nova_senha !== $confirmar):
            Session::put('status.msg', 'Nova senha e confirmação não conferem.');
            return redirect(getenv('APP_URL').'/painel/minha-senha');
        endif;

        if(strlen($nova_senha) < 4):
            Session::put('status.msg', 'A nova senha deve ter pelo menos 4 caracteres.');
            return redirect(getenv('APP_URL').'/painel/minha-senha');
        endif;

        $usuario->senha = hash('sha1', $nova_senha);
        $usuario->save();

        Session::put('status.msg', 'Senha alterada com sucesso!');
        return redirect(getenv('APP_URL').'/painel/minha-senha');
    }

    private function garantirColunaMatricula(){
        if(!Schema::hasColumn('usuario', 'matricula')):
            Schema::table('usuario', function(Blueprint $table){
                $table->string('matricula', 50)->nullable()->after('cpf');
            });
        endif;
    }

    public function setupFoto(){

        $admin = Session::get('login.ponto.painel.admin');
        if($admin != 1):
            return redirect(getenv('APP_URL').'/painel/');
        endif;

        if(Schema::hasColumn('usuario', 'foto')):
            Session::put('status.msg', 'Coluna foto já existe. Nenhuma alteração necessária.');
        else:
            Schema::table('usuario', function(Blueprint $table){
                $table->string('foto', 100)->nullable()->after('local');
            });
            Session::put('status.msg', 'Coluna foto criada com sucesso!');
        endif;

        return redirect(getenv('APP_URL').'/painel/usuarios');
    }

    public function desabilitar($id){
        $usuario = Usuario::find($id);
        $usuario->ativo = 0;
        $usuario->save();
        Session::put('status.msg', 'Usuário desabilitado com sucesso!');
        return redirect(getenv('APP_URL').'/painel/usuarios');
    }

    public function habilitar($id){
        $usuario = Usuario::find($id);
        $usuario->ativo = 1;
        $usuario->save();
        Session::put('status.msg', 'Usuário ativado com sucesso!');
        return redirect(getenv('APP_URL').'/painel/usuarios');
    }

    public function excluir($id){
        $usuario = Usuario::find($id);
        if($usuario):
            if($usuario->foto):
                $caminho = public_path('img/foto') . DIRECTORY_SEPARATOR . $usuario->foto;
                if(file_exists($caminho)) unlink($caminho);
            endif;
            $usuario->delete();
        endif;
        Session::put('status.msg', 'Usuário excluído com sucesso!');
        return redirect(getenv('APP_URL').'/painel/usuarios');
    }

    public function _verifica_email($email){
        $usuario = Usuario::where(['email' => $email])->get();
        return count($usuario) > 0;
    }

    public function _verifica_cpf($cpf){
        $cpf = str_replace(['.', '-', '/'], '', $cpf);
        $usuario = Usuario::where(['cpf' => $cpf])->get();
        return count($usuario) > 0;
    }

}
