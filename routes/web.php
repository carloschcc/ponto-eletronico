<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/
Route::group(['namespace' => 'PontoEletronico'], function()
{
  Route::get('/', 'IndexController@index');

  Route::post('/login', 'LoginController@login');
  Route::post('/registrar', 'PontoController@registrar_validando');
  Route::get('/registrar', 'PontoController@registrar');
  Route::get('/dashboard', 'DashboardController@index');

  Route::get('/sair', 'LoginController@sair');

});

Route::group(['prefix' => 'painel', 'namespace' => 'PontoEletronico'], function()
{
  Route::post('/login', 'LoginPainelController@login');

  Route::get('/', 'IndexPainelController@index');

  Route::get('/dashboard', 'DashboardPainelController@index');

  Route::get('/usuarios', 'UsuarioController@index');
  Route::get('/setup/foto', 'UsuarioController@setupFoto');
  Route::get('/usuario/novo', 'UsuarioController@novo');
  Route::get('/usuario/editar/{id}', 'UsuarioController@editar');
  Route::get('/usuario/excluir/{id}', 'UsuarioController@excluir');
  Route::get('/usuario/desabilitar/{id}', 'UsuarioController@desabilitar');
  Route::get('/usuario/habilitar/{id}', 'UsuarioController@habilitar');
  Route::post('/usuario/salvar', 'UsuarioController@salvar');

  Route::get('/minha-senha', 'UsuarioController@minhaSenha');
  Route::post('/minha-senha/salvar', 'UsuarioController@minhaSenhaSalvar');

  Route::get('/acompanhamento', 'AcompanhamentoController@index');
  Route::post('/acompanhamento', 'AcompanhamentoController@index');
  Route::post('/ponto/salvar', 'PontoPainelController@ajuste');
  Route::post('/ponto/periodo/salvar', 'PontoAjusteController@salvar');
  Route::get('/ponto/excluir-campo/{ponto_id}/{tipo}', 'PontoPainelController@excluirCampo');

  Route::get('/ajuste', 'PontoAjusteController@index');
  Route::get('/ajuste/excluir/{id}', 'PontoAjusteController@delete');
  Route::get('/ajuste/admin/excluir/{id}', 'PontoAjusteController@excluirAdmin');

  Route::get('/certificacao', 'PontoAjusteController@index');
  Route::post('/certificacao/salvar', 'PontoAjusteController@certificar');
  Route::post('/certificacao/bulk', 'PontoAjusteController@certificarBulk');

  Route::get('/excel-acompanhamento/{usuario}/{inicio}/{fim}', 'AcompanhamentoController@index_download');
  Route::get('/export-txt/{usuario}/{inicio}/{fim}', 'AcompanhamentoController@exportarTxt');
  Route::get('/relatorio/{usuario_id}/{inicio}/{fim}', 'AcompanhamentoController@relatorio');
  Route::get('/espelho-v2/{usuario_id}/{inicio}/{fim}', 'AcompanhamentoController@espelhoV2');

  Route::post('/feriado/salvar', 'PeriodoController@salvarFeriado');
  Route::get('/feriado/excluir/{id}', 'PeriodoController@excluirFeriado');

  Route::post('/configuracao/logo-espelho', 'ConfiguracaoController@salvarLogoEspelho');

  Route::get('/periodo', 'PeriodoController@index');
  Route::post('/periodo/salvar', 'PeriodoController@salvar');
  Route::get('/periodo/excluir/{id}', 'PeriodoController@excluir');
  Route::get('/periodo/ativar/{id}', 'PeriodoController@ativar');
  Route::get('/periodo/desativar/{id}', 'PeriodoController@desativar');

  Route::get('/configuracao', 'ConfiguracaoController@index');
  Route::post('/configuracao/logo', 'ConfiguracaoController@salvarLogo');

  Route::get('/sair', 'LoginPainelController@sair');

});
