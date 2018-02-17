<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BasicAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->getUser();
        $pass = $request->getPassword();

        if (!($user === getenv('HTTP_USER') && $pass === getenv('HTTP_PASS'))) {
            $headers = ['WWW-Authenticate' => 'Basic'];

            return response('Admin Login', 401, $headers);
        }

        return $next($request);
    }
}