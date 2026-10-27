<?php

namespace App\Jobs;

use App\Models\NotifikasiLog;
use App\Models\Pengaturan;
use App\Models\Siswa;
use App\Support\AntreanWhatsapp;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class KirimNotifikasiAbsensi implements ShouldQueue
{
    use Queueable;

    /**
     * Tanpa batas: job boleh "menunggu jadwal" dengan release() berkali-kali
     * tanpa dianggap gagal. Status akhir (terkirim/gagal) selalu ditulis
     * sendiri di handle(), jadi tidak ada percobaan ulang yang menduplikasi
     * pesan.
     */
    public $tries = 0;

    public $timeout = 40;

    public function __construct(public int $logId) {}

    public function handle(): void
    {
        $log = NotifikasiLog::find($this->logId);
        if (! $log || $log->status !== 'antre') {
            return;
        }

        // Status yang dinonaktifkan di pengaturan tidak boleh terkirim walau
        // job-nya sudah telanjur masuk antrean.
        if (! AntreanWhatsapp::aktif($log->tipe, $log->keterangan)) {
            $log->update([
                'status' => 'batal',
                'dibatalkan_at' => now(),
                'respons' => 'Dinonaktifkan di pengaturan WhatsApp.',
            ]);

            return;
        }

        DB::table('tb_pengaturan')->updateOrInsert(['kunci' => 'cron_last_run'], ['nilai' => now()->toDateTimeString(), 'updated_at' => now(), 'created_at' => now()]);

        // Belum waktunya dikirim (jadwal atau jeda antar pesan). Kembalikan ke
        // antrean. Saat dipanggil langsung tanpa queue ($this->job kosong,
        // misal aksi Tandai satu siswa) pesan tetap dikirim seketika.
        $tunggu = $this->detikMenunggu($log);
        if ($tunggu > 0 && $this->job) {
            $this->release($tunggu);

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

        // Pesan bisa saja dibatalkan admin saat job masih menunggu.
        $log->refresh();
        if ($log->status !== 'antre') {
            return;
        }

        // Pesan slot lebih dulu agar worker lain tidak mengirim pesan
        // berikutnya pada detik yang sama (mis. setelah cron tertinggal).
        $this->tandaiSlot();

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
            // Tidak dilempar ulang: pengiriman ulang otomatis berisiko
            // mengirim pesan ganda (sinyal spam ke WhatsApp). Catat gagal dan
            // biarkan admin menindaklanjuti dari tab Log/Antrean.
            $log->update(['status' => 'gagal', 'respons' => 'Gagal koneksi: ' . $e->getMessage(), 'sent_at' => now()]);
        }
    }

    /** Sisa detik sebelum pesan ini boleh dikirim (0 = boleh sekarang). */
    private function detikMenunggu(NotifikasiLog $log): int
    {
        $tunggu = 0;

        if ($log->jadwal_kirim) {
            $tunggu = $log->jadwal_kirim->getTimestamp() - now()->getTimestamp();
        }

        $terakhir = Pengaturan::nilai('wa_terakhir_kirim');
        if ($terakhir !== '') {
            try {
                $tunggu = max($tunggu, Carbon::parse($terakhir)->getTimestamp() + AntreanWhatsapp::jedaDetik() - now()->getTimestamp());
            } catch (\Throwable $e) {
                // Nilai pengaturan rusak: abaikan pembatas, tetap kirim.
            }
        }

        return max(0, (int) ceil($tunggu));
    }

    private function tandaiSlot(): void
    {
        DB::table('tb_pengaturan')->updateOrInsert(
            ['kunci' => 'wa_terakhir_kirim'],
            ['nilai' => now()->toDateTimeString(), 'updated_at' => now(), 'created_at' => now()]
        );
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

    /** Pesan untuk penandaan manual dari /laporan (Belum Absen). */
    public static function susunPesanTandai(Siswa $siswa, string $status, string $jam, string $tanggal): string
    {
        $institusi = trim(Pengaturan::nilai('nama_institusi'));
        $kepala = $institusi !== '' ? "[Absensi {$institusi}]" : '[Absensi RFID]';
        $kelas = $siswa->kelas->nama ?? '-';
        $waktu = $jam !== '' ? ' pukul ' . substr($jam, 0, 5) : '';

        return "{$kepala} {$siswa->nama} ({$siswa->nisn}, {$kelas}) ditandai {$status} pada {$tanggal}{$waktu}.";
    }
}
