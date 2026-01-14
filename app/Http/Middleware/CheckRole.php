<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Check if user has any of the specified roles
        foreach ($roles as $role) {
            if ($this->hasRole($user, $role)) {
                return $next($request);
            }
        }

        abort(403, 'Unauthorized access. Required role: '.implode(' or ', $roles));
    }

    protected function hasRole($user, $role)
    {
        // Check user_type
        if ($user->user_type === $role) {
            return true;
        }

        // Check staff_level for staff users
        if ($role === 'staff' && in_array($user->user_type, ['staff', 'admin'])) {
            return true;
        }

        // Check admin role
        if ($role === 'admin' && $user->user_type === 'admin') {
            return true;
        }

        return false;
    }
}
