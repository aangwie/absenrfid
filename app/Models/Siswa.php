<?php

namespace App\Models;

use App\Support\FotoWebp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Siswa extends Model
{
    protected $table = 'tb_siswa';

    protected $fillable = ['nisn', 'rfid_tag', 'nama', 'tempat_lahir', 'tanggal_lahir', 'tahun_masuk', 'foto', 'kelas_id', 'gender', 'no_hp_ortu'];

    protected $casts = ['tanggal_lahir' => 'date', 'tahun_masuk' => 'integer'];

    /** URL siap pakai untuk <img>: file di storage/app/public atau data URI lama. */
    public function getFotoUrlAttribute(): ?string
    {
        $foto = $this->attributes['foto'] ?? null;
        if (! $foto) {
            return null;
        }
        if (str_starts_with($foto, 'data:') || preg_match('~^https?://~i', $foto)) {
            return $foto;
        }

        return Storage::disk('public')->url($foto);
    }

    /** Hapus file foto siswa (bila tersimpan sebagai file, bukan base64). */
    public function hapusFoto(): void
    {
        FotoWebp::hapus($this->attributes['foto'] ?? null);
    }

    protected static function booted(): void
    {
        static::deleting(fn (Siswa $siswa) => $siswa->hapusFoto());
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function absensi(): HasMany
    {
        return $this->hasMany(Absensi::class, 'nisn', 'nisn');
    }
}
