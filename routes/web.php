<?php

use App\Http\Controllers\Auth\EnhancedLoginController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\RoleRedirectController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Dashboard\AdminDashboardController;
use App\Http\Controllers\Dashboard\ParentDashboardController;
use App\Http\Controllers\Dashboard\StudentDashboardController;
use App\Http\Controllers\LandingPage\HomeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Landing Page Routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/programs', [HomeController::class, 'programs'])->name('programs');
Route::get('/programs/{slug}', [HomeController::class, 'programDetail'])->name('program.detail');
Route::get('/about', function () {
    return view('landing.about');
})->name('about');
Route::get('/blog', function () {
    return view('landing.blog');
})->name('blog');
Route::get('/contact', function () {
    return view('landing.contact');
})->name('contact');

// Authentication Routes
Route::middleware('guest')->group(function () {
    // Enhanced Login Routes
    Route::get('/login', [EnhancedLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [EnhancedLoginController::class, 'login'])->name('enhanced.login');

    // Legacy Login (fallback)
    Route::get('/login/basic', [LoginController::class, 'showLoginForm'])->name('login.basic');
    Route::post('/login/basic', [LoginController::class, 'login'])->name('login.basic.post');

    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);

    // Password Reset Routes
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Post-login redirect
Route::middleware('auth')->get('/redirect', [RoleRedirectController::class, 'redirect'])->name('auth.redirect');

// Dashboard Routes (Protected)
Route::middleware(['auth'])->group(function () {
    // Auto redirect to appropriate dashboard
    Route::get('/dashboard', function () {
        $user = auth()->user();
        switch ($user->user_type) {
            case 'student':
                return redirect()->route('student.dashboard');
            case 'parent':
                return redirect()->route('parent.dashboard');
            case 'teacher':
                return redirect()->route('teacher.dashboard');
            case 'admin':
            case 'staff':
                return redirect()->route('admin.dashboard');
            default:
                return redirect()->route('home');
        }
    })->name('dashboard');

    // Student Dashboard
    Route::prefix('student')->name('student.')->group(function () {
        Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
        Route::get('/classes', [StudentDashboardController::class, 'classes'])->name('classes');
        Route::get('/assignments', [StudentDashboardController::class, 'assignments'])->name('assignments');
        Route::get('/grades', [StudentDashboardController::class, 'grades'])->name('grades');
        Route::get('/payments', [StudentDashboardController::class, 'payments'])->name('payments');
    });

    // Parent Dashboard
    Route::prefix('parent')->name('parent.')->group(function () {
        Route::get('/dashboard', [ParentDashboardController::class, 'index'])->name('dashboard');
        Route::get('/children', [ParentDashboardController::class, 'children'])->name('children');
    });

    // Admin Dashboard
    Route::prefix('admin')->name('admin.')->middleware('role:admin,staff')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/users', [UserManagementController::class, 'index'])->name('users');
    });
});
