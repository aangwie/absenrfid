<?php

namespace App\Support;

use App\Jobs\KirimNotifikasiAbsensi;
use App\Models\NotifikasiLog;
use App\Models\Pengaturan;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Penjadwal antrean notifikasi WhatsApp.
 *
 * Hanya status yang dicentang di menu WhatsApp yang dikirim:
 * tepat waktu, terlambat, dan pulang. Semua pesan terpilih masuk satu antrean
 * dan dikirim bertahap sesuai pengaturan durasi/jumlah (mis. 10 menit /
 * 10 pesan = 1 pesan tiap 60 detik). Status yang tidak dicentang tidak dibuat
 * notifikasinya. Notifikasi dari aksi "Tandai" manual di /laporan selalu
 * dikirim karena merupakan tindakan admin.
 *
 * Semua pesan tercatat di tb_notifikasi_log beserta jadwal kirimnya sehingga
 * admin bisa melihat dan membatalkan antrean yang belum terkirim.
 */
class AntreanWhatsapp
{
    /** Durasi bawaan: 10 menit. */
    public const DURASI_DEFAULT = 10;

    /** Jumlah pesan bawaan per durasi. */
    public const JUMLAH_DEFAULT = 10;

    /** Status yang bisa dipilih untuk dikirim: kunci => label. */
    public const KATEGORI = [
        'tepat_waktu' => 'Tepat Waktu',
        'terlambat' => 'Terlambat',
        'pulang' => 'Pulang',
    ];

    public static function durasiMenit(): int
    {
        return max(1, (int) Pengaturan::nilai('wa_batch_menit', (string) self::DURASI_DEFAULT));
    }

    public static function jumlahPesan(): int
    {
        return max(1, (int) Pengaturan::nilai('wa_batch_jumlah', (string) self::JUMLAH_DEFAULT));
    }

    /** Jarak antar pesan dalam antrean, dalam detik. */
    public static function jedaDetik(): int
    {
        return max(1, (int) round(self::durasiMenit() * 60 / self::jumlahPesan()));
    }

    /** Kategori sebuah notifikasi: tepat_waktu, terlambat, pulang, atau lain. */
    public static function kategori(string $tipe, ?string $keterangan): string
    {
        if ($tipe === 'pulang') {
            return 'pulang';
        }

        $keterangan = trim((string) $keterangan);
        if ($tipe === 'masuk') {
            if ($keterangan === 'Tepat Waktu') {
                return 'tepat_waktu';
            }
            if ($keterangan === 'Terlambat') {
                return 'terlambat';
            }
        }

        return 'lain';
    }

    public static function kunciKategori(string $kategori): string
    {
        return 'wa_kirim_' . $kategori;
    }

    /**
     * Apakah notifikasi dengan status ini boleh dikirim. Kategori "lain"
     * (tandai manual Izin/Sakit/Tanpa Keterangan) selalu boleh.
     */
    public static function aktif(string $tipe, ?string $keterangan): bool
    {
        $kategori = self::kategori($tipe, $keterangan);
        if ($kategori === 'lain') {
            return true;
        }

        return Pengaturan::nilai(self::kunciKategori($kategori), '1') !== '0';
    }

    /** Label status yang sedang aktif, untuk ditampilkan di UI. */
    public static function aktifLabel(): array
    {
        $label = [];
        foreach (self::KATEGORI as $kategori => $teks) {
            if (Pengaturan::nilai(self::kunciKategori($kategori), '1') !== '0') {
                $label[] = $teks;
            }
        }

        return $label;
    }

    /** Query log untuk satu kategori status. */
    public static function queryKategori(string $kategori): Builder
    {
        $query = NotifikasiLog::query();

        return match ($kategori) {
            'tepat_waktu' => $query->where('tipe', 'masuk')->where('keterangan', 'Tepat Waktu'),
            'terlambat' => $query->where('tipe', 'masuk')->where('keterangan', 'Terlambat'),
            'pulang' => $query->where('tipe', 'pulang'),
            default => $query->where('tipe', 'masuk')->whereNotIn('keterangan', ['Tepat Waktu', 'Terlambat']),
        };
    }

    /**
     * Tentukan waktu kirim untuk pesan baru: melanjutkan jadwal antrean
     * terakhir, atau sekarang bila antrean sedang kosong.
     */
    public static function jadwalkan(): Carbon
    {
        $terakhir = NotifikasiLog::query()
            ->antre()
            ->whereNotNull('jadwal_kirim')
            ->max('jadwal_kirim');

        $sekarang = now();
        if (! $terakhir) {
            return $sekarang;
        }

        $berikut = Carbon::parse($terakhir)->addSeconds(self::jedaDetik());

        return $berikut->greaterThan($sekarang) ? $berikut : $sekarang;
    }

    /**
     * Catat notifikasi lalu antrekan pengirimannya. Mengembalikan null bila
     * statusnya tidak dicentang di pengaturan.
     *
     * @param  bool  $langsung  Kirim sinkron saat itu juga (dipakai aksi Tandai
     *                          satu siswa di /laporan agar status bisa tampil).
     */
    public static function antre(
        Siswa $siswa,
        string $tanggal,
        string $tipe,
        ?string $keterangan,
        string $pesan,
        bool $langsung = false
    ): ?NotifikasiLog {
        if (! self::aktif($tipe, $keterangan)) {
            return null;
        }

        $tujuan = KirimNotifikasiAbsensi::normalisasiNomor($siswa->no_hp_ortu);
        $jadwal = $langsung ? now() : self::jadwalkan();

        $log = NotifikasiLog::create([
            'nisn' => $siswa->nisn,
            'tanggal' => $tanggal,
            'tipe' => $tipe,
            'keterangan' => $keterangan,
            'nomor_tujuan' => $tujuan !== '' ? $tujuan : trim((string) $siswa->no_hp_ortu),
            'pesan' => $pesan,
            'status' => 'antre',
            'jadwal_kirim' => $jadwal,
        ]);

        if ($tujuan === '') {
            $log->update([
                'status' => 'gagal',
                'respons' => 'Nomor HP ortu/wali kosong atau tidak valid.',
                'sent_at' => now(),
            ]);

            return $log;
        }

        if ($langsung) {
            (new KirimNotifikasiAbsensi($log->id))->handle();
        } else {
            KirimNotifikasiAbsensi::dispatch($log->id)->delay($jadwal);
        }

        return $log->refresh();
    }

    /** Batalkan seluruh antrean pada satu kategori; mengembalikan jumlahnya. */
    public static function batalkanKategori(string $kategori): int
    {
        return self::queryKategori($kategori)->antre()->update([
            'status' => 'batal',
            'dibatalkan_at' => now(),
            'respons' => 'Dinonaktifkan di pengaturan WhatsApp.',
        ]);
    }

    /** Perkiraan kapan seluruh antrean selesai dikirim (null bila kosong). */
    public static function perkiraanSelesai(): ?Carbon
    {
        $terakhir = NotifikasiLog::query()->antre()->max('jadwal_kirim');

        return $terakhir ? Carbon::parse($terakhir) : null;
    }
}
