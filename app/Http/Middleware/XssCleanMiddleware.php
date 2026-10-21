<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

use App\Interfaces\SanitizerInterface;
use Symfony\Component\HttpFoundation\Response;

class XssCleanMiddleware
{
    protected SanitizerInterface $sanitizer;
    public function __construct(SanitizerInterface $sanitizer)
    {
        $this->sanitizer = $sanitizer;
    }
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $method = $request->method();

    if ($method === 'GET') {
        $sanitized = $this->sanitizer->cleanArray($request->query());
        $request->query->replace($sanitized);

    // Se for POST, PUT, PATCH etc, sanitiza o corpo da requisição
    } else {
        $sanitized = $this->sanitizer->cleanArray($request->all());
        $request->merge($sanitized);
    }
        return $next($request);
    }
}
