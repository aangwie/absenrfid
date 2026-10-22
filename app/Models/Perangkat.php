<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Perangkat extends Model
{
    protected $table = 'tb_perangkat';

    protected $fillable = ['nama', 'lokasi', 'api_key', 'aktif', 'terakhir_tap_at'];

    protected $casts = [
        'aktif' => 'boolean',
        'terakhir_tap_at' => 'datetime',
    ];

    /** Sembunyikan kunci dari serialisasi JSON tak sengaja. */
    protected $hidden = ['api_key'];

    public function tapLog(): HasMany
    {
        return $this->hasMany(TapLog::class, 'perangkat_id');
    }

    /** Kunci acak 48 karakter hex untuk satu perangkat. */
    public static function kunciBaru(): string
    {
        return bin2hex(random_bytes(24));
    }
}
