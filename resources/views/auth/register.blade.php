@extends('layouts.landing')

@section('title', 'Daftar - Sibali.id')

@section('content')
    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <div class="flex justify-center">
                    <span class="text-4xl font-bold text-sibali-maroon">Sibali</span>
                    <span class="text-4xl font-bold text-sibali-blue">.id</span>
                </div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Daftar Akun Baru
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Sudah punya akun?
                    <a href="{{ route('login') }}" class="font-medium text-sibali-blue hover:text-sibali-maroon">
                        Masuk di sini
                    </a>
                </p>
            </div>

            <form class="mt-8 space-y-6" method="POST" action="{{ route('register') }}">
                @csrf

                @if ($errors->any())
                    <div class="rounded-md bg-red-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">
                                    Terjadi kesalahan:
                                </h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <ul class="list-disc pl-5 space-y-1">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="space-y-4">
                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                        <input id="name" name="name" type="text" required
                            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-sibali-blue focus:border-sibali-blue sm:text-sm"
                            placeholder="Nama Lengkap" value="{{ old('name') }}">
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input id="email" name="email" type="email" autocomplete="email" required
                            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-sibali-blue focus:border-sibali-blue sm:text-sm"
                            placeholder="email@example.com" value="{{ old('email') }}">
                    </div>

                    <!-- Phone -->
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">Nomor Telepon</label>
                        <input id="phone" name="phone" type="tel" required
                            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-sibali-blue focus:border-sibali-blue sm:text-sm"
                            placeholder="08xxxxxxxxxx" value="{{ old('phone') }}">
                    </div>

                    <!-- User Type -->
                    <div>
                        <label for="user_type" class="block text-sm font-medium text-gray-700">Daftar Sebagai</label>
                        <select id="user_type" name="user_type" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-sibali-blue focus:border-sibali-blue sm:text-sm"
                            onchange="toggleStudentFields()">
                            <option value="">Pilih...</option>
                            <option value="student" {{ old('user_type') == 'student' ? 'selected' : '' }}>Siswa</option>
                            <option value="parent" {{ old('user_type') == 'parent' ? 'selected' : '' }}>Orang Tua/Wali
                            </option>
                        </select>
                    </div>

                    <!-- Student Fields (conditional) -->
                    <div id="student-fields" class="space-y-4" style="display: none;">
                        <div>
                            <label for="birthdate" class="block text-sm font-medium text-gray-700">Tanggal Lahir</label>
                            <input id="birthdate" name="birthdate" type="date"
                                class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 text-gray-900 rounded-md focus:outline-none focus:ring-sibali-blue focus:border-sibali-blue sm:text-sm"
                                value="{{ old('birthdate') }}">
                        </div>

                        <div>
                            <label for="education_level" class="block text-sm font-medium text-gray-700">Tingkat
                                Pendidikan</label>
                            <select id="education_level" name="education_level"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-sibali-blue focus:border-sibali-blue sm:text-sm">
                                <option value="">Pilih...</option>
                                <option value="SD" {{ old('education_level') == 'SD' ? 'selected' : '' }}>SD/Sederajat
                                </option>
                                <option value="SMP" {{ old('education_level') == 'SMP' ? 'selected' : '' }}>
                                    SMP/Sederajat</option>
                                <option value="SMA" {{ old('education_level') == 'SMA' ? 'selected' : '' }}>
                                    SMA/Sederajat</option>
                                <option value="Mahasiswa" {{ old('education_level') == 'Mahasiswa' ? 'selected' : '' }}>
                                    Mahasiswa</option>
                                <option value="Umum" {{ old('education_level') == 'Umum' ? 'selected' : '' }}>Umum
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <input id="password" name="password" type="password" autocomplete="new-password" required
                            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-sibali-blue focus:border-sibali-blue sm:text-sm"
                            placeholder="Minimal 8 karakter">
                        <p class="mt-1 text-xs text-gray-500">Password harus mengandung huruf besar, huruf kecil, angka, dan
                            simbol</p>
                    </div>

                    <!-- Password Confirmation -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Konfirmasi
                            Password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password"
                            autocomplete="new-password" required
                            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-sibali-blue focus:border-sibali-blue sm:text-sm"
                            placeholder="Ulangi password">
                    </div>

                    <!-- Terms -->
                    <div class="flex items-start">
                        <input id="terms" name="terms" type="checkbox" required
                            class="h-4 w-4 text-sibali-blue focus:ring-sibali-blue border-gray-300 rounded mt-1">
                        <label for="terms" class="ml-2 block text-sm text-gray-900">
                            Saya setuju dengan <a href="#" class="text-sibali-blue hover:text-sibali-maroon">Syarat
                                & Ketentuan</a> dan <a href="#"
                                class="text-sibali-blue hover:text-sibali-maroon">Kebijakan Privasi</a>
                        </label>
                    </div>
                </div>

                <div>
                    <button type="submit"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-sibali-blue hover:bg-sibali-maroon focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sibali-blue transition">
                        Daftar Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleStudentFields() {
            const userType = document.getElementById('user_type').value;
            const studentFields = document.getElementById('student-fields');
            const birthdate = document.getElementById('birthdate');
            const educationLevel = document.getElementById('education_level');

            if (userType === 'student') {
                studentFields.style.display = 'block';
                birthdate.required = true;
                educationLevel.required = true;
            } else {
                studentFields.style.display = 'none';
                birthdate.required = false;
                educationLevel.required = false;
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleStudentFields();
        });
    </script>
@endsection
