<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->header('_token') !== getenv('TOKEN')) {
            return response('Invalid token', 403);
        }

        return $next($request);
    }
}