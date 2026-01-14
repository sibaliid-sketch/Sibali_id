@extends('layouts.auth')

@section('title', 'Login - Sibali.id')

@section('content')
    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <img class="mx-auto h-12 w-auto" src="{{ asset('images/logo.png') }}" alt="Sibali.id">
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Masuk ke Akun Anda
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Atau
                    <a href="{{ route('register') }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                        daftar akun baru
                    </a>
                </p>
            </div>

            <form class="mt-8 space-y-6" action="{{ route('enhanced.login') }}" method="POST" id="loginForm">
                @csrf

                <!-- Identifier Field -->
                <div>
                    <label for="identifier" name="identifier" class="sr-only">Email, Username, atau Nomor Telepon</label>
                    <input id="identifier" name="identifier" type="text" autocomplete="username" required
                        class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                        placeholder="Email, Username, atau Nomor Telepon" value="{{ old('identifier') }}">
                    @error('identifier')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password Field -->
                <div>
                    <label for="password" class="sr-only">Password</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required
                        class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                        placeholder="Password">
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 2FA QR and OTP Field (hidden initially) -->
                <div id="otpField" class="hidden">
                    @if (isset($qrCode))
                        <div class="text-center mb-4">
                            <p class="text-gray-600 mb-2">Scan QR code dengan kamera ponsel Anda untuk mendapatkan kode
                                verifikasi:</p>
                            <img src="data:image/svg+xml;base64,{{ base64_encode($qrCode) }}" alt="QR Code"
                                class="mx-auto border border-gray-300 rounded">
                        </div>
                    @endif
                    <label for="otp" class="sr-only">Kode 2FA</label>
                    <input id="otp" name="otp" type="text" autocomplete="one-time-code"
                        class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                        placeholder="Kode 2FA (6 digit)" maxlength="6">
                    @error('otp')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- CAPTCHA Field (hidden initially) -->
                <div id="captchaField" class="hidden">
                    <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
                    @error('captcha')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember_me" name="remember" type="checkbox"
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="remember_me" class="ml-2 block text-sm text-gray-900">
                            Ingat saya
                        </label>
                    </div>

                    <div class="text-sm">
                        <a href="{{ route('password.request') }}"
                            class="font-medium text-indigo-600 hover:text-indigo-500">
                            Lupa password?
                        </a>
                    </div>
                </div>

                <div>
                    <button type="submit" id="loginButton"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <svg id="loginSpinner" class="h-5 w-5 text-indigo-500 group-hover:text-indigo-400 hidden"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                        </span>
                        Masuk
                    </button>
                </div>

                <!-- Status Messages -->
                @if (session('success'))
                    <div class="rounded-md bg-green-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800">
                                    {{ session('success') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="rounded-md bg-red-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <ul class="text-sm text-red-700">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <!-- reCAPTCHA Script -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');
            const loginButton = document.getElementById('loginButton');
            const loginSpinner = document.getElementById('loginSpinner');
            const otpField = document.getElementById('otpField');
            const captchaField = document.getElementById('captchaField');

            loginForm.addEventListener('submit', function(e) {
                loginButton.disabled = true;
                loginSpinner.classList.remove('hidden');

                // Show OTP field if 2FA is required
                if (document.querySelector('[name="otp"]') && document.querySelector('[name="otp"]')
                    .value === '') {
                    e.preventDefault();
                    otpField.classList.remove('hidden');
                    loginButton.disabled = false;
                    loginSpinner.classList.add('hidden');
                    return;
                }

                // Show CAPTCHA if required
                if (document.querySelector('.g-recaptcha') && !grecaptcha.getResponse()) {
                    e.preventDefault();
                    captchaField.classList.remove('hidden');
                    loginButton.disabled = false;
                    loginSpinner.classList.add('hidden');
                    return;
                }
            });

            // Auto-focus OTP field when shown
            const otpInput = document.getElementById('otp');
            if (otpInput) {
                otpInput.addEventListener('input', function() {
                    if (this.value.length === 6) {
                        loginForm.submit();
                    }
                });
            }
        });
    </script>
@endsection
