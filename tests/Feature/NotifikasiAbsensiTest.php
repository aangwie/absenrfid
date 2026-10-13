<?php

namespace Tests\Feature;

use App\Jobs\KirimNotifikasiAbsensi;
use App\Models\Kelas;
use App\Models\NotifikasiLog;
use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NotifikasiAbsensiTest extends TestCase
{
    use RefreshDatabase;

    public function test_crud_siswa_menyimpan_no_hp_ortu(): void
    {
        $kelas = Kelas::create(['nama' => 'XII RPL 1']);
        $siswa = Siswa::create([
            'nisn' => '0011223344', 'rfid_tag' => 'TAG001', 'nama' => 'Anak Tes',
            'kelas_id' => $kelas->id, 'gender' => 'L', 'no_hp_ortu' => '081234567890',
        ]);
        $this->assertSame('081234567890', $siswa->fresh()->no_hp_ortu);

        $this->actingAs(\App\Models\User::factory()->create())
            ->put(route('siswa.update', $siswa), [
                'nisn' => '0011223344', 'rfid_tag' => 'TAG001', 'nama' => 'Anak Tes',
                'kelas_id' => $kelas->id, 'gender' => 'L', 'no_hp_ortu' => '6281234567890',
            ])->assertRedirect(route('siswa.index'));
        $this->assertSame('6281234567890', $siswa->fresh()->no_hp_ortu);
    }

    public function test_normalisasi_nomor(): void
    {
        $this->assertSame('6281234567890', KirimNotifikasiAbsensi::normalisasiNomor('081234567890'));
        $this->assertSame('6281234567890', KirimNotifikasiAbsensi::normalisasiNomor('+6281234567890'));
        $this->assertSame('6281234567890', KirimNotifikasiAbsensi::normalisasiNomor('6281234567890'));
        $this->assertSame('', KirimNotifikasiAbsensi::normalisasiNomor(''));
        $this->assertSame('', KirimNotifikasiAbsensi::normalisasiNomor('abc'));
    }

    public function test_job_mencatat_terkirim_dan_gagal(): void
    {
        Http::fakeSequence()->push(['status' => true], 200)->push('kuota habis', 400);
        $kelas = Kelas::create(['nama' => 'XII RPL 1']);
        $siswa = Siswa::create([
            'nisn' => '0099887766', 'rfid_tag' => 'TAG009', 'nama' => 'Anak Kirim',
            'kelas_id' => $kelas->id, 'gender' => 'P', 'no_hp_ortu' => '081234567890',
        ]);
        \App\Models\Pengaturan::query()->upsert([
            ['kunci' => 'wa_url', 'nilai' => 'https://wa.contoh.com/send-message', 'created_at' => now(), 'updated_at' => now()],
            ['kunci' => 'wa_api_key', 'nilai' => 'KUNCI', 'created_at' => now(), 'updated_at' => now()],
            ['kunci' => 'wa_number', 'nilai' => '6281111111111', 'created_at' => now(), 'updated_at' => now()],
        ], ['kunci'], ['nilai', 'updated_at']);

        $log = NotifikasiLog::create([
            'nisn' => $siswa->nisn, 'tanggal' => now()->toDateString(), 'tipe' => 'masuk',
            'nomor_tujuan' => '081234567890', 'pesan' => 'tes', 'status' => 'antre',
        ]);
        (new KirimNotifikasiAbsensi($log->id))->handle();
        $log->refresh();
        $this->assertSame('terkirim', $log->status);
        $this->assertSame(200, (int) $log->http_code);
        $this->assertSame('6281234567890', $log->nomor_tujuan);
        Http::assertSent(fn ($req) => $req['nomor_penerima'] === '6281234567890'
            && $req['nomor_pengirim'] === '6281111111111'
            && $req->header('Content-Type')[0] === 'application/x-www-form-urlencoded');

        $log2 = NotifikasiLog::create([
            'nisn' => $siswa->nisn, 'tanggal' => now()->toDateString(), 'tipe' => 'pulang',
            'nomor_tujuan' => '081234567890', 'pesan' => 'tes', 'status' => 'antre',
        ]);
        (new KirimNotifikasiAbsensi($log2->id))->handle();
        $this->assertSame('gagal', $log2->fresh()->status);
        $this->assertSame(400, (int) $log2->fresh()->http_code);
    }

    public function test_template_memuat_kolom_no_hp_ortu(): void
    {
        $res = $this->actingAs(\App\Models\User::factory()->create())->get(route('siswa.template'));
        $res->assertOk();
        $path = $res->baseResponse->getFile()->getPathname();
        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($path));
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        $this->assertIsString($xml);
        foreach (['NISN', 'RFID_TAG', 'NAMA', 'KELAS', 'GENDER', 'NO_HP_ORTU', '6281234567890'] as $kata) {
            $this->assertStringContainsString($kata, $xml);
        }
    }

    public function test_tap_membuat_log_dan_mengirim_saat_worker_jalan(): void
    {
        Http::fake(['*' => Http::response(['status' => true], 200)]);
        $kelas = Kelas::create(['nama' => 'XII RPL 1']);
        $siswa = Siswa::create([
            'nisn' => '0077665544', 'rfid_tag' => 'TAG007', 'nama' => 'Anak Tap',
            'kelas_id' => $kelas->id, 'gender' => 'L', 'no_hp_ortu' => '081234567890',
        ]);
        // Longgarkan jam absensi agar tap selalu lolos.
        \App\Models\Pengaturan::query()->upsert(collect([
            'masuk_mulai' => '00:00', 'masuk_selesai' => '23:58',
            'terlambat_mulai' => '23:58', 'terlambat_selesai' => '23:59',
            'pulang_mulai' => '00:00', 'pulang_selesai' => '23:59',
            'wa_url' => 'https://wa.contoh.com/send-message', 'wa_api_key' => 'KUNCI', 'wa_number' => '6281111111111',
        ])->map(fn ($v, $k) => ['kunci' => $k, 'nilai' => $v, 'created_at' => now(), 'updated_at' => now()])->values()->all(), ['kunci'], ['nilai', 'updated_at']);

        // Queue sync di testing = worker jalan seketika: tap -> log -> kirim -> catat.
        $this->postJson('/tap', ['tag' => 'TAG007'])->assertOk()->assertJson(['ok' => true]);
        $log = NotifikasiLog::where('nisn', $siswa->nisn)->first();
        $this->assertNotNull($log);
        $this->assertSame('terkirim', $log->status);
        $this->assertStringContainsString('Anak Tap', $log->pesan);
    }

    public function test_tap_mengantre_job_tanpa_memblokir_respons(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        $kelas = Kelas::create(['nama' => 'XII RPL 2']);
        $siswa = Siswa::create([
            'nisn' => '0077665545', 'rfid_tag' => 'TAG008', 'nama' => 'Anak Antre',
            'kelas_id' => $kelas->id, 'gender' => 'L', 'no_hp_ortu' => '081234567891',
        ]);
        \App\Models\Pengaturan::query()->upsert(collect([
            'masuk_mulai' => '00:00', 'masuk_selesai' => '23:58',
            'terlambat_mulai' => '23:58', 'terlambat_selesai' => '23:59',
            'pulang_mulai' => '00:00', 'pulang_selesai' => '23:59',
        ])->map(fn ($v, $k) => ['kunci' => $k, 'nilai' => $v, 'created_at' => now(), 'updated_at' => now()])->values()->all(), ['kunci'], ['nilai', 'updated_at']);

        $this->postJson('/tap', ['tag' => 'TAG008'])->assertOk()->assertJson(['ok' => true]);
        $log = NotifikasiLog::where('nisn', $siswa->nisn)->first();
        $this->assertNotNull($log);
        $this->assertSame('antre', $log->status);
        $this->assertStringContainsString('Anak Antre', $log->pesan);
        \Illuminate\Support\Facades\Queue::assertPushed(KirimNotifikasiAbsensi::class);
    }
}
