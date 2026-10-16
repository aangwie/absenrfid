<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Siswa extends Model
{
    protected $table = 'tb_siswa';

    protected $fillable = ['nisn', 'rfid_tag', 'nama', 'tempat_lahir', 'tanggal_lahir', 'tahun_masuk', 'foto', 'kelas_id', 'gender', 'no_hp_ortu'];

    protected $casts = ['tanggal_lahir' => 'date', 'tahun_masuk' => 'integer'];

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function absensi(): HasMany
    {
        return $this->hasMany(Absensi::class, 'nisn', 'nisn');
    }
}
