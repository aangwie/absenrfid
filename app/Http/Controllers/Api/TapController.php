<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengaturan;
use App\Models\Siswa;
use App\Models\TapLog;
use App\Support\TapAbsensi;
use App\Support\WaktuAbsensi;
use Illuminate\Http\Request;

/**
 * API untuk perangkat RFID (ESP32 + MFRC522) dan integrator lain.
 *
 * Semua respons berupa JSON. Hasil bisnis (MASUK/TERLAMBAT/PULANG/GAGAL)
 * selalu dikembalikan dengan HTTP 200 agar firmware cukup membaca field
 * "ok" dan "kode". Hanya kesalahan autentikasi/throttling yang memakai
 * status HTTP lain (401/403/429/503).
 */
class TapController extends Controller
{
    public function tap(Request $request, TapAbsensi $tap)
    {
        $tag = trim((string) $request->input(
            'tag',
            $request->input('rfid_tag', $request->input('uid', $request->input('id', '')))
        ));

        $h = $tap->proses($tag);
        $perangkat = $request->attributes->get('perangkat');

        $this->catatLog($request, $perangkat, $tag, $h);

        return response()->json([
            'ok' => $h['ok'],
            'kode' => $h['kode'],
            'status' => $h['status'],
            'pesan' => $h['pesan'],
            'waktu' => now()->toIso8601String(),
            'siswa' => $h['siswa'] ? [
                'nisn' => $h['siswa']->nisn,
                'nama' => $h['siswa']->nama,
                'kelas' => $h['siswa']->kelas->nama ?? null,
                'rfid_tag' => $h['siswa']->rfid_tag,
            ] : null,
            'absensi' => $h['absensi'] ? [
                'tanggal' => $h['absensi']->tanggal?->toDateString(),
                'jam_masuk' => $h['absensi']->jam_masuk,
                'jam_pulang' => $h['absensi']->jam_pulang,
                'status_masuk' => $h['absensi']->status_masuk,
                'status_pulang' => $h['absensi']->status_pulang,
            ] : null,
        ]);
    }

    /**
     * Cek koneksi + API key dari perangkat, sekaligus melihat jam server
     * dan rentang absensi yang sedang berlaku.
     */
    public function ping(Request $request)
    {
        $perangkat = $request->attributes->get('perangkat');

        return response()->json([
            'ok' => true,
            'kode' => 'PONG',
            'pesan' => 'API RFID aktif.',
            'sekolah' => Pengaturan::nilai('nama_sekolah', config('app.name')),
            'perangkat' => $perangkat ? $perangkat->nama : 'master',
            'waktu' => now()->toIso8601String(),
            'zona_waktu' => config('app.timezone'),
            'kategori_sekarang' => WaktuAbsensi::kategori(now()->format('H:i:s')),
            'rentang' => WaktuAbsensi::rentang(),
            'total_siswa' => Siswa::count(),
        ]);
    }

    /**
     * Simpan jejak tap. Kegagalan logging tidak boleh menggagalkan absensi.
     */
    private function catatLog(Request $request, $perangkat, string $tag, array $hasil): void
    {
        try {
            TapLog::create([
                'perangkat_id' => $perangkat?->id,
                'tag' => mb_substr($tag, 0, 100),
                'kode' => $hasil['kode'],
                'pesan' => mb_substr($hasil['pesan'], 0, 255),
                'ip' => $request->ip(),
            ]);

            if ($perangkat) {
                $perangkat->forceFill(['terakhir_tap_at' => now()])->save();
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
