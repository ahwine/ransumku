<?php

return [
    /*
    | Ambang batas peringatan dini (berbasis aturan/statistik, bukan LLM).
    */
    'anomali' => [
        'hari_dasar' => 7,           // jumlah hari pembanding sebelum 3 hari terakhir
        'pakan_naik_persen' => 10,   // pakan naik >= X% selama 3 hari berturut-turut
        'telur_turun_persen' => 5,   // sementara telur turun >= Y%
        'kematian_min' => 3,         // ekor/hari yang dianggap lonjakan
        'zscore' => 2.0,             // ambang z-score biaya pakan per kg telur
    ],

    /*
    | Asisten AI (Gemini). Kunci diambil dari .env, jangan ditulis di kode.
    */
    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        'timeout' => 20,
    ],

    // Perkiraan umum (dapat diubah peternak)
    'konsumsi_default' => 0.11,       // kg/ekor/hari
    'harga_usang_hari' => 7,

    'disclaimer_ai' => 'Ini perkiraan dari pola data, bukan diagnosis. Periksa ayam langsung atau hubungi dokter hewan.',
];
