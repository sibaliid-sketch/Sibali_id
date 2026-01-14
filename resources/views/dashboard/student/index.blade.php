@extends('layouts.app')

@section('title', 'Dashboard Siswa - Sibali.id')

@section('content')
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <div class="bg-white shadow">
            <div class="container mx-auto px-4 py-6">
                <h1 class="text-2xl font-bold text-sibali-maroon">Dashboard Siswa</h1>
                <p class="text-gray-600">Selamat datang, {{ $student->name }}!</p>
            </div>
        </div>

        <div class="container mx-auto px-4 py-8">
            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Kehadiran</p>
                            <p class="text-2xl font-bold text-sibali-blue">{{ $attendance_summary['rate'] }}%</p>
                        </div>
                        <div class="w-12 h-12 bg-sibali-sky rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-sibali-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Rata-rata Nilai</p>
                            <p class="text-2xl font-bold text-sibali-blue">
                                {{ number_format($progress['grade_average'], 1) }}</p>
                        </div>
                        <div class="w-12 h-12 bg-sibali-sky rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-sibali-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                </path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Tugas Pending</p>
                            <p class="text-2xl font-bold text-sibali-blue">{{ count($assignments) }}</p>
                        </div>
                        <div class="w-12 h-12 bg-sibali-sky rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-sibali-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                                </path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Progress</p>
                            <p class="text-2xl font-bold text-sibali-blue">
                                {{ number_format($progress['completion_rate'], 0) }}%</p>
                        </div>
                        <div class="w-12 h-12 bg-sibali-sky rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-sibali-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Upcoming Classes -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-xl font-bold text-sibali-maroon">Kelas Mendatang</h2>
                        </div>
                        <div class="p-6">
                            @forelse($upcoming_classes as $class)
                                <div class="flex items-center justify-between py-4 border-b border-gray-100 last:border-0">
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-900">{{ $class->name }}</h3>
                                        <p class="text-sm text-gray-500">Pengajar: {{ $class->teacher_name }}</p>
                                        <p class="text-sm text-gray-500">{{ $class->schedule }}</p>
                                    </div>
                                    <a href="{{ route('student.classes.show', $class->id) }}"
                                        class="text-sibali-blue hover:text-sibali-maroon transition">
                                        Lihat Detail →
                                    </a>
                                </div>
                            @empty
                                <p class="text-gray-500 text-center py-8">Belum ada kelas terdaftar</p>
                            @endforelse
                        </div>
                    </div>

                    <!-- Pending Assignments -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-xl font-bold text-sibali-maroon">Tugas yang Harus Dikerjakan</h2>
                        </div>
                        <div class="p-6">
                            @forelse($assignments as $assignment)
                                <div class="flex items-center justify-between py-4 border-b border-gray-100 last:border-0">
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-900">{{ $assignment->title }}</h3>
                                        <p class="text-sm text-gray-500">Deadline:
                                            {{ \Carbon\Carbon::parse($assignment->due_date)->format('d M Y, H:i') }}</p>
                                        @if (\Carbon\Carbon::parse($assignment->due_date)->isPast())
                                            <span
                                                class="inline-block px-2 py-1 text-xs bg-red-100 text-red-800 rounded">Terlambat</span>
                                        @elseif(\Carbon\Carbon::parse($assignment->due_date)->diffInDays(now()) <= 1)
                                            <span
                                                class="inline-block px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded">Segera</span>
                                        @endif
                                    </div>
                                    <a href="{{ route('student.assignments.show', $assignment->id) }}"
                                        class="bg-sibali-blue text-white px-4 py-2 rounded hover:bg-sibali-maroon transition">
                                        Kerjakan
                                    </a>
                                </div>
                            @empty
                                <p class="text-gray-500 text-center py-8">Tidak ada tugas pending</p>
                            @endforelse
                        </div>
                    </div>

                    <!-- Recent Grades -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-xl font-bold text-sibali-maroon">Nilai Terbaru</h2>
                        </div>
                        <div class="p-6">
                            @forelse($recent_grades as $grade)
                                <div class="flex items-center justify-between py-4 border-b border-gray-100 last:border-0">
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-900">{{ $grade->class_name }}</h3>
                                        <p class="text-sm text-gray-500">
                                            {{ \Carbon\Carbon::parse($grade->created_at)->format('d M Y') }}</p>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-2xl font-bold text-sibali-blue">{{ $grade->grade_value }}</div>
                                        <div class="text-sm text-gray-500">{{ $grade->grade_letter }}</div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-gray-500 text-center py-8">Belum ada nilai</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Notifications -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-xl font-bold text-sibali-maroon">Notifikasi</h2>
                        </div>
                        <div class="p-6">
                            @forelse($notifications as $notification)
                                <div class="py-3 border-b border-gray-100 last:border-0">
                                    <p class="text-sm text-gray-900">{{ $notification->message }}</p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ \Carbon\Carbon::parse($notification->created_at)->diffForHumans() }}</p>
                                </div>
                            @empty
                                <p class="text-gray-500 text-center py-4">Tidak ada notifikasi</p>
                            @endforelse
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-xl font-bold text-sibali-maroon">Aksi Cepat</h2>
                        </div>
                        <div class="p-6 space-y-3">
                            <a href="{{ route('student.classes') }}"
                                class="block w-full bg-sibali-blue text-white text-center px-4 py-2 rounded hover:bg-sibali-maroon transition">
                                Lihat Semua Kelas
                            </a>
                            <a href="{{ route('student.assignments') }}"
                                class="block w-full bg-white border-2 border-sibali-blue text-sibali-blue text-center px-4 py-2 rounded hover:bg-sibali-blue hover:text-white transition">
                                Lihat Semua Tugas
                            </a>
                            <a href="{{ route('student.grades') }}"
                                class="block w-full bg-white border-2 border-sibali-blue text-sibali-blue text-center px-4 py-2 rounded hover:bg-sibali-blue hover:text-white transition">
                                Lihat Nilai
                            </a>
                            <a href="{{ route('student.payments') }}"
                                class="block w-full bg-white border-2 border-sibali-blue text-sibali-blue text-center px-4 py-2 rounded hover:bg-sibali-blue hover:text-white transition">
                                Riwayat Pembayaran
                            </a>
                        </div>
                    </div>

                    <!-- Progress Chart -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="font-bold text-sibali-maroon mb-4">Progress Belajar</h3>
                        <div class="space-y-4">
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-gray-600">Penyelesaian Kursus</span>
                                    <span
                                        class="font-semibold text-sibali-blue">{{ number_format($progress['completion_rate'], 0) }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-sibali-blue h-2 rounded-full"
                                        style="width: {{ $progress['completion_rate'] }}%"></div>
                                </div>
                            </div>

                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-gray-600">Kehadiran</span>
                                    <span class="font-semibold text-sibali-blue">{{ $attendance_summary['rate'] }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-500 h-2 rounded-full"
                                        style="width: {{ $attendance_summary['rate'] }}%"></div>
                                </div>
                            </div>

                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-gray-600">Rata-rata Nilai</span>
                                    <span
                                        class="font-semibold text-sibali-blue">{{ number_format($progress['grade_average'], 1) }}</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-yellow-500 h-2 rounded-full"
                                        style="width: {{ $progress['grade_average'] }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
