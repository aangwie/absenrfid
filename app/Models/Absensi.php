<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Absensi extends Model
{
    protected $table = 'tb_absensi';

    protected $fillable = ['nisn', 'tanggal', 'jam_masuk', 'jam_pulang', 'status_masuk', 'status_pulang'];

    protected $casts = ['tanggal' => 'date'];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'nisn', 'nisn');
    }
}
