<?php

use App\Http\Controllers\Web\PainelController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PainelController::class, 'index'])->name('painel.index');
Route::get('/fiis', [PainelController::class, 'fiis'])->name('painel.fiis');
Route::get('/etfs', [PainelController::class, 'etfs'])->name('painel.etfs');

Route::get('/calculo-cotas', [PainelController::class, 'calculoCotas'])->name('painel.calculo');

Route::get('/adicionar', [PainelController::class, 'formAdicionar'])->name('painel.adicionar');
Route::post('/adicionar', [PainelController::class, 'adicionar'])->name('painel.adicionar.store');

Route::get('/acoes/{acao}', [PainelController::class, 'show'])->name('painel.show');
