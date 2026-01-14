<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Product Templates
    |--------------------------------------------------------------------------
    |
    | Define the templated products that can be managed via database.
    | These templates can be added, edited, reduced, or deleted through UI/UX.
    | The structure includes all necessary fields for product management.
    |
    */

    'templates' => [
        [
            'index_code' => 4,
            'produk_kode' => 'P1-ENG-SD-8',
            'tingkat_pendidikan' => 'SD/Sederajat',
            'layanan' => 'Privat',
            'program' => 'Bahasa Inggris',
            'kelas' => 'Rumah siswa',
            'jumlah_pertemuan' => 8,
            'hpp' => 280000,
            'harga_kelas' => 550000,
            'satuan' => '1 Bulan',
            'min_tingkat_pendidikan' => 'Kelas 3 SD',
            'maks_tingkat_pendidikan' => 'Kelas 6 SD',
            'desc' => 'Belajar sesuai level CEFR, fokus pada grammar, kosakata, komunikasi, dan keterampilan 4 skills (listening, speaking, reading, writing).',
            'link_visual' => '',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Education Levels
    |--------------------------------------------------------------------------
    */

    'education_levels' => [
        'SD/Sederajat',
        'SMP/Sederajat',
        'SMA/Sederajat',
        'Mahasiswa',
        'Umum',
    ],

    /*
    |--------------------------------------------------------------------------
    | Service Types
    |--------------------------------------------------------------------------
    */

    'service_types' => [
        'Privat',
        'Regular',
        'Rumah Belajar',
        'Special Program',
    ],

    /*
    |--------------------------------------------------------------------------
    | Programs
    |--------------------------------------------------------------------------
    */

    'programs' => [
        'Bahasa Inggris',
        'Preparation for IELTS/TOEFL',
        'Program ECLAIR',
    ],

];
