<?php

use App\Http\Controllers\Siga\SigaPessoaController;
use App\Http\Controllers\Siga\SigaProfissionalController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;



Route::get('/test', function (Request $request) {
    return Log::info('teste de log');
});

Route::prefix('siga')->group(function () {
    Route::get('/buscar-pessoa', [SigaPessoaController::class, 'buscarPessoa']);
    Route::get('/buscar-profissional', [SigaProfissionalController::class, 'buscarProfissional']);
});

Route::fallback(fn() => response(["message" => 'Página não encontrada'], 404));

