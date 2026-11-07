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

    public function test_http_200_tapi_success_false_dicatat_gagal(): void
    {
        // Bentuk respons gateway: 200 OK tapi device terputus.
        Http::fake(['*' => Http::response([
            'success' => false,
            'message' => 'Device tidak terhubung',
        ], 200)]);
        $this->setPengaturanWa([
            'wa_url' => 'https://wa.contoh.com/send-message',
            'wa_api_key' => 'KUNCI',
            'wa_number' => '6281111111111',
        ]);
        $kelas = Kelas::create(['nama' => 'XII RPL 1']);
        $siswa = Siswa::create([
            'nisn' => '0099887700', 'rfid_tag' => 'TAG010', 'nama' => 'Anak Gagal',
            'kelas_id' => $kelas->id, 'gender' => 'L', 'no_hp_ortu' => '081234567890',
        ]);
        $log = NotifikasiLog::create([
            'nisn' => $siswa->nisn, 'tanggal' => now()->toDateString(), 'tipe' => 'pulang',
            'nomor_tujuan' => '081234567890', 'pesan' => 'tes', 'status' => 'antre',
        ]);

        (new KirimNotifikasiAbsensi($log->id))->handle();

        $log->refresh();
        $this->assertSame('gagal', $log->status);
        $this->assertStringContainsString('Device tidak terhubung', (string) $log->respons);
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
        $this->assertSame('1', \App\Models\Pengaturan::nilai('wa_kirim_tepat_waktu'));
        $this->assertSame(60, \App\Support\AntreanWhatsapp::jedaDetik());

        $this->actingAs($admin)->get(route('whatsapp.index'))
            ->assertOk()
            ->assertSee('Status Pesan yang Dikirim')
            ->assertSee('Tepat Waktu');

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

        // Semua status yang dicentang memakai antrean dan jeda yang sama.
        $terlambat = \App\Support\AntreanWhatsapp::antre($siswa, $tanggal, 'masuk', 'Terlambat', 'pesan 4');
        $pulang = \App\Support\AntreanWhatsapp::antre($siswa, $tanggal, 'pulang', 'Selesai', 'pesan 5');
        $this->assertSame(60, (int) round($tiga->jadwal_kirim->diffInSeconds($terlambat->jadwal_kirim)));
        $this->assertSame(60, (int) round($terlambat->jadwal_kirim->diffInSeconds($pulang->jadwal_kirim)));

        // Notifikasi manual (Izin/Sakit) tetap boleh dan ikut antrean.
        $manual = \App\Support\AntreanWhatsapp::antre($siswa, $tanggal, 'masuk', 'Izin', 'pesan 6');
        $this->assertNotNull($manual);
        $this->assertSame(60, (int) round($pulang->jadwal_kirim->diffInSeconds($manual->jadwal_kirim)));

        \Illuminate\Support\Facades\Queue::assertPushed(KirimNotifikasiAbsensi::class, 6);
    }

    public function test_status_yang_tidak_dicentang_tidak_dibuatkan_notifikasi(): void
    {
        $this->setPengaturanWa([
            'wa_kirim_tepat_waktu' => '0',
            'wa_kirim_terlambat' => '1',
            'wa_kirim_pulang' => '0',
        ]);

        $siswa = $this->siswaUji('TAGMATI', '0099004400');
        $tanggal = now()->toDateString();

        $this->assertNull(\App\Support\AntreanWhatsapp::antre($siswa, $tanggal, 'masuk', 'Tepat Waktu', 'matikan'));
        $this->assertNull(\App\Support\AntreanWhatsapp::antre($siswa, $tanggal, 'pulang', 'Selesai', 'matikan'));

        $aktif = \App\Support\AntreanWhatsapp::antre($siswa, $tanggal, 'masuk', 'Terlambat', 'kirim');
        $this->assertNotNull($aktif);
        $this->assertSame(1, NotifikasiLog::count());
    }

    public function test_menonaktifkan_status_membatalkan_antrean_lama(): void
    {
        $admin = \App\Models\User::factory()->create();
        $log = NotifikasiLog::create([
            'nisn' => '0099005500', 'tanggal' => now()->toDateString(), 'tipe' => 'pulang',
            'keterangan' => 'Selesai', 'nomor_tujuan' => '6281234567890',
            'pesan' => 'tes nonaktif', 'status' => 'antre', 'jadwal_kirim' => now()->addHour(),
        ]);

        $this->actingAs($admin)->post(route('whatsapp.simpan'), [
            'wa_url' => 'https://wa.contoh.com/send-message',
            'wa_api_key' => 'KUNCI',
            'wa_number' => '6281111111111',
            'wa_batch_menit' => 10,
            'wa_batch_jumlah' => 10,
            'wa_kirim_tepat_waktu' => '1',
            'wa_kirim_terlambat' => '1',
            'wa_kirim_pulang' => '0',
        ])->assertRedirect(route('whatsapp.index', ['tab' => 'konfigurasi']));

        $log->refresh();
        $this->assertSame('batal', $log->status);
        $this->assertSame('Dinonaktifkan di pengaturan WhatsApp.', $log->respons);
        $this->assertSame('0', \App\Models\Pengaturan::nilai('wa_kirim_pulang'));
        $this->assertSame('1', \App\Models\Pengaturan::nilai('wa_kirim_tepat_waktu'));
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

    public function test_sesi_whatsapp_per_kelas_dipakai_saat_mengirim(): void
    {
        Http::fake(['*' => Http::response(['status' => true], 200)]);
        $this->setPengaturanWa([
            'wa_url' => 'https://wa.contoh.com/send-message',
            'wa_api_key' => 'PUSAT',
            'wa_number' => '6281111111111',
        ]);

        // Kelas X punya sesi sendiri (nomor + API key), kelas Y ikut pusat.
        $punyaSesi = Kelas::create(['nama' => 'XII RPL 4', 'wa_number' => '6282222222222', 'wa_api_key' => 'KUNCI-KELAS']);
        $ikutPusat = Kelas::create(['nama' => 'XII RPL 5']);
        $siswaA = Siswa::create([
            'nisn' => '0099006601', 'rfid_tag' => 'TAGSESI1', 'nama' => 'Siswa Sesi A',
            'kelas_id' => $punyaSesi->id, 'gender' => 'L', 'no_hp_ortu' => '081234567890',
        ]);
        $siswaB = Siswa::create([
            'nisn' => '0099006602', 'rfid_tag' => 'TAGSESI2', 'nama' => 'Siswa Sesi B',
            'kelas_id' => $ikutPusat->id, 'gender' => 'L', 'no_hp_ortu' => '081298765432',
        ]);

        foreach ([$siswaA, $siswaB] as $siswa) {
            $log = NotifikasiLog::create([
                'nisn' => $siswa->nisn, 'tanggal' => now()->toDateString(), 'tipe' => 'masuk',
                'keterangan' => 'Tepat Waktu', 'nomor_tujuan' => $siswa->no_hp_ortu,
                'pesan' => 'tes sesi kelas', 'status' => 'antre', 'jadwal_kirim' => now(),
            ]);
            (new KirimNotifikasiAbsensi($log->id))->handle();
            $this->assertSame('terkirim', $log->fresh()->status);
        }

        // wa_url kelas kosong -> tetap memakai URL pusat.
        Http::assertSent(fn ($req) => $req['device_id'] === 'kelas-'.$punyaSesi->id
            && $req['nomor_pengirim'] === '6282222222222'
            && $req['api_key'] === 'KUNCI-KELAS'
            && $req['nomor_penerima'] === '6281234567890'
            && $req->url() === 'https://wa.contoh.com/send-message');
        // Kelas tanpa nomor sendiri memakai device pusat.
        Http::assertSent(fn ($req) => $req['device_id'] === 'pusat'
            && $req['nomor_pengirim'] === '6281111111111'
            && $req['api_key'] === 'PUSAT'
            && $req['nomor_penerima'] === '6281298765432');
    }

    public function test_antrean_tiap_kelas_berjalan_sendiri(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        $this->setPengaturanWa([
            'wa_batch_menit' => '1', 'wa_batch_jumlah' => '1',
            'wa_kirim_tepat_waktu' => '1', 'wa_kirim_terlambat' => '1', 'wa_kirim_pulang' => '1',
        ]);

        $kelasA = Kelas::create(['nama' => 'XII RPL 6']);
        $kelasB = Kelas::create(['nama' => 'XII RPL 7']);
        $buat = fn (string $nisn, string $tag, int $kelasId) => Siswa::create([
            'nisn' => $nisn, 'rfid_tag' => $tag, 'nama' => 'Siswa '.$tag,
            'kelas_id' => $kelasId, 'gender' => 'L', 'no_hp_ortu' => '081234567890',
        ]);
        $a1 = $buat('0099007701', 'TAGA1', $kelasA->id);
        $a2 = $buat('0099007702', 'TAGA2', $kelasA->id);
        $b1 = $buat('0099007703', 'TAGB1', $kelasB->id);
        $tanggal = now()->toDateString();

        $logA1 = \App\Support\AntreanWhatsapp::antre($a1, $tanggal, 'masuk', 'Tepat Waktu', 'a1');
        $logA2 = \App\Support\AntreanWhatsapp::antre($a2, $tanggal, 'masuk', 'Tepat Waktu', 'a2');
        $logB1 = \App\Support\AntreanWhatsapp::antre($b1, $tanggal, 'masuk', 'Tepat Waktu', 'b1');

        // Dalam satu kelas tetap berjarak 60 detik (1 pesan/menit)...
        $this->assertSame(60, (int) round($logA1->jadwal_kirim->diffInSeconds($logA2->jadwal_kirim)));
        // ...tetapi kelas lain tidak ikut menunggu antrean kelas A.
        $this->assertLessThan(5, (int) round(abs($logA1->jadwal_kirim->diffInSeconds($logB1->jadwal_kirim))));
    }

    public function test_scan_qr_menyimpan_nomor_sesi_kelas(): void
    {
        $admin = \App\Models\User::factory()->create();
        $this->setPengaturanWa([
            'wa_url' => 'https://wa.contoh.com/send-message',
            'wa_api_key' => 'PUSAT',
            'wa_gateway_url' => 'https://gateway.contoh.com',
        ]);
        $kelas = Kelas::create(['nama' => 'XII RPL 8']);

        // Sebelum discan: belum ada nomor, pengiriman jatuh ke device pusat.
        $this->assertSame('kelas-'.$kelas->id, \App\Support\AntreanWhatsapp::deviceKelas($kelas->id));
        $this->assertSame('pusat', \App\Support\AntreanWhatsapp::sesi($kelas->id)['device']);

        Http::fake(['*' => Http::response(['status' => 'connected', 'phone_number' => '6282222222222'], 200)]);

        $this->actingAs($admin)->get(route('whatsapp.qr', ['kelas' => $kelas->id]))
            ->assertOk()
            ->assertJson(['status' => 'connected', 'phone_number' => '6282222222222', 'device' => 'kelas-'.$kelas->id]);

        // Nomor diambil dari hasil scan, bukan dari input manual.
        $this->assertSame('6282222222222', $kelas->fresh()->wa_number);
        // API Key sesi ikut digenerate saat scan berhasil.
        $this->assertNotEmpty($kelas->fresh()->wa_api_key);
        Http::assertSent(fn ($req) => $req->url() === 'https://gateway.contoh.com/qr/kelas-'.$kelas->id);

        // Setelah punya nomor, sesi kelas itu yang dipakai untuk mengirim.
        $sesi = \App\Support\AntreanWhatsapp::sesi($kelas->id);
        $this->assertSame('kelas-'.$kelas->id, $sesi['device']);
        $this->assertSame('6282222222222', $sesi['wa_number']);

        // Tabel koneksi menampilkan nomor hasil scan.
        $this->actingAs($admin)->get(route('whatsapp.index'))
            ->assertOk()
            ->assertSee('Koneksi WhatsApp')
            ->assertSee('Nomor Pengirim')
            ->assertSee('6282222222222')
            ->assertSee('XII RPL 8');
    }

    public function test_scan_qr_pusat_menyimpan_nomor_pusat(): void
    {
        $admin = \App\Models\User::factory()->create();
        $this->setPengaturanWa([
            'wa_url' => 'https://wa.contoh.com/send-message',
            'wa_api_key' => 'PUSAT',
            'wa_gateway_url' => 'https://gateway.contoh.com',
        ]);

        Http::fake(['*' => Http::response(['status' => 'connected', 'phone_number' => '6281111111111'], 200)]);

        $this->actingAs($admin)->get(route('whatsapp.qr'))
            ->assertOk()
            ->assertJson(['status' => 'connected', 'device' => 'pusat']);

        $this->assertSame('6281111111111', \App\Models\Pengaturan::nilai('wa_number'));
        // Kunci yang sudah ada tidak ditimpa saat scan ulang.
        $this->assertSame('PUSAT', \App\Models\Pengaturan::nilai('wa_api_key'));
        Http::assertSent(fn ($req) => $req->url() === 'https://gateway.contoh.com/qr/pusat');
    }

    public function test_api_key_pusat_digenerate_saat_scan_dan_stabil(): void
    {
        $admin = \App\Models\User::factory()->create();
        $this->setPengaturanWa([
            'wa_url' => 'https://wa.contoh.com/send-message',
            'wa_gateway_url' => 'https://gateway.contoh.com',
        ]);
        $this->assertSame('', \App\Models\Pengaturan::nilai('wa_api_key'));

        // Gateway tidak memvalidasi api_key; kunci hanya perlu ada & stabil.
        Http::fake(['*' => Http::response(['status' => 'connected', 'phone_number' => '6281111111111'], 200)]);

        $this->actingAs($admin)->get(route('whatsapp.qr'))->assertOk();
        $kunci = \App\Models\Pengaturan::nilai('wa_api_key');
        $this->assertStringStartsWith('wa_', $kunci);

        // Scan ulang sesi yang sama tidak membuat kunci baru.
        $this->actingAs($admin)->get(route('whatsapp.qr', ['force' => 1]))->assertOk();
        $this->assertSame($kunci, \App\Models\Pengaturan::nilai('wa_api_key'));
    }

    public function test_simpan_konfigurasi_tanpa_api_key_tetap_tersimpan(): void
    {
        $admin = \App\Models\User::factory()->create();

        $this->actingAs($admin)->post(route('whatsapp.simpan'), [
            'wa_url' => 'https://wa.contoh.com/send-message',
            'wa_gateway_url' => 'https://gateway.billnesia.com',
            'wa_batch_menit' => 10,
            'wa_batch_jumlah' => 10,
        ])->assertRedirect(route('whatsapp.index', ['tab' => 'konfigurasi']));

        $this->assertStringStartsWith('wa_', \App\Models\Pengaturan::nilai('wa_api_key'));
    }

    public function test_qr_memakai_device_kelas_walau_nomor_belum_ada(): void
    {
        $admin = \App\Models\User::factory()->create();
        $this->setPengaturanWa([
            'wa_url' => 'https://wa.contoh.com/send-message',
            'wa_api_key' => 'PUSAT',
            'wa_gateway_url' => 'https://gateway.contoh.com/',
        ]);
        $kelas = Kelas::create(['nama' => 'XII RPL 9']);

        Http::fake(['*' => Http::response([
            'status' => 'pending',
            'qr' => 'data:image/png;base64,AAA',
        ], 200)]);

        $this->actingAs($admin)->get(route('whatsapp.qr', ['kelas' => $kelas->id]))
            ->assertOk()
            ->assertJson(['status' => 'pending', 'qr' => 'data:image/png;base64,AAA', 'device' => 'kelas-'.$kelas->id]);

        // Device kelas tetap bisa diminta walau nomornya belum diketahui;
        // host gateway diambil dari wa_gateway_url, bukan dari wa_url.
        Http::assertSent(fn ($req) => $req->url() === 'https://gateway.contoh.com/qr/kelas-'.$kelas->id);
        $this->assertNull($kelas->fresh()->wa_number);

        // Tombol Scan Ulang = paksa gateway menghapus sesi lama.
        $this->actingAs($admin)->get(route('whatsapp.qr', ['kelas' => $kelas->id, 'force' => 1]))->assertOk();
        Http::assertSent(fn ($req) => $req->url() === 'https://gateway.contoh.com/qr/kelas-'.$kelas->id.'?force=true');
    }

    public function test_qr_menolak_tanpa_url_gateway(): void
    {
        $admin = \App\Models\User::factory()->create();
        $this->setPengaturanWa([
            'wa_url' => 'https://wa.contoh.com/send-message',
            'wa_api_key' => 'PUSAT',
            'wa_number' => '6281111111111',
        ]);

        // Nomor pusat ada, tapi URL Gateway belum diisi.
        $this->actingAs($admin)->get(route('whatsapp.qr'))
            ->assertStatus(422)
            ->assertJsonPath('status', 'error');

        Http::assertNothingSent();
    }

    public function test_url_gateway_disimpan_dari_tab_konfigurasi(): void
    {
        $admin = \App\Models\User::factory()->create();

        $this->actingAs($admin)->post(route('whatsapp.simpan'), [
            'wa_url' => 'https://wa.contoh.com/send-message',
            'wa_api_key' => 'KUNCI',
            'wa_number' => '6281111111111',
            'wa_gateway_url' => 'https://gateway.billnesia.com',
            'wa_batch_menit' => 10,
            'wa_batch_jumlah' => 10,
        ])->assertRedirect(route('whatsapp.index', ['tab' => 'konfigurasi']));

        $this->assertSame('https://gateway.billnesia.com', \App\Models\Pengaturan::nilai('wa_gateway_url'));
        $this->actingAs($admin)->get(route('whatsapp.index'))
            ->assertOk()->assertSee('Koneksi WhatsApp')->assertSee('URL Gateway (untuk QR)');
    }

    public function test_putuskan_sesi_mengosongkan_nomor_pengirim(): void
    {
        $admin = \App\Models\User::factory()->create();
        $this->setPengaturanWa([
            'wa_url' => 'https://wa.contoh.com/send-message',
            'wa_api_key' => 'PUSAT',
            'wa_gateway_url' => 'https://gateway.contoh.com',
        ]);
        $kelas = Kelas::create(['nama' => 'XII RPL 10', 'wa_number' => '6283333333333']);

        Http::fake(['*' => Http::response(['success' => true], 200)]);

        $this->actingAs($admin)->post(route('whatsapp.putuskan'), ['kelas' => $kelas->id])
            ->assertRedirect(route('whatsapp.index', ['tab' => 'konfigurasi']));

        Http::assertSent(fn ($req) => $req->url() === 'https://gateway.contoh.com/disconnect/kelas-'.$kelas->id);
        // Nomor dikosongkan: kelas itu kembali memakai sesi pusat.
        $this->assertNull($kelas->fresh()->wa_number);
        $this->assertSame('pusat', \App\Support\AntreanWhatsapp::sesi($kelas->id)['device']);
    }

    public function test_status_semua_sesi_dibaca_dari_gateway(): void
    {
        $admin = \App\Models\User::factory()->create();
        $this->setPengaturanWa([
            'wa_url' => 'https://wa.contoh.com/send-message',
            'wa_api_key' => 'PUSAT',
            'wa_gateway_url' => 'https://gateway.contoh.com',
        ]);
        $kelas = Kelas::create(['nama' => 'XII RPL 11', 'wa_number' => '6284444444444']);

        Http::fake(['*' => Http::response(['status' => 'connected', 'phone_number' => '6284444444444'], 200)]);

        $this->actingAs($admin)->get(route('whatsapp.status'))
            ->assertOk()
            ->assertJsonPath('gateway', true)
            ->assertJsonPath('sesi.pusat.status', 'connected')
            ->assertJsonPath('sesi.kelas-'.$kelas->id.'.status', 'connected');
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
