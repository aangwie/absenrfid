<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Jadwal absensi khusus pada rentang tanggal tertentu.
 * Berlaku inklusif dari tanggal_mulai sampai tanggal_selesai dan
 * mengalahkan jadwal rutin harian.
 */
class JadwalAbsensiKhusus extends Model
{
    protected $table = 'tb_jadwal_absensi_khusus';

    protected $fillable = [
        'tanggal_mulai',
        'tanggal_selesai',
        'masuk_mulai',
        'masuk_selesai',
        'terlambat_mulai',
        'terlambat_selesai',
        'pulang_mulai',
        'pulang_selesai',
        'keterangan',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    /**
     * Jadwal khusus yang berlaku pada tanggal tertentu.
     * Bila ada lebih dari satu (data lama), ambil yang paling baru.
     */
    public static function untukTanggal(string $tanggal): ?self
    {
        try {
            return static::query()
                ->whereDate('tanggal_mulai', '<=', $tanggal)
                ->whereDate('tanggal_selesai', '>=', $tanggal)
                ->orderByDesc('tanggal_mulai')
                ->orderByDesc('id')
                ->first();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
