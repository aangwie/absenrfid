<?php

namespace App\Support;

use App\Models\Pengaturan;

class WaktuAbsensi
{
    public static function rentang(): array
    {
        return [
            'masuk_mulai' => Pengaturan::nilai('masuk_mulai', '06:30'),
            'masuk_selesai' => Pengaturan::nilai('masuk_selesai', '07:30'),
            'terlambat_mulai' => Pengaturan::nilai('terlambat_mulai', '07:31'),
            'terlambat_selesai' => Pengaturan::nilai('terlambat_selesai', '13:29'),
            'pulang_mulai' => Pengaturan::nilai('pulang_mulai', '13:30'),
            'pulang_selesai' => Pengaturan::nilai('pulang_selesai', '14:30'),
        ];
    }

    protected static function diDalam(string $jam, string $mulai, string $selesai): bool
    {
        $j = substr($jam, 0, 5);
        return $j >= $mulai && $j <= $selesai;
    }

    // Kategori jam tap: Masuk / Terlambat / Pulang / Tanpa Keterangan
    public static function kategori(string $jam): string
    {
        $r = static::rentang();
        if (static::diDalam($jam, $r['masuk_mulai'], $r['masuk_selesai'])) {
            return 'Masuk';
        }
        if (static::diDalam($jam, $r['terlambat_mulai'], $r['terlambat_selesai'])) {
            return 'Terlambat';
        }
        if (static::diDalam($jam, $r['pulang_mulai'], $r['pulang_selesai'])) {
            return 'Pulang';
        }
        return 'Tanpa Keterangan';
    }

    public static function boleh(string $jam): bool
    {
        return static::kategori($jam) !== 'Tanpa Keterangan';
    }

    // Tutup = lewat batas pulang_selesai hari ini.
    public static function tutup(?string $jam = null): bool
    {
        $jam ??= now()->format('H:i:s');
        $r = static::rentang();

        return substr($jam, 0, 5) > $r['pulang_selesai'];
    }

    // Siswa tanpa baris absensi di $tanggal -> otomatis Tanpa Keterangan.
    // Hari ini hanya jalan setelah jam tutup agar tap susulan tetap bisa masuk.
    // ponytail: loop insert per-NISN, pindah ke insert batch bila >10rb siswa.
    public static function tandaiTanpaKeterangan(?string $tanggal = null): int
    {
        try {
            $tanggal ??= now()->toDateString();
            if ($tanggal >= now()->toDateString() && ! static::tutup()) {
                return 0;
            }
            $ada = \App\Models\Absensi::whereDate('tanggal', $tanggal)->pluck('nisn')->all();
            $kurang = \App\Models\Siswa::when($ada, fn ($q) => $q->whereNotIn('nisn', $ada))->pluck('nisn');
            $n = 0;
            foreach ($kurang as $nisn) {
                \App\Models\Absensi::firstOrCreate(['nisn' => $nisn, 'tanggal' => $tanggal], [
                    'jam_masuk' => null, 'jam_pulang' => null,
                    'status_masuk' => 'Tanpa Keterangan', 'status_pulang' => 'Belum',
                ]);
                $n++;
            }

            return $n;
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
