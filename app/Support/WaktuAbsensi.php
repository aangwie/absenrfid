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
}
