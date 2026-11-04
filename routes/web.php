<?php

use App\Http\Controllers\Web\PainelController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PainelController::class, 'index'])->name('painel.index');
Route::get('/fiis', [PainelController::class, 'fiis'])->name('painel.fiis');
Route::get('/etfs', [PainelController::class, 'etfs'])->name('painel.etfs');

Route::get('/calculo-cotas', [PainelController::class, 'calculoCotas'])->name('painel.calculo');
Route::get('/porcentagem', [PainelController::class, 'porcentagem'])->name('painel.porcentagem');

// Carteira — movimentações (compra/venda) e posição consolidada
Route::get('/carteira', [PainelController::class, 'carteira'])->name('painel.carteira');
Route::get('/carteira/evolucao', [PainelController::class, 'evolucao'])->name('painel.carteira.evolucao');
Route::get('/movimentacoes', [PainelController::class, 'movimentacoesIndex'])->name('painel.movimentacoes.index');
Route::get('/movimentacoes/registrar', [PainelController::class, 'formMovimentacao'])->name('painel.movimentacoes.create');
Route::post('/movimentacoes/registrar', [PainelController::class, 'storeMovimentacao'])->name('painel.movimentacoes.store');
Route::get('/movimentacoes/{movimentacao}/editar', [PainelController::class, 'editMovimentacao'])->name('painel.movimentacoes.edit');
Route::put('/movimentacoes/{movimentacao}', [PainelController::class, 'updateMovimentacao'])->name('painel.movimentacoes.update');
Route::delete('/movimentacoes/{movimentacao}', [PainelController::class, 'destroyMovimentacao'])->name('painel.movimentacoes.destroy');

// URLs antigas de "Aportes" (favoritos/links salvos)
Route::redirect('/aportes/registrar', '/movimentacoes/registrar');
Route::redirect('/aportes/meus-ativos', '/carteira');
Route::redirect('/aportes/registros', '/movimentacoes');

Route::get('/adicionar', [PainelController::class, 'formAdicionar'])->name('painel.adicionar');
Route::post('/adicionar', [PainelController::class, 'adicionar'])->name('painel.adicionar.store');

// Cadastro — CRUD completo dos ativos monitorados
Route::get('/cadastro/ativos', [PainelController::class, 'ativosIndex'])->name('painel.ativos.index');
Route::get('/cadastro/ativos/{acao}', [PainelController::class, 'detalhesAtivo'])->name('painel.ativos.show');
Route::get('/cadastro/ativos/{acao}/editar', [PainelController::class, 'editAtivo'])->name('painel.ativos.edit');
Route::put('/cadastro/ativos/{acao}', [PainelController::class, 'updateAtivo'])->name('painel.ativos.update');
Route::delete('/cadastro/ativos/{acao}', [PainelController::class, 'destroyAtivo'])->name('painel.ativos.destroy');

Route::get('/acoes/{acao}', [PainelController::class, 'show'])->name('painel.show');
