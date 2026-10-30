<?php

use App\Http\Controllers\Web\PainelController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PainelController::class, 'index'])->name('painel.index');
Route::get('/fiis', [PainelController::class, 'fiis'])->name('painel.fiis');
Route::get('/etfs', [PainelController::class, 'etfs'])->name('painel.etfs');

Route::get('/calculo-cotas', [PainelController::class, 'calculoCotas'])->name('painel.calculo');
Route::get('/porcentagem', [PainelController::class, 'porcentagem'])->name('painel.porcentagem');

// Aportes — registro de compras e posição consolidada da carteira
Route::get('/aportes/registrar', [PainelController::class, 'formAporte'])->name('painel.aportes.create');
Route::post('/aportes/registrar', [PainelController::class, 'storeAporte'])->name('painel.aportes.store');
Route::get('/aportes/meus-ativos', [PainelController::class, 'meusAtivos'])->name('painel.aportes.meus');
Route::get('/aportes/registros', [PainelController::class, 'aportesIndex'])->name('painel.aportes.index');
Route::get('/aportes/{aporte}/editar', [PainelController::class, 'editAporte'])->name('painel.aportes.edit');
Route::put('/aportes/{aporte}', [PainelController::class, 'updateAporte'])->name('painel.aportes.update');
Route::delete('/aportes/{aporte}', [PainelController::class, 'destroyAporte'])->name('painel.aportes.destroy');

Route::get('/adicionar', [PainelController::class, 'formAdicionar'])->name('painel.adicionar');
Route::post('/adicionar', [PainelController::class, 'adicionar'])->name('painel.adicionar.store');

// Cadastro — CRUD completo dos ativos monitorados
Route::get('/cadastro/ativos', [PainelController::class, 'ativosIndex'])->name('painel.ativos.index');
Route::get('/cadastro/ativos/{acao}', [PainelController::class, 'detalhesAtivo'])->name('painel.ativos.show');
Route::get('/cadastro/ativos/{acao}/editar', [PainelController::class, 'editAtivo'])->name('painel.ativos.edit');
Route::put('/cadastro/ativos/{acao}', [PainelController::class, 'updateAtivo'])->name('painel.ativos.update');
Route::delete('/cadastro/ativos/{acao}', [PainelController::class, 'destroyAtivo'])->name('painel.ativos.destroy');

Route::get('/acoes/{acao}', [PainelController::class, 'show'])->name('painel.show');
