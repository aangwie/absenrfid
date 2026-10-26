<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Jadwal absensi rutin per hari.
 * hari: 1 = Senin ... 7 = Minggu (ISO-8601).
 */
class JadwalAbsensi extends Model
{
    protected $table = 'tb_jadwal_absensi';

    protected $fillable = [
        'hari',
        'masuk_mulai',
        'masuk_selesai',
        'terlambat_mulai',
        'terlambat_selesai',
        'pulang_mulai',
        'pulang_selesai',
    ];

    protected $casts = ['hari' => 'integer'];

    /**
     * Jadwal untuk satu hari. Null bila hari tersebut belum diatur,
     * sehingga pemanggil bisa memakai pengaturan lama sebagai cadangan.
     */
    public static function untukHari(int $hari): ?self
    {
        try {
            return static::where('hari', $hari)->first();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
