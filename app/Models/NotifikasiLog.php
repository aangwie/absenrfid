<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotifikasiLog extends Model
{
    protected $table = 'tb_notifikasi_log';

    protected $fillable = [
        'nisn', 'tanggal', 'tipe', 'nomor_tujuan', 'pesan',
        'status', 'http_code', 'respons', 'sent_at',
    ];

    protected $casts = ['tanggal' => 'date', 'sent_at' => 'datetime'];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'nisn', 'nisn');
    }
}
