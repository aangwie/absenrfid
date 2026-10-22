<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TapLog extends Model
{
    protected $table = 'tb_tap_log';

    protected $fillable = ['perangkat_id', 'tag', 'kode', 'pesan', 'ip'];

    public function perangkat(): BelongsTo
    {
        return $this->belongsTo(Perangkat::class, 'perangkat_id');
    }

    /** Warna badge Tailwind untuk kode hasil tap. */
    public function warnaKode(): string
    {
        return match ($this->kode) {
            'MASUK' => 'bg-emerald-500/15 text-emerald-300',
            'TERLAMBAT' => 'bg-amber-500/15 text-amber-300',
            'PULANG' => 'bg-indigo-500/15 text-indigo-300',
            'SUDAH_ABSEN' => 'bg-slate-700/60 text-slate-300',
            default => 'bg-rose-500/15 text-rose-300',
        };
    }
}
