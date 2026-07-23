@extends('pontoeletronico.painel')

@section('conteudo')

<!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        Empregador
      </h1>
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="row">
        <!-- left column -->
        <div class="col-md-12">
          <!-- general form elements -->
          <div class="box box-primary">
            <div class="box-header with-border">
              <h3 class="box-title">Identificação do Empregador</h3>
            </div>
            <!-- /.box-header -->
            <!-- form start -->

            <form method="POST" action="{{ getenv('APP_URL') }}/painel/empregador/salvar">
                {{ csrf_field() }}

                <div class="box-body">

                        <p class="text-muted" style="font-size:11px; margin-bottom:15px;">
                            Esses dados identificam o empregador no arquivo de ponto exportado (Portaria MTP nº 671/2021 / 271/2021), junto de cada marcação registrada.
                        </p>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Tipo de pessoa</label><br>
                                    <label class="radio-inline">
                                        <input type="radio" name="tipo_pessoa" value="juridica" id="tipoJuridica" {{ $tipo_pessoa != 'fisica' ? 'checked' : '' }}> Pessoa Jurídica
                                    </label>
                                    <label class="radio-inline">
                                        <input type="radio" name="tipo_pessoa" value="fisica" id="tipoFisica" {{ $tipo_pessoa == 'fisica' ? 'checked' : '' }}> Pessoa Física
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label id="labelNome">{{ $tipo_pessoa == 'fisica' ? 'Nome completo' : 'Razão Social' }}</label>
                                    <input type="text" name="nome" id="campoNome" maxlength="150" value="{{ $nome }}" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label id="labelDocumento">{{ $tipo_pessoa == 'fisica' ? 'CPF' : 'CNPJ' }}</label>
                                    <input type="text" name="documento" id="campoDocumento" value="{{ $documento }}" class="form-control documento-mask" required>
                                </div>
                            </div>
                        </div>

                        <h4><i class="fa fa-map-marker"></i> Endereço</h4>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>Logradouro</label>
                                    <input type="text" name="endereco" maxlength="150" value="{{ $endereco }}" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Número</label>
                                    <input type="text" name="numero" maxlength="20" value="{{ $numero }}" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Complemento</label>
                                    <input type="text" name="complemento" maxlength="100" value="{{ $complemento }}" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Bairro</label>
                                    <input type="text" name="bairro" maxlength="100" value="{{ $bairro }}" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label>Cidade</label>
                                    <input type="text" name="cidade" maxlength="100" value="{{ $cidade }}" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>UF</label>
                                    <input type="text" name="uf" maxlength="2" style="text-transform:uppercase;" value="{{ $uf }}" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>CEP</label>
                                    <input type="text" name="cep" value="{{ $cep }}" class="form-control cep-mask">
                                </div>
                            </div>
                        </div>

                </div>

                <div class="box-footer">
                    <button type="submit" class="btn btn-primary pull-right"><i class="fa fa-save"></i> Salvar</button>
                </div>

            </form>

          </div>
          <!-- /.box -->

        </div>
        <!--/.col (left) -->
      </div>
      <!-- /.row -->
    </section>
    <!-- /.content -->

@endsection

@push('scripts')
<script>
$(function () {

    function aplicarMascaraDocumento() {
        if ($('#tipoFisica').is(':checked')) {
            $('#labelDocumento').text('CPF');
            $('#labelNome').text('Nome completo');
            $('#campoDocumento').mask('000.000.000-00');
        } else {
            $('#labelDocumento').text('CNPJ');
            $('#labelNome').text('Razão Social');
            $('#campoDocumento').mask('00.000.000/0000-00');
        }
    }

    $('.cep-mask').mask('00000-000');
    $('input[name=tipo_pessoa]').on('change', aplicarMascaraDocumento);
    aplicarMascaraDocumento();

});
</script>
@endpush
