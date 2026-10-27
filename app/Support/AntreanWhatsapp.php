<?php

namespace App\Support;

use App\Jobs\KirimNotifikasiAbsensi;
use App\Models\NotifikasiLog;
use App\Models\Pengaturan;
use App\Models\Siswa;
use Illuminate\Support\Carbon;

/**
 * Penjadwal antrean notifikasi WhatsApp.
 *
 * Notifikasi "MASUK tepat waktu" ditampung lebih dulu, lalu dikirim bertahap
 * dengan kecepatan yang diatur admin (mis. 10 menit / 10 pesan = 1 pesan tiap
 * 60 detik). Notifikasi lain (terlambat, izin/sakit, pulang, tandai manual)
 * tetap segera dikirim dengan jeda pengaman agar tidak menumpuk.
 *
 * Semua pesan tetap tercatat di tb_notifikasi_log beserta jadwal kirimnya,
 * sehingga admin bisa melihat dan membatalkan antrean yang belum terkirim.
 */
class AntreanWhatsapp
{
    /** Durasi bawaan: 10 menit. */
    public const DURASI_DEFAULT = 10;

    /** Jumlah pesan bawaan per durasi. */
    public const JUMLAH_DEFAULT = 10;

    /** Jeda pengaman antar pesan yang tidak ditampung (detik). */
    public const JEDA_LAIN = 10;

    public static function durasiMenit(): int
    {
        return max(1, (int) Pengaturan::nilai('wa_batch_menit', (string) self::DURASI_DEFAULT));
    }

    public static function jumlahPesan(): int
    {
        return max(1, (int) Pengaturan::nilai('wa_batch_jumlah', (string) self::JUMLAH_DEFAULT));
    }

    /** Jarak antar pesan yang ditampung, dalam detik. */
    public static function jedaDetik(): int
    {
        return max(1, (int) round(self::durasiMenit() * 60 / self::jumlahPesan()));
    }

    /**
     * Hanya absensi MASUK berstatus Tepat Waktu yang ditampung. Status lain
     * (Terlambat, Izin, Sakit, Tanpa Keterangan, Selesai) dikirim segera.
     */
    public static function perluDitampung(string $tipe, ?string $keterangan): bool
    {
        return $tipe === 'masuk' && trim((string) $keterangan) === 'Tepat Waktu';
    }

    /**
     * Tentukan waktu kirim untuk pesan baru: melanjutkan jadwal terakhir pada
     * jalur yang sama, atau sekarang bila jalur sedang kosong.
     */
    public static function jadwalkan(string $tipe, ?string $keterangan): Carbon
    {
        $ditampung = self::perluDitampung($tipe, $keterangan);

        $query = NotifikasiLog::query()
            ->antre()
            ->whereNotNull('jadwal_kirim');

        if ($ditampung) {
            $query->where('tipe', 'masuk')
                ->where('keterangan', 'Tepat Waktu');
        } else {
            // Semua yang bukan "masuk tepat waktu" memakai jalur cepat.
            $query->where(function ($q) {
                $q->where('tipe', '!=', 'masuk')
                    ->orWhereNull('keterangan')
                    ->orWhere('keterangan', '!=', 'Tepat Waktu');
            });
        }

        $terakhir = $query->max('jadwal_kirim');
        $sekarang = now();

        if (! $terakhir) {
            return $sekarang;
        }

        $jeda = $ditampung ? self::jedaDetik() : self::JEDA_LAIN;
        $berikut = Carbon::parse($terakhir)->addSeconds($jeda);

        return $berikut->greaterThan($sekarang) ? $berikut : $sekarang;
    }

    /**
     * Catat notifikasi lalu antrekan pengirimannya.
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
    ): NotifikasiLog {
        $tujuan = KirimNotifikasiAbsensi::normalisasiNomor($siswa->no_hp_ortu);
        $jadwal = $langsung ? now() : self::jadwalkan($tipe, $keterangan);

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

    /** Perkiraan kapan seluruh antrean selesai dikirim (null bila kosong). */
    public static function perkiraanSelesai(): ?Carbon
    {
        $terakhir = NotifikasiLog::query()->antre()->max('jadwal_kirim');

        return $terakhir ? Carbon::parse($terakhir) : null;
    }
}
