<?php

namespace Tests\Unit;

use App\Support\WaktuAbsensi;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WaktuAbsensiTest extends TestCase
{
    public function test_kategori_boleh_dan_tutup(): void
    {
        $asli = DB::table('tb_pengaturan')->pluck('nilai', 'kunci')->all();
        try {
            foreach (['masuk_mulai' => '06:30', 'masuk_selesai' => '07:30', 'terlambat_mulai' => '07:31', 'terlambat_selesai' => '13:29', 'pulang_mulai' => '13:30', 'pulang_selesai' => '14:30'] as $k => $v) {
                DB::table('tb_pengaturan')->updateOrInsert(['kunci' => $k], ['nilai' => $v, 'created_at' => now(), 'updated_at' => now()]);
            }
            $this->assertSame('Masuk', WaktuAbsensi::kategori('06:45:00'));
            $this->assertSame('Terlambat', WaktuAbsensi::kategori('08:00:00'));
            $this->assertSame('Pulang', WaktuAbsensi::kategori('13:45:00'));
            $this->assertSame('Tanpa Keterangan', WaktuAbsensi::kategori('05:00:00'));
            $this->assertTrue(WaktuAbsensi::boleh('06:45:00'));
            $this->assertFalse(WaktuAbsensi::boleh('05:00:00'));
            $this->assertFalse(WaktuAbsensi::tutup('07:00:00'));
            $this->assertTrue(WaktuAbsensi::tutup('15:00:00'));
        } finally {
            foreach (['masuk_mulai', 'masuk_selesai', 'terlambat_mulai', 'terlambat_selesai', 'pulang_mulai', 'pulang_selesai'] as $k) {
                if (array_key_exists($k, $asli)) {
                    DB::table('tb_pengaturan')->updateOrInsert(['kunci' => $k], ['nilai' => $asli[$k], 'created_at' => now(), 'updated_at' => now()]);
                } else {
                    DB::table('tb_pengaturan')->where('kunci', $k)->delete();
                }
            }
        }
    }
}
