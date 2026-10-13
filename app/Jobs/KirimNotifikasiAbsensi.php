<?php

namespace App\Jobs;

use App\Models\NotifikasiLog;
use App\Models\Pengaturan;
use App\Models\Siswa;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class KirimNotifikasiAbsensi implements ShouldQueue
{
    use Queueable;

    public $tries = 3;

    public function __construct(public int $logId) {}

    public function handle(): void
    {
        $log = NotifikasiLog::find($this->logId);
        if (! $log || $log->status !== 'antre') {
            return;
        }

        // Jaga jeda 10 detik antar notifikasi: bila masih ada antrean yang
        // lebih dulu dibuat dan belum terkirim, tunda lagi 10 detik.
        $masihAdaAntrianLama = NotifikasiLog::where('status', 'antre')
            ->where('id', '<', $log->id)
            ->exists();
        if ($masihAdaAntrianLama) {
            $this->release(10);

            return;
        }

        $cfg = Pengaturan::ambil();
        $url = trim($cfg['wa_url'] ?? '');
        $apiKey = trim($cfg['wa_api_key'] ?? '');
        $sender = trim($cfg['wa_number'] ?? '');
        if ($url === '' || $apiKey === '' || $sender === '') {
            $log->update(['status' => 'gagal', 'respons' => 'Konfigurasi WhatsApp (URL/API Key/Number) belum disimpan.', 'sent_at' => now()]);

            return;
        }

        $tujuan = static::normalisasiNomor($log->nomor_tujuan);
        if ($tujuan === '') {
            $log->update(['status' => 'gagal', 'respons' => 'Nomor HP ortu/wali kosong atau tidak valid.', 'sent_at' => now()]);

            return;
        }

        try {
            $res = Http::withHeaders(['Content-Type' => 'application/x-www-form-urlencoded'])
                ->asForm()
                ->timeout(30)
                ->post($url, [
                    'nomor_pengirim' => $sender,
                    'api_key' => $apiKey,
                    'nomor_penerima' => $tujuan,
                    'pesan' => $log->pesan,
                ]);

            $respons = (string) $res->body();
            if (strlen($respons) > 2000) {
                $respons = substr($respons, 0, 2000) . '... (dipotong)';
            }
            $log->update([
                'nomor_tujuan' => $tujuan,
                'status' => $res->successful() ? 'terkirim' : 'gagal',
                'http_code' => $res->status(),
                'respons' => $respons,
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $log->update(['status' => 'gagal', 'respons' => 'Gagal koneksi: ' . $e->getMessage(), 'sent_at' => now()]);
            throw $e;
        }
    }

    /** 08xx -> 628xx, +62xx -> 62xx; '' bila tidak valid. */
    public static function normalisasiNomor(?string $no): string
    {
        $no = trim((string) $no);
        if ($no === '') {
            return '';
        }
        $digit = preg_replace('/[^0-9]/', '', $no) ?? '';
        if (str_starts_with($digit, '08')) {
            $digit = '62' . substr($digit, 1);
        } elseif (str_starts_with($digit, '8')) {
            $digit = '62' . $digit;
        }
        if (! preg_match('/^62[0-9]{8,13}$/', $digit)) {
            return '';
        }

        return $digit;
    }

    public static function susunPesan(Siswa $siswa, string $tipe, string $jam, string $status, string $tanggal): string
    {
        $institusi = trim(Pengaturan::nilai('nama_institusi'));
        $kepala = $institusi !== '' ? "[Absensi {$institusi}]" : '[Absensi RFID]';
        $kelas = $siswa->kelas->nama ?? '-';
        $aksi = $tipe === 'pulang' ? 'telah PULANG' : 'telah MASUK';
        $waktu = substr($jam, 0, 5);

        return "{$kepala} {$siswa->nama} ({$siswa->nisn}, {$kelas}) {$aksi} pada {$tanggal} pukul {$waktu} ({$status}).";
    }
}
