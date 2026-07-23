<?php namespace App\Http\Controllers\PontoEletronico;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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

    /**
     * Gestão de colaboradores (listar/cadastrar/editar/excluir/ativar) é de
     * administradores e do perfil Gestor de RH — gerentes só aprovam ajustes.
     * Restrições específicas do RH em relação à flag admin são tratadas à
     * parte, em cada método (ver _souAdminReal / bloqueios abaixo).
     */
    private function _bloqueiaSemAcesso()
    {
        if(!$this->painelAcessoTotal()):
            Session::put('status.msg', 'Acesso restrito a administradores e RH.');
            return redirect(getenv('APP_URL').'/painel/dashboard');
        endif;
        return null;
    }

    private function _souAdminReal()
    {
        return Session::get('login.ponto.painel.admin') == 1;
    }

    public function index(){
        if($redir = $this->_bloqueiaSemAcesso()) return $redir;
        $usuarios = Usuario::orderBy('nome', 'ASC')->get();
        return view('pontoeletronico/usuario/index')->with('usuarios', $usuarios);
    }

    public function novo(){
        if($redir = $this->_bloqueiaSemAcesso()) return $redir;
        return view('pontoeletronico/usuario/data');
    }

    public function editar($id){
        if($redir = $this->_bloqueiaSemAcesso()) return $redir;
        $usuario = Usuario::find($id);
        return view('pontoeletronico/usuario/data')->with('u', $usuario);
    }

    public function salvar(){
        if($redir = $this->_bloqueiaSemAcesso()) return $redir;

        if(Request::input('id') AND Request::input('id') > 0):
            $usuario = Usuario::find(Request::input('id'));
        else:
            $usuario = new Usuario();
        endif;

        $senha   = Request::input('senha');
        $resenha = Request::input('resenha');

        if(!empty($senha)):
            if($senha !== $resenha):
                Session::put('status.msg', 'A senha e a confirmação não conferem.');
                return redirect(getenv('APP_URL').'/painel/usuarios');
            endif;
            if(strlen($senha) < 6):
                Session::put('status.msg', 'A senha deve ter pelo menos 6 caracteres.');
                return redirect(getenv('APP_URL').'/painel/usuarios');
            endif;
            $usuario->senha = Hash::make($senha);
        endif;

        $cpf_banco   = Request::input('cpf_banco');
        $email_banco = Request::input('email_banco');
        $email       = Request::input('email');
        $cpf         = Request::input('cpf');

        $cpf_banco = str_replace(['.', '-'], '', $cpf_banco);
        $cpf       = str_replace(['.', '-'], '', $cpf);

        $ativo   = (NULL !== Request::input('ativo')) ? 1 : 0;
        $admin   = (NULL !== Request::input('admin')) ? 1 : 0;
        $gerente = (NULL !== Request::input('gerente')) ? 1 : 0;
        $rh      = (NULL !== Request::input('rh')) ? 1 : 0;

        // RH (não-admin) não pode atribuir nem remover o acesso de
        // Administrador de ninguém — só um admin de verdade mexe nessa flag.
        if(!$this->_souAdminReal() AND $admin != (int) $usuario->admin):
            Session::put('status.msg', 'Apenas administradores podem alterar o acesso de Administrador de um colaborador.');
            return redirect(getenv('APP_URL').'/painel/usuarios');
        endif;

        // Um administrador não pode remover o próprio acesso ao painel —
        // evita ficar todo mundo trancado pra fora por engano.
        $logado_id = Session::get('login.ponto.painel.usuario_id');
        if($usuario->id AND $usuario->id == $logado_id AND $usuario->admin == 1 AND $admin == 0):
            Session::put('status.msg', 'Você não pode remover seu próprio acesso de administrador.');
            return redirect(getenv('APP_URL').'/painel/usuarios');
        endif;

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

        $usuario->nome      = Request::input('nome');
        $usuario->email     = $email;
        $usuario->cpf       = $cpf;
        $usuario->matricula = Request::input('matricula') ?? '';
        $usuario->cargo     = Request::input('cargo');
        $usuario->local     = Request::input('local');
        $usuario->ativo     = $ativo;
        $usuario->admin     = $admin;
        $usuario->gerente   = $gerente;
        $usuario->rh        = $rh;

        // Upload de foto — mesmo padrão do upload de logo que funciona
        if (isset($_FILES['foto']) && !empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK):
            $arquivo = $_FILES['foto'];
            $ext     = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, ['jpg', 'jpeg', 'png'])):
                Session::put('status.msg', 'Formato de foto inválido. Use JPG ou PNG.');
                return redirect(getenv('APP_URL').'/painel/usuarios');
            endif;

            // Confere o conteúdo real do arquivo, não só a extensão do nome —
            // evita que um arquivo disfarçado (ex: .php renomeado pra .jpg) seja aceito.
            $infoImagem = @getimagesize($arquivo['tmp_name']);
            if ($infoImagem === false || !in_array($infoImagem['mime'], ['image/jpeg', 'image/png'])):
                Session::put('status.msg', 'Arquivo inválido: o conteúdo não corresponde a uma imagem JPG/PNG.');
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

        if(!$usuario->autenticar($senha_atual)):
            Session::put('status.msg', 'Senha atual incorreta.');
            return redirect(getenv('APP_URL').'/painel/minha-senha');
        endif;

        if($nova_senha !== $confirmar):
            Session::put('status.msg', 'Nova senha e confirmação não conferem.');
            return redirect(getenv('APP_URL').'/painel/minha-senha');
        endif;

        if(strlen($nova_senha) < 6):
            Session::put('status.msg', 'A nova senha deve ter pelo menos 6 caracteres.');
            return redirect(getenv('APP_URL').'/painel/minha-senha');
        endif;

        $usuario->senha = Hash::make($nova_senha);
        $usuario->save();

        Session::put('status.msg', 'Senha alterada com sucesso!');
        return redirect(getenv('APP_URL').'/painel/minha-senha');
    }

    public function desabilitar($id){
        if($redir = $this->_bloqueiaSemAcesso()) return $redir;

        $usuario = Usuario::find($id);

        if($usuario->admin == 1 AND !$this->_souAdminReal()):
            Session::put('status.msg', 'Você não pode desabilitar um usuário administrador.');
            return redirect(getenv('APP_URL').'/painel/usuarios');
        endif;

        $usuario->ativo = 0;
        $usuario->save();
        Session::put('status.msg', 'Usuário desabilitado com sucesso!');
        return redirect(getenv('APP_URL').'/painel/usuarios');
    }

    public function habilitar($id){
        if($redir = $this->_bloqueiaSemAcesso()) return $redir;
        $usuario = Usuario::find($id);
        $usuario->ativo = 1;
        $usuario->save();
        Session::put('status.msg', 'Usuário ativado com sucesso!');
        return redirect(getenv('APP_URL').'/painel/usuarios');
    }

    public function excluir($id){
        if($redir = $this->_bloqueiaSemAcesso()) return $redir;

        $usuario = Usuario::find($id);

        if($usuario AND $usuario->admin == 1 AND !$this->_souAdminReal()):
            Session::put('status.msg', 'Você não pode excluir um usuário administrador.');
            return redirect(getenv('APP_URL').'/painel/usuarios');
        endif;

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
