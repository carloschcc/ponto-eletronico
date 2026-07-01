@extends('pontoeletronico.painel')

@section('conteudo')

<section class="content-header">
  <h1>Alterar Senha</h1>
</section>

<section class="content">
  <div class="row">
    <div class="col-md-6">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Alterar minha senha</h3>
        </div>
        <form method="POST" action="/painel/minha-senha/salvar">
          {{ csrf_field() }}
          <div class="box-body">

            <div class="form-group">
              <label>Senha atual</label>
              <input type="password" name="senha_atual" class="form-control" placeholder="Digite sua senha atual" required autofocus>
            </div>

            <div class="form-group">
              <label>Nova senha</label>
              <input type="password" name="nova_senha" id="nova_senha" class="form-control" placeholder="Nova senha (mínimo 4 caracteres)" required>
            </div>

            <div class="form-group">
              <label>Confirmar nova senha</label>
              <input type="password" name="confirmar" id="confirmar" class="form-control" placeholder="Repita a nova senha" required>
              <small id="msg-senha" style="color:red;display:none;">As senhas não conferem.</small>
            </div>

          </div>
          <div class="box-footer">
            <a href="/painel/dashboard" class="btn btn-default">Cancelar</a>
            <button type="submit" class="btn btn-primary pull-right">Salvar nova senha</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>

@push('scripts')
<script>
$('#confirmar').on('input', function(){
    var ok = $(this).val() === $('#nova_senha').val();
    $('#msg-senha').toggle(!ok);
});
$('form').on('submit', function(e){
    if($('#confirmar').val() !== $('#nova_senha').val()){
        e.preventDefault();
        $('#msg-senha').show();
    }
});
</script>
@endpush

@endsection
