<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Pengaturan extends Model
{
    protected $table = 'tb_pengaturan';

    protected $fillable = ['kunci', 'nilai'];

    public static function ambil(): array
    {
        try {
            return DB::table('tb_pengaturan')->pluck('nilai', 'kunci')->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function nilai(string $kunci, string $default = ''): string
    {
        $all = static::ambil();
        return isset($all[$kunci]) ? (string) $all[$kunci] : $default;
    }
}
