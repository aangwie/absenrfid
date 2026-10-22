<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Key Perangkat RFID
    |--------------------------------------------------------------------------
    |
    | Kunci yang harus dikirim perangkat (ESP32 + MFRC522) pada setiap request
    | ke /api/tap. Kirim lewat header "X-API-Key" (disarankan) atau parameter
    | "api_key". Kosongkan hanya untuk pengembangan lokal; di hosting wajib diisi.
    |
    */

    'api_key' => env('RFID_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Batas Request per Menit
    |--------------------------------------------------------------------------
    |
    | Dihitung per alamat IP perangkat. Tap normal hanya beberapa detik sekali,
    | jadi 120/menit sudah sangat longgar. Naikkan bila ada banyak reader di
    | balik satu IP (NAT).
    |
    */

    'throttle_per_minute' => (int) env('RFID_THROTTLE_PER_MINUTE', 120),

];
