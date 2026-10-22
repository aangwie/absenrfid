<?php

use App\Http\Controllers\Api\TapController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Perangkat RFID (ESP32 + MFRC522)
|--------------------------------------------------------------------------
|
| Semua rute di sini otomatis berprefix /api dan memakai grup middleware
| "api" (tanpa sesi & tanpa CSRF). Autentikasi memakai header X-API-Key.
|
*/

Route::middleware(['rfid.key'])->group(function () {
    Route::post('/tap', [TapController::class, 'tap'])->middleware('throttle:rfid');
    Route::get('/ping', [TapController::class, 'ping']);
});
