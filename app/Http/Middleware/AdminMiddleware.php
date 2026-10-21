<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // $payload = JWTAuth::parseToken()->getPayload();
        //     if ($payload['user']['role'] == 'Administrador') {
        //         return $next($request);
        //     }
        //     Log::warning('Acesso nao autorizado. Apenas administradores.');
        //     return response()->json(['erro' => 'Acesso não autorizado. Apenas administradores.'], 403);

        // } catch (JWTException $e) {
        //     return response()->json(['erro' => 'Token inválido', 'msg' => $e->getMessage()], 401);
        // }
        // if (auth()->user()->role_id !== 1) {
        //     Log::warning('Acesso nao autorizado. Apenas administradores.');
        //     return response()->json(['erro' => 'Acesso não autorizado.'], 403);
        // }
        // return $next($request);
         if (Gate::allows('Gate-Admin')) { //nome do gate
            return $next($request);
        }

        // Permissão negada
        Log::warning('Acesso nao autorizado. Apenas administradores.');
        abort(403, 'Acesso não autorizado.');
    }
}
