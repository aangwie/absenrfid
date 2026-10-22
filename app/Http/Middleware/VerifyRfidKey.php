<?php

namespace App\Http\Middleware;

use App\Models\Perangkat;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autentikasi perangkat RFID (ESP32).
 *
 * Urutan pemeriksaan:
 *   1. Kunci induk dari .env (RFID_API_KEY) — untuk uji cepat / master.
 *   2. Kunci per perangkat di tabel tb_perangkat — agar satu reader bisa
 *      dicabut tanpa mengganggu reader lain.
 *
 * Perangkat mengirim kunci lewat header "X-API-Key" atau parameter "api_key".
 */
class VerifyRfidKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $dikirim = (string) ($request->header('X-API-Key')
            ?? $request->input('api_key')
            ?? $request->query('api_key', ''));

        $master = trim((string) config('rfid.api_key'));

        if ($dikirim !== '' && $master !== '' && hash_equals($master, $dikirim)) {
            return $next($request);
        }

        // Kunci per perangkat. Dibungkus try/catch agar aplikasi tetap
        // memberi pesan jelas bila migrasi belum dijalankan.
        try {
            $perangkat = Perangkat::where('api_key', $dikirim)->first();
        } catch (\Throwable $e) {
            $perangkat = null;
        }

        if ($perangkat) {
            if (! $perangkat->aktif) {
                return response()->json([
                    'ok' => false,
                    'kode' => 'PERANGKAT_NONAKTIF',
                    'pesan' => 'Perangkat ini dinonaktifkan oleh admin.',
                ], 403);
            }

            $request->attributes->set('perangkat', $perangkat);

            return $next($request);
        }

        if ($master === '' && $dikirim === '') {
            return response()->json([
                'ok' => false,
                'kode' => 'API_KEY_BELUM_DISETEL',
                'pesan' => 'RFID_API_KEY belum diisi dan belum ada perangkat terdaftar.',
            ], 503);
        }

        return response()->json([
            'ok' => false,
            'kode' => 'UNAUTHORIZED',
            'pesan' => 'API key salah atau tidak dikirim.',
        ], 401);
    }
}
