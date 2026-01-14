<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleRedirectController extends Controller
{
    public function redirect(Request $request)
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        $redirectUrl = $this->getRedirectUrl($user);

        // Store intended URL if present
        if ($request->has('intended')) {
            $intended = $request->input('intended');
            // Validate that intended URL is safe (same domain)
            if ($this->isSafeUrl($intended)) {
                return redirect($intended);
            }
        }

        return redirect($redirectUrl);
    }

    protected function getRedirectUrl($user): string
    {
        $redirects = [
            'student' => route('student.dashboard'),
            'parent' => route('parent.dashboard'),
            'teacher' => route('teacher.dashboard'),
            'admin' => route('admin.dashboard'),
            'staff' => route('staff.dashboard'),
        ];

        // Default redirect
        $defaultRoute = route('home');

        // Get user type redirect
        $userTypeRedirect = $redirects[$user->user_type] ?? $defaultRoute;

        // Check department-based redirects for staff
        if ($user->user_type === 'staff' || $user->user_type === 'admin') {
            $departmentRedirect = $this->getDepartmentRedirect($user);
            if ($departmentRedirect) {
                return $departmentRedirect;
            }
        }

        return $userTypeRedirect;
    }

    protected function getDepartmentRedirect($user): ?string
    {
        // This would check user department and return appropriate dashboard
        // For now, return null to use default
        if (isset($user->department)) {
            $departmentRoutes = [
                'it' => route('admin.dashboard'), // IT gets admin access
                'sales' => route('sales.dashboard'),
                'marketing' => route('marketing.dashboard'),
                'hr' => route('hr.dashboard'),
                'finance' => route('finance.dashboard'),
                'academic' => route('academic.dashboard'),
            ];

            return $departmentRoutes[$user->department] ?? null;
        }

        return null;
    }

    protected function isSafeUrl(string $url): bool
    {
        // Basic check to prevent open redirect
        $parsed = parse_url($url);

        // Allow relative URLs
        if (! isset($parsed['host'])) {
            return true;
        }

        // Allow same domain
        $currentHost = parse_url(config('app.url'), PHP_URL_HOST);

        return $parsed['host'] === $currentHost;
    }
}
