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

    public function test_tombol_nfc_ada_di_kios_dan_dashboard(): void
    {
        $this->get('/')->assertOk()->assertSee('NfcHelper', false)->assertSee('Scan NFC HP', false);
        $admin = \App\Models\User::factory()->create();
        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()->assertSee('NfcHelper', false)->assertSee('nfc-btn', false);
    }

    public function test_hapus_semua_log_notifikasi(): void
    {
        $admin = \App\Models\User::factory()->create();
        \App\Models\NotifikasiLog::create(['nisn' => 'X1', 'tanggal' => now()->toDateString(), 'tipe' => 'masuk', 'nomor_tujuan' => '6281', 'pesan' => 'a', 'status' => 'terkirim']);
        \App\Models\NotifikasiLog::create(['nisn' => 'X2', 'tanggal' => now()->toDateString(), 'tipe' => 'pulang', 'nomor_tujuan' => '6282', 'pesan' => 'b', 'status' => 'gagal']);
        $this->actingAs($admin)->delete(route('whatsapp.log-hapus'))->assertRedirect(route('whatsapp.index', ['tab' => 'log']));
        $this->assertSame(0, \App\Models\NotifikasiLog::count());
    }

    public function test_tab_tes_cron_ping_dan_cek(): void
    {
        $admin = \App\Models\User::factory()->create();
        $this->actingAs($admin)->get(route('whatsapp.index', ['tab' => 'cron']))->assertOk();
        // Queue sync di testing = worker seketika: ping langsung dieksekusi.
        $this->actingAs($admin)->post(route('whatsapp.ping'))->assertRedirect(route('whatsapp.index', ['tab' => 'cron']));
        $res = $this->actingAs($admin)->post(route('whatsapp.cek'));
        $res->assertRedirect(route('whatsapp.index', ['tab' => 'cron']));
        $hasil = session('wa_cron_hasil');
        $this->assertNotNull($hasil);
        $this->assertTrue($hasil['sukses']);
        $this->assertStringContainsString('AKTIF', $hasil['ringkasan']);
    }

    public function test_dokumentasi_semua_tab_bisa_dibuka(): void
    {
        $admin = \App\Models\User::factory()->create();
        foreach (['dashboard', 'siswa', 'kelas', 'laporan', 'whatsapp', 'pengaturan', 'ngawur'] as $tab) {
            $this->actingAs($admin)->get(route('dokumentasi.index', ['tab' => $tab]))->assertOk();
        }
    }

    public function test_tombol_perawatan_jalankan_antrean(): void
    {
        $admin = \App\Models\User::factory()->create();
        $this->actingAs($admin)->get(route('pengaturan.index', ['tab' => 'perawatan']))->assertOk();
        $res = $this->actingAs($admin)->post(route('pengaturan.rawat'), ['aksi' => 'queue-work']);
        $res->assertRedirect(route('pengaturan.index', ['tab' => 'perawatan']));
        $this->assertStringContainsString('Antrean jobs', session('log') ?? '');
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

    public function test_pengaturan_durasi_dan_jumlah_pesan_disimpan(): void
    {
        $admin = \App\Models\User::factory()->create();

        $this->actingAs($admin)->post(route('whatsapp.simpan'), [
            'wa_url' => 'https://wa.contoh.com/send-message',
            'wa_api_key' => 'KUNCI',
            'wa_number' => '6281111111111',
            'wa_batch_menit' => 10,
            'wa_batch_jumlah' => 10,
        ])->assertRedirect(route('whatsapp.index', ['tab' => 'konfigurasi']));

        $this->assertSame('10', \App\Models\Pengaturan::nilai('wa_batch_menit'));
        $this->assertSame('10', \App\Models\Pengaturan::nilai('wa_batch_jumlah'));
        $this->assertSame(60, \App\Support\AntreanWhatsapp::jedaDetik());

        $this->actingAs($admin)->post(route('whatsapp.simpan'), [
            'wa_url' => 'https://wa.contoh.com/send-message',
            'wa_api_key' => 'KUNCI',
            'wa_number' => '6281111111111',
            'wa_batch_menit' => 0,
            'wa_batch_jumlah' => 0,
        ])->assertSessionHasErrors(['wa_batch_menit', 'wa_batch_jumlah']);
    }

    public function test_masuk_tepat_waktu_dijadwalkan_bertahap_lainnya_segera(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        $this->setPengaturanWa(['wa_batch_menit' => '10', 'wa_batch_jumlah' => '10']);

        $siswa = $this->siswaUji('TAGJADWAL', '0099001100');
        $tanggal = now()->toDateString();
        $awal = now();

        $satu = \App\Support\AntreanWhatsapp::antre($siswa, $tanggal, 'masuk', 'Tepat Waktu', 'pesan 1');
        $dua = \App\Support\AntreanWhatsapp::antre($siswa, $tanggal, 'masuk', 'Tepat Waktu', 'pesan 2');
        $tiga = \App\Support\AntreanWhatsapp::antre($siswa, $tanggal, 'masuk', 'Tepat Waktu', 'pesan 3');

        $this->assertTrue($satu->jadwal_kirim->greaterThanOrEqualTo($awal->copy()->subSecond()));
        $this->assertSame(60, (int) round($satu->jadwal_kirim->diffInSeconds($dua->jadwal_kirim)));
        $this->assertSame(60, (int) round($dua->jadwal_kirim->diffInSeconds($tiga->jadwal_kirim)));

        // Terlambat tidak ditampung: tidak ikut antrean 60 detik.
        $terlambat = \App\Support\AntreanWhatsapp::antre($siswa, $tanggal, 'masuk', 'Terlambat', 'pesan 4');
        $this->assertTrue($terlambat->jadwal_kirim->lessThan($dua->jadwal_kirim));

        \Illuminate\Support\Facades\Queue::assertPushed(KirimNotifikasiAbsensi::class, 4);
    }

    public function test_pesan_antrean_bisa_dibatalkan_dan_tidak_terkirim(): void
    {
        $admin = \App\Models\User::factory()->create();
        $log = NotifikasiLog::create([
            'nisn' => '0099002200', 'tanggal' => now()->toDateString(), 'tipe' => 'masuk',
            'keterangan' => 'Tepat Waktu', 'nomor_tujuan' => '6281234567890',
            'pesan' => 'tes batal', 'status' => 'antre', 'jadwal_kirim' => now()->addMinutes(5),
        ]);

        $this->actingAs($admin)->get(route('whatsapp.index', ['tab' => 'antrean']))
            ->assertOk()->assertSee('Batalkan');

        $this->actingAs($admin)->delete(route('whatsapp.antrean-batal', $log))
            ->assertRedirect(route('whatsapp.index', ['tab' => 'antrean']));

        $log->refresh();
        $this->assertSame('batal', $log->status);
        $this->assertNotNull($log->dibatalkan_at);

        Http::fake();
        (new KirimNotifikasiAbsensi($log->id))->handle();
        Http::assertNothingSent();
    }

    public function test_pesan_sudah_terkirim_tidak_bisa_dibatalkan(): void
    {
        $admin = \App\Models\User::factory()->create();
        $log = NotifikasiLog::create([
            'nisn' => '0099003300', 'tanggal' => now()->toDateString(), 'tipe' => 'pulang',
            'keterangan' => 'Selesai', 'nomor_tujuan' => '6281234567890',
            'pesan' => 'tes', 'status' => 'terkirim', 'sent_at' => now(),
        ]);

        $this->actingAs($admin)->delete(route('whatsapp.antrean-batal', $log))
            ->assertSessionHasErrors('msg');
        $this->assertSame('terkirim', $log->fresh()->status);
    }

    private function setPengaturanWa(array $nilai): void
    {
        $baris = collect($nilai)->map(fn ($v, $k) => [
            'kunci' => $k, 'nilai' => (string) $v, 'created_at' => now(), 'updated_at' => now(),
        ])->values()->all();

        \App\Models\Pengaturan::query()->upsert($baris, ['kunci'], ['nilai', 'updated_at']);
        \App\Models\Pengaturan::lupakan();
    }

    private function siswaUji(string $tag, string $nisn): Siswa
    {
        $kelas = Kelas::create(['nama' => 'XII RPL ' . substr($nisn, -2)]);

        return Siswa::create([
            'nisn' => $nisn, 'rfid_tag' => $tag, 'nama' => 'Siswa ' . $tag,
            'kelas_id' => $kelas->id, 'gender' => 'L', 'no_hp_ortu' => '081234567890',
        ]);
    }
}
