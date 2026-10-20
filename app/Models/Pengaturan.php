<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Pengaturan extends Model
{
    protected $table = 'tb_pengaturan';

    protected $fillable = ['kunci', 'nilai'];

    /**
     * Cache per request: semua pemanggilan ambil()/nilai() dalam satu request
     * hanya menghasilkan satu query. Tidak dipakai di console/queue worker
     * (proses berjalan lama) agar perubahan pengaturan selalu terbaca.
     */
    protected static ?array $cache = null;

    public static function ambil(): array
    {
        $cacheable = ! app()->runningInConsole();
        if ($cacheable && static::$cache !== null) {
            return static::$cache;
        }
        try {
            $all = DB::table('tb_pengaturan')->pluck('nilai', 'kunci')->all();
        } catch (\Throwable $e) {
            $all = [];
        }
        if ($cacheable) {
            static::$cache = $all;
        }

        return $all;
    }

    /** Buang cache agar perubahan pengaturan langsung terbaca di request yang sama. */
    public static function lupakan(): void
    {
        static::$cache = null;
    }

    public static function nilai(string $kunci, string $default = ''): string
    {
        $all = static::ambil();
        return isset($all[$kunci]) ? (string) $all[$kunci] : $default;
    }
}
