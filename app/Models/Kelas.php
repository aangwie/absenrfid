<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kelas extends Model
{
    protected $table = 'tb_kelas';

    protected $fillable = ['nama', 'wa_number', 'wa_api_key', 'wa_url'];

    public function siswas(): HasMany
    {
        return $this->hasMany(Siswa::class, 'kelas_id');
    }
}
