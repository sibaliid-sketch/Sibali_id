@extends('layouts.landing')

@section('title', 'Login - Sibali.id')

@section('content')
<div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <!-- Logo & Title -->
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-sibali-maroon mb-2">Login</h2>
                <p class="text-gray-600">Masuk ke akun Sibali.id Anda</p>
            </div>

            <!-- Error Messages -->
            @if ($errors->any())
            <div class="alert alert-error mb-6">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <!-- Success Message -->
            @if (session('success'))
            <div class="alert alert-success mb-6">
                {{ session('success') }}
            </div>
            @endif

            <!-- Login Form -->
            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                @csrf

                <!-- Email/Phone -->
                <div>
                    <label for="email" class="form-label">Email atau Nomor Telepon</label>
                    <input
                        id="email"
                        type="text"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        class="form-input @error('email') border-red-500 @enderror"
                        placeholder="email@example.com atau 08123456789"
                    >
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="form-label">Password</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        class="form-input @error('password') border-red-500 @enderror"
                        placeholder="Masukkan password Anda"
                    >
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input
                            id="remember"
                            name="remember"
                            type="checkbox"
                            class="h-4 w-4 text-sibali-blue focus:ring-sibali-blue border-gray-300 rounded"
                        >
                        <label for="remember" class="ml-2 block text-sm text-gray-700">
                            Ingat saya
                        </label>
                    </div>

                    <div class="text-sm">
                        <a href="{{ route('password.request') }}" class="text-sibali-blue hover:text-sibali-maroon">
                            Lupa password?
                        </a>
                    </div>
                </div>

                <!-- Submit Button -->
                <div>
                    <button
                        type="submit"
                        class="w-full bg-sibali-blue text-white py-3 rounded-lg font-semibold hover:bg-sibali-maroon transition focus:outline-none focus:ring-2 focus:ring-sibali-blue focus:ring-offset-2"
                    >
                        Login
                    </button>
                </div>

                <!-- Register Link -->
                <div class="text-center">
                    <p class="text-gray-600">
                        Belum punya akun?
                        <a href="{{ route('register') }}" class="text-sibali-blue hover:text-sibali-maroon font-semibold">
                            Daftar Sekarang
                        </a>
                    </p>
                </div>
            </form>

            <!-- Social Login (Optional) -->
            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-gray-500">Atau login dengan</span>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-2 gap-3">
                    <button class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-lg shadow-sm bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 0C4.477 0 0 4.477 0 10c0 4.991 3.657 9.128 8.438 9.879V12.89h-2.54V10h2.54V7.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V10h2.773l-.443 2.89h-2.33v6.989C16.343 19.128 20 14.991 20 10c0-5.523-4.477-10-10-10z"/>
                        </svg>
                        <span class="ml-2">Facebook</span>
                    </button>

                    <button class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-lg shadow-sm bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 0C4.477 0 0 4.477 0 10c0 4.418 2.865 8.166 6.839 9.489.5.092.682-.217.682-.482 0-.237-.008-.866-.013-1.7-2.782.603-3.369-1.34-3.369-1.34-.454-1.156-1.11-1.463-1.11-1.463-.908-.62.069-.608.069-.608 1.003.07 1.531 1.03 1.531 1.03.892 1.529 2.341 1.087 2.91.831.092-.646.35-1.086.636-1.336-2.22-.253-4.555-1.11-4.555-4.943 0-1.091.39-1.984 1.029-2.683-.103-.253-.446-1.27.098-2.647 0 0 .84-.269 2.75 1.025A9.578 9.578 0 0110 4.836c.85.004 1.705.114 2.504.336 1.909-1.294 2.747-1.025 2.747-1.025.546 1.377.203 2.394.1 2.647.64.699 1.028 1.592 1.028 2.683 0 3.842-2.339 4.687-4.566 4.935.359.309.678.919.678 1.852 0 1.336-.012 2.415-.012 2.743 0 .267.18.578.688.48C17.137 18.163 20 14.418 20 10c0-5.523-4.477-10-10-10z"/>
                        </svg>
                        <span class="ml-2">Google</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
