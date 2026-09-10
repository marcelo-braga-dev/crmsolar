<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsConsultor
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isConsultor()) {
            abort(403, 'Acesso negado.');
        }

        return $next($request);
    }
}
