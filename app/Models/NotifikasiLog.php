<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotifikasiLog extends Model
{
    protected $table = 'tb_notifikasi_log';

    protected $fillable = [
        'nisn', 'tanggal', 'tipe', 'keterangan', 'nomor_tujuan', 'pesan',
        'status', 'http_code', 'respons', 'jadwal_kirim', 'sent_at', 'dibatalkan_at',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jadwal_kirim' => 'datetime',
        'sent_at' => 'datetime',
        'dibatalkan_at' => 'datetime',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'nisn', 'nisn');
    }

    /** Notifikasi yang belum terkirim (masih menunggu jadwal / worker). */
    public function scopeAntre(Builder $query): Builder
    {
        return $query->where('status', 'antre');
    }

    /**
     * Sesi WhatsApp pengirim yang dipakai: nama kelasnya bila kelas itu sudah
     * punya nomor sendiri (hasil scan QR), selain itu sesi Pusat.
     */
    public function labelSesi(): string
    {
        $kelas = $this->siswa?->kelas;

        return trim((string) ($kelas?->wa_number ?? '')) !== '' ? (string) $kelas->nama : 'Pusat';
    }

    public function dibatalkan(): bool
    {
        return $this->status === 'batal';
    }
}
