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
    | Asisten AI (OpenRouter sebagai penyedia utama, Google Gemini sebagai cadangan).
    | Kunci diambil dari .env, jangan ditulis di kode.
    */
    'openrouter' => [
        'key' => env('OPENROUTER_API_KEY'),
        'model' => env('OPENROUTER_MODEL', 'openrouter/free'),
        'fallback_models' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('OPENROUTER_FALLBACK_MODELS', 'openrouter/free'))
        ))),
        'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
        'max_tokens' => (int) env('OPENROUTER_MAX_TOKENS', 8192),
        'timeout' => (int) env('OPENROUTER_TIMEOUT', 120),
    ],

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.5-flash-lite'),
        'timeout' => 60,
    ],

    // Perkiraan umum (dapat diubah peternak)
    'konsumsi_default' => 0.11,       // kg/ekor/hari
    'harga_telur_default' => 27969,   // Rp/kg acuan harga pasar telur ayam layer
    'harga_usang_hari' => 7,

    'disclaimer_ai' => 'Ini perkiraan dari pola data, bukan diagnosis. Periksa ayam langsung atau hubungi dokter hewan.',
];
