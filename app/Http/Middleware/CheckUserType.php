<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserType
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$types): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $userType = auth()->user()->user_type;

        if (! in_array($userType, $types)) {
            abort(403, 'Unauthorized access.');
        }

        return $next($request);
    }
}
