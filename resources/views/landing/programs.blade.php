@extends('layouts.landing')

@section('title', 'Program - Sibali.id')

@section('content')
    <!-- Hero Section -->
    <section class="relative bg-gradient-to-br from-sibali-blue to-sibali-maroon text-white py-16">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-6">
                Program Pembelajaran Bahasa Inggris
            </h1>
            <p class="text-xl mb-8 text-gray-100 max-w-3xl mx-auto">
                Pilih program yang sesuai dengan kebutuhan dan tingkat pendidikan Anda. Kami menyediakan berbagai program
                dengan metode CEFR yang terbukti efektif.
            </p>
        </div>
    </section>

    <!-- Programs Section -->
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            @if ($programs->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    @foreach ($programs as $program)
                        <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <span class="badge badge-info">{{ $program->education_level }}</span>
                                    <span
                                        class="text-2xl font-bold text-sibali-blue">{{ format_currency($program->price) }}</span>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $program->name }}</h3>
                                <p class="text-gray-600 mb-4">{{ $program->description }}</p>
                                <div class="space-y-2 mb-6">
                                    <div class="flex items-center text-sm text-gray-600">
                                        <svg class="w-5 h-5 mr-2 text-sibali-blue" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        {{ $program->duration }} pertemuan
                                    </div>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <svg class="w-5 h-5 mr-2 text-sibali-blue" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                                            </path>
                                        </svg>
                                        {{ $program->class_type }}
                                    </div>
                                </div>
                                <a href="{{ route('program.detail', $program->slug) }}"
                                    class="block w-full text-center bg-sibali-blue text-white py-2 rounded-lg hover:bg-sibali-maroon transition">
                                    Lihat Detail
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-12">
                    {{ $programs->links() }}
                </div>
            @else
                <div class="text-center py-16">
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Program Belum Tersedia</h3>
                    <p class="text-gray-600 mb-8">Maaf, saat ini belum ada program yang tersedia. Silakan kembali lagi
                        nanti.</p>
                    <a href="{{ route('home') }}"
                        class="bg-sibali-blue text-white px-8 py-3 rounded-lg font-semibold hover:bg-sibali-maroon transition">
                        Kembali ke Beranda
                    </a>
                </div>
            @endif
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16 bg-gradient-to-r from-sibali-blue to-sibali-maroon text-white">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-3xl font-bold mb-4">Butuh Bantuan Memilih Program?</h2>
            <p class="text-xl mb-8 text-gray-100">
                Konsultasikan kebutuhan Anda dengan tim kami untuk mendapatkan rekomendasi program yang tepat
            </p>
            <div class="flex flex-wrap justify-center gap-4">
                <a href="{{ route('contact') }}"
                    class="bg-white text-sibali-blue px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">
                    Konsultasi Gratis
                </a>
                <a href="{{ route('register') }}"
                    class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-sibali-blue transition">
                    Daftar Sekarang
                </a>
            </div>
        </div>
    </section>
@endsection
