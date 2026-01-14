<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
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
Route::get('/programs', function () {
    return view('landing.programs');
})->name('programs');
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
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

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
    });
});
