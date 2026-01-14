@extends('layouts.landing')

@section('title', $program->name . ' - Sibali.id')

@section('content')
    <!-- Hero Section -->
    <section class="relative bg-gradient-to-br from-sibali-blue to-sibali-maroon text-white py-16">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto text-center">
                <h1 class="text-4xl md:text-5xl font-bold mb-6">
                    {{ $program->name }}
                </h1>
                <p class="text-xl mb-8 text-gray-100">
                    {{ $program->description }}
                </p>
                <div class="flex flex-wrap justify-center gap-4">
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg px-6 py-3">
                        <div class="text-2xl font-bold">{{ format_currency($program->price) }}</div>
                        <div class="text-sm text-gray-200">Harga Program</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg px-6 py-3">
                        <div class="text-2xl font-bold">{{ $program->duration }}</div>
                        <div class="text-sm text-gray-200">Pertemuan</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg px-6 py-3">
                        <div class="text-2xl font-bold">{{ $program->education_level }}</div>
                        <div class="text-sm text-gray-200">Tingkat</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Program Details -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                    <!-- Program Info -->
                    <div>
                        <h2 class="text-3xl font-bold text-sibali-maroon mb-6">Tentang Program</h2>
                        <div class="prose prose-lg max-w-none text-gray-700 mb-8">
                            {!! $program->long_description ?? $program->description !!}
                        </div>

                        <div class="space-y-4 mb-8">
                            <div class="flex items-center">
                                <svg class="w-6 h-6 mr-3 text-sibali-blue" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span><strong>Durasi:</strong> {{ $program->duration }} pertemuan</span>
                            </div>
                            <div class="flex items-center">
                                <svg class="w-6 h-6 mr-3 text-sibali-blue" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                                    </path>
                                </svg>
                                <span><strong>Tipe Kelas:</strong> {{ $program->class_type }}</span>
                            </div>
                            <div class="flex items-center">
                                <svg class="w-6 h-6 mr-3 text-sibali-blue" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
                                    </path>
                                </svg>
                                <span><strong>Tingkat Pendidikan:</strong> {{ $program->education_level }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Registration Form -->
                    <div class="bg-gray-50 rounded-lg p-8">
                        <h3 class="text-2xl font-bold text-sibali-maroon mb-6">Daftar Program Ini</h3>
                        <form action="{{ route('register') }}" method="GET" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap</label>
                                <input type="text" name="name"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sibali-blue focus:border-transparent"
                                    required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                <input type="email" name="email"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sibali-blue focus:border-transparent"
                                    required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nomor Telepon</label>
                                <input type="tel" name="phone"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sibali-blue focus:border-transparent"
                                    required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Pesan (Opsional)</label>
                                <textarea name="message" rows="3"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sibali-blue focus:border-transparent"
                                    placeholder="Beritahu kami kebutuhan khusus Anda..."></textarea>
                            </div>
                            <button type="submit"
                                class="w-full bg-sibali-blue text-white py-3 rounded-lg font-semibold hover:bg-sibali-maroon transition">
                                Daftar Sekarang
                            </button>
                        </form>
                        <p class="text-sm text-gray-600 mt-4">
                            Dengan mendaftar, Anda akan dihubungi oleh tim kami untuk proses selanjutnya.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Related Programs -->
    @if (isset($related_programs) && $related_programs->count() > 0)
        <section class="py-16 bg-gray-50">
            <div class="container mx-auto px-4">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-sibali-maroon mb-4">Program Lainnya</h2>
                    <p class="text-gray-600 max-w-2xl mx-auto">
                        Lihat program lainnya yang mungkin sesuai dengan kebutuhan Anda
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">
                    @foreach ($related_programs as $related)
                        <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <span class="badge badge-info">{{ $related->education_level }}</span>
                                    <span
                                        class="text-xl font-bold text-sibali-blue">{{ format_currency($related->price) }}</span>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $related->name }}</h3>
                                <p class="text-gray-600 mb-4">{{ Str::limit($related->description, 100) }}</p>
                                <a href="{{ route('program.detail', $related->slug) }}"
                                    class="block w-full text-center bg-sibali-blue text-white py-2 rounded-lg hover:bg-sibali-maroon transition">
                                    Lihat Detail
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- CTA Section -->
    <section class="py-16 bg-gradient-to-r from-sibali-blue to-sibali-maroon text-white">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-3xl font-bold mb-4">Masih Ada Pertanyaan?</h2>
            <p class="text-xl mb-8 text-gray-100">
                Tim kami siap membantu Anda memilih program yang tepat
            </p>
            <div class="flex flex-wrap justify-center gap-4">
                <a href="{{ route('contact') }}"
                    class="bg-white text-sibali-blue px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">
                    Hubungi Kami
                </a>
                <a href="{{ route('programs') }}"
                    class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-sibali-blue transition">
                    Lihat Semua Program
                </a>
            </div>
        </div>
    </section>
@endsection
