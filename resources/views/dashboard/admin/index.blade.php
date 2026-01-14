@extends('layouts.app')

@section('title', 'Admin Dashboard - Sibali.id')

@section('content')
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <div class="bg-white shadow">
            <div class="container mx-auto px-4 py-6">
                <h1 class="text-2xl font-bold text-sibali-maroon">Admin Dashboard</h1>
                <p class="text-gray-600">Selamat datang, {{ auth()->user()->name }}!</p>
            </div>
        </div>

        <div class="container mx-auto px-4 py-8">
            <!-- System Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Total Pengguna</p>
                            <p class="text-2xl font-bold text-sibali-blue">{{ number_format($stats['total_users']) }}</p>
                        </div>
                        <div class="w-12 h-12 bg-sibali-sky rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-sibali-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                                </path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Siswa Aktif</p>
                            <p class="text-2xl font-bold text-sibali-blue">{{ number_format($stats['active_students']) }}
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
                                </path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Kelas Aktif</p>
                            <p class="text-2xl font-bold text-sibali-blue">{{ number_format($stats['active_classes']) }}</p>
                        </div>
                        <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                                </path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Pembayaran Pending</p>
                            <p class="text-2xl font-bold text-sibali-blue">{{ number_format($stats['pending_payments']) }}
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                </path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Revenue Summary -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow p-6 text-white">
                    <p class="text-sm opacity-90">Pendapatan Hari Ini</p>
                    <p class="text-3xl font-bold mt-2">Rp {{ number_format($revenue_summary['today'], 0, ',', '.') }}</p>
                </div>

                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow p-6 text-white">
                    <p class="text-sm opacity-90">Pendapatan Bulan Ini</p>
                    <p class="text-3xl font-bold mt-2">Rp {{ number_format($revenue_summary['this_month'], 0, ',', '.') }}
                    </p>
                </div>

                <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-lg shadow p-6 text-white">
                    <p class="text-sm opacity-90">Pending</p>
                    <p class="text-3xl font-bold mt-2">Rp {{ number_format($revenue_summary['pending'], 0, ',', '.') }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Recent Registrations -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-xl font-bold text-sibali-maroon">Pendaftaran Terbaru</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipe
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($recent_registrations as $user)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {{ $user->name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $user->email }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span
                                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                    {{ ucfirst($user->user_type) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ \Carbon\Carbon::parse($user->created_at)->format('d M Y') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pending Payment Verifications -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-xl font-bold text-sibali-maroon">Verifikasi Pembayaran Pending</h2>
                        </div>
                        <div class="p-6">
                            @forelse($pending_verifications as $payment)
                                <div class="flex items-center justify-between py-4 border-b border-gray-100 last:border-0">
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-900">{{ $payment->student_name }}</h3>
                                        <p class="text-sm text-gray-500">Rp
                                            {{ number_format($payment->amount, 0, ',', '.') }}</p>
                                        <p class="text-xs text-gray-400">
                                            {{ \Carbon\Carbon::parse($payment->created_at)->diffForHumans() }}</p>
                                    </div>
                                    <a href="{{ route('admin.payments.verify', $payment->id) }}"
                                        class="bg-sibali-blue text-white px-4 py-2 rounded hover:bg-sibali-maroon transition text-sm">
                                        Verifikasi
                                    </a>
                                </div>
                            @empty
                                <p class="text-gray-500 text-center py-8">Tidak ada pembayaran yang perlu diverifikasi</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- System Health -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-xl font-bold text-sibali-maroon">Kesehatan Sistem</h2>
                        </div>
                        <div class="p-6 space-y-4">
                            @foreach ($system_health as $component => $health)
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-700 capitalize">{{ $component }}</span>
                                    <span
                                        class="px-3 py-1 rounded-full text-xs font-semibold
                                {{ $health['status'] === 'healthy' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $health['status'] === 'warning' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $health['status'] === 'error' ? 'bg-red-100 text-red-800' : '' }}">
                                        {{ ucfirst($health['status']) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-xl font-bold text-sibali-maroon">Aksi Cepat</h2>
                        </div>
                        <div class="p-6 space-y-3">
                            <a href="{{ route('admin.users') }}"
                                class="block w-full bg-sibali-blue text-white text-center px-4 py-2 rounded hover:bg-sibali-maroon transition">
                                Kelola Pengguna
                            </a>
                            <a href="{{ route('admin.classes') }}"
                                class="block w-full bg-white border-2 border-sibali-blue text-sibali-blue text-center px-4 py-2 rounded hover:bg-sibali-blue hover:text-white transition">
                                Kelola Kelas
                            </a>
                            <a href="{{ route('admin.payments') }}"
                                class="block w-full bg-white border-2 border-sibali-blue text-sibali-blue text-center px-4 py-2 rounded hover:bg-sibali-blue hover:text-white transition">
                                Verifikasi Pembayaran
                            </a>
                            <a href="{{ route('admin.reports') }}"
                                class="block w-full bg-white border-2 border-sibali-blue text-sibali-blue text-center px-4 py-2 rounded hover:bg-sibali-blue hover:text-white transition">
                                Laporan
                            </a>
                        </div>
                    </div>

                    <!-- Recent Activities -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-xl font-bold text-sibali-maroon">Aktivitas Terbaru</h2>
                        </div>
                        <div class="p-6">
                            @forelse($recent_activities->take(5) as $activity)
                                <div class="py-3 border-b border-gray-100 last:border-0">
                                    <p class="text-sm text-gray-900">{{ $activity->user_name }}</p>
                                    <p class="text-xs text-gray-500">{{ $activity->action }}</p>
                                    <p class="text-xs text-gray-400">
                                        {{ \Carbon\Carbon::parse($activity->created_at)->diffForHumans() }}</p>
                                </div>
                            @empty
                                <p class="text-gray-500 text-center py-4">Tidak ada aktivitas</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
