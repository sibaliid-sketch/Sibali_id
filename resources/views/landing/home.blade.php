@extends('layouts.landing')

@section('title', 'Beranda - Sibali.id')

@section('content')
    <!-- Hero Section -->
    <section class="relative bg-gradient-to-br from-sibali-blue to-sibali-maroon text-white py-20">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h1 class="text-4xl md:text-5xl font-bold mb-6">
                        Belajar Bahasa Inggris Bersama Sibali.id
                    </h1>
                    <p class="text-xl mb-8 text-gray-100">
                        Platform pembelajaran bahasa Inggris terpercaya dengan metode CEFR dan pengajar berpengalaman
                    </p>
                    <div class="flex flex-wrap gap-4">
                        <a href="{{ route('register') }}"
                            class="bg-white text-sibali-blue px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">
                            Daftar Sekarang
                        </a>
                        <a href="{{ route('programs') }}"
                            class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-sibali-blue transition">
                            Lihat Program
                        </a>
                    </div>
                </div>
                <div class="hidden lg:block">
                    <img src="{{ asset('images/hero-illustration.svg') }}" alt="Learning Illustration" class="w-full">
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <div class="text-center">
                    <div class="text-4xl font-bold text-sibali-blue mb-2">{{ number_format($stats['total_students']) }}+
                    </div>
                    <div class="text-gray-600">Siswa Aktif</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-sibali-blue mb-2">{{ number_format($stats['total_classes']) }}+
                    </div>
                    <div class="text-gray-600">Kelas Tersedia</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-sibali-blue mb-2">{{ number_format($stats['total_teachers']) }}+
                    </div>
                    <div class="text-gray-600">Pengajar Profesional</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-sibali-blue mb-2">{{ $stats['success_rate'] }}%</div>
                    <div class="text-gray-600">Tingkat Keberhasilan</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Programs -->
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-sibali-maroon mb-4">Program Unggulan</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">
                    Pilih program yang sesuai dengan kebutuhan dan tingkat pendidikan Anda
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach ($featured_programs as $program)
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

            <div class="text-center mt-12">
                <a href="{{ route('programs') }}"
                    class="inline-block bg-sibali-maroon text-white px-8 py-3 rounded-lg font-semibold hover:bg-sibali-red transition">
                    Lihat Semua Program
                </a>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-sibali-maroon mb-4">Testimoni Siswa</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">
                    Apa kata mereka yang telah belajar bersama kami
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach ($testimonials as $testimonial)
                    <div class="bg-gray-50 rounded-lg p-6 shadow hover:shadow-lg transition">
                        <div class="flex items-center mb-4">
                            @for ($i = 1; $i <= 5; $i++)
                                <svg class="w-5 h-5 {{ $i <= $testimonial->rating ? 'text-yellow-400' : 'text-gray-300' }}"
                                    fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z">
                                    </path>
                                </svg>
                            @endfor
                        </div>
                        <p class="text-gray-700 mb-4 italic">"{{ $testimonial->content }}"</p>
                        <div class="flex items-center">
                            <div
                                class="w-10 h-10 bg-sibali-sky rounded-full flex items-center justify-center text-sibali-blue font-bold mr-3">
                                {{ substr($testimonial->name, 0, 1) }}
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900">{{ $testimonial->name }}</div>
                                <div class="text-sm text-gray-500">{{ $testimonial->program }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Latest Blog Posts -->
    @if ($latest_posts->isNotEmpty())
        <section class="py-16 bg-gray-50">
            <div class="container mx-auto px-4">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-sibali-maroon mb-4">Artikel Terbaru</h2>
                    <p class="text-gray-600 max-w-2xl mx-auto">
                        Tips dan informasi seputar pembelajaran bahasa Inggris
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    @foreach ($latest_posts as $post)
                        <article class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition">
                            @if ($post->featured_image)
                                <img src="{{ asset('storage/' . $post->featured_image) }}" alt="{{ $post->title }}"
                                    class="w-full h-48 object-cover">
                            @endif
                            <div class="p-6">
                                <div class="text-sm text-gray-500 mb-2">
                                    {{ \Carbon\Carbon::parse($post->publish_at)->format('d M Y') }}
                                </div>
                                <h3 class="text-xl font-bold text-gray-900 mb-3">{{ $post->title }}</h3>
                                <p class="text-gray-600 mb-4">{{ Str::limit(strip_tags($post->body), 120) }}</p>
                                <a href="{{ route('blog.show', $post->slug) }}"
                                    class="text-sibali-blue hover:text-sibali-maroon font-semibold">
                                    Baca Selengkapnya →
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- CTA Section -->
    <section class="py-16 bg-gradient-to-r from-sibali-blue to-sibali-maroon text-white">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-3xl font-bold mb-4">Siap Memulai Perjalanan Belajar Anda?</h2>
            <p class="text-xl mb-8 text-gray-100">
                Daftar sekarang dan dapatkan konsultasi gratis untuk menentukan program yang tepat
            </p>
            <div class="flex flex-wrap justify-center gap-4">
                <a href="{{ route('register') }}"
                    class="bg-white text-sibali-blue px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">
                    Daftar Gratis
                </a>
                <a href="{{ route('contact') }}"
                    class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-sibali-blue transition">
                    Hubungi Kami
                </a>
            </div>
        </div>
    </section>
@endsection
