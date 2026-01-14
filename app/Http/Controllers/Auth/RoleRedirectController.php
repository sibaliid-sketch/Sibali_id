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

        // Store intended URL if present and safe
        if ($request->has('intended')) {
            $intended = $request->input('intended');
            if ($this->isSafeUrl($intended) && $this->isAllowedRoute($intended, $user)) {
                return redirect($intended);
            }
        }

        return redirect($redirectUrl);
    }

    protected function getRedirectUrl($user): string
    {
        // Use config/roles.php mapping and staffLevel
        $roleConfig = config('roles.roles', []);
        $redirectMap = config('roles.redirect_map', [
            'student' => route('student.dashboard'),
            'parent' => route('parent.dashboard'),
            'teacher' => route('teacher.dashboard'),
            'admin' => route('admin.dashboard'),
            'staff' => route('staff.dashboard'),
        ]);

        // Default landing
        $defaultRoute = route('home');

        // Get user type redirect
        $userTypeRedirect = $redirectMap[$user->user_type] ?? $defaultRoute;

        // Check department-based redirects for staff/admin
        if (in_array($user->user_type, ['staff', 'admin'])) {
            $departmentRedirect = $this->getDepartmentRedirect($user);
            if ($departmentRedirect) {
                return $departmentRedirect;
            }
        }

        // Check staff level for hierarchical redirects
        if ($user->user_type === 'staff' && isset($user->staff_level)) {
            $levelRedirect = $this->getStaffLevelRedirect($user);
            if ($levelRedirect) {
                return $levelRedirect;
            }
        }

        return $userTypeRedirect;
    }

    protected function getDepartmentRedirect($user): ?string
    {
        $departmentRoutes = config('roles.department_redirects', [
            'it' => route('admin.dashboard'),
            'sales' => route('sales.dashboard'),
            'marketing' => route('marketing.dashboard'),
            'hr' => route('hr.dashboard'),
            'finance' => route('finance.dashboard'),
            'academic' => route('academic.dashboard'),
            'operations' => route('operations.dashboard'),
            'engagement' => route('engagement.dashboard'),
            'public_relation' => route('pr.dashboard'),
            'product_research' => route('product.dashboard'),
        ]);

        return $departmentRoutes[$user->department] ?? null;
    }

    protected function getStaffLevelRedirect($user): ?string
    {
        $levelRoutes = config('roles.staff_level_redirects', [
            1 => route('staff.basic.dashboard'), // Basic Staff
            2 => route('staff.senior.dashboard'), // Senior Staff
            3 => route('staff.leader.dashboard'), // Leader
            4 => route('staff.supervisor.dashboard'), // Supervisor
            5 => route('staff.manager.dashboard'), // Manager
            6 => route('staff.header.dashboard'), // Header
            7 => route('executives.dashboard'), // Executives
        ]);

        return $levelRoutes[$user->staff_level] ?? null;
    }

    protected function isSafeUrl(string $url): bool
    {
        $parsed = parse_url($url);

        // Allow relative URLs
        if (! isset($parsed['host'])) {
            return true;
        }

        // Allow same domain
        $currentHost = parse_url(config('app.url'), PHP_URL_HOST);

        return $parsed['host'] === $currentHost;
    }

    protected function isAllowedRoute(string $url, $user): bool
    {
        // Check if the intended route is allowed for user's role
        // This prevents privilege escalation via intended URLs
        $allowedRoutes = config('roles.allowed_routes.'.$user->user_type, []);

        // Extract route name from URL if possible
        $routeName = $this->getRouteNameFromUrl($url);

        if ($routeName && ! in_array($routeName, $allowedRoutes)) {
            return false;
        }

        return true;
    }

    protected function getRouteNameFromUrl(string $url): ?string
    {
        try {
            $route = app('router')->getRoutes()->match(app('request')->create($url));

            return $route->getName();
        } catch (\Exception $e) {
            return null;
        }
    }
}
