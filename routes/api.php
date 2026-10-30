<?php

use App\Http\Controllers\AcaoController;
use App\Http\Controllers\AporteController;
use App\Http\Controllers\TestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
// Route::get('/test', function (Request $request) {
//     return Log::info('teste de log');
// });



Route::fallback(fn() => response(["message" => 'Página não encontrada'], 404));

Route::get('acao/{acao}/cotacoes', [AcaoController::class, 'cotacoes']);
Route::get('acao/{acao}/historico', [AcaoController::class, 'historico']);
Route::apiResource('acao', AcaoController::class);

Route::get('/test', [TestController::class, 'index']);

Route::apiResource('aporte', AporteController::class);
