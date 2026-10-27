<?php

namespace App\Http\Controllers;

use App\Jobs\CronPing;
use App\Models\NotifikasiLog;
use App\Models\Pengaturan;
use App\Support\AntreanWhatsapp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WhatsappController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'konfigurasi');
        if (! in_array($tab, ['konfigurasi', 'antrean', 'uji', 'log', 'cron'], true)) {
            $tab = 'konfigurasi';
        }
        $def = [
            'wa_url' => '', 'wa_api_key' => '', 'wa_number' => '',
            'wa_batch_menit' => (string) AntreanWhatsapp::DURASI_DEFAULT,
            'wa_batch_jumlah' => (string) AntreanWhatsapp::JUMLAH_DEFAULT,
        ];
        $cfg = array_merge($def, Pengaturan::ambil());

        $jumlahAntre = NotifikasiLog::query()->antre()->count();
        $logs = $tab === 'log'
            ? NotifikasiLog::with('siswa')->orderByDesc('id')->paginate(10)->withQueryString()
            : null;
        $antrean = $tab === 'antrean'
            ? NotifikasiLog::with('siswa')->antre()->orderBy('jadwal_kirim')->orderBy('id')->paginate(20)->withQueryString()
            : null;
        $cron = $tab === 'cron' ? $this->statusCron($request) : null;
        $jadwal = $tab === 'antrean' ? [
            'jeda' => AntreanWhatsapp::jedaDetik(),
            'terakhir' => AntreanWhatsapp::perkiraanSelesai(),
            'ditampung' => NotifikasiLog::query()->antre()
                ->where('tipe', 'masuk')->where('keterangan', 'Tepat Waktu')->count(),
        ] : null;

        return view('whatsapp.index', compact('tab', 'cfg', 'logs', 'cron', 'antrean', 'jadwal', 'jumlahAntre'));
    }

    public function simpan(Request $request)
    {
        $data = $request->validate([
            'wa_url' => ['required', 'url', 'max:500'],
            'wa_api_key' => ['required', 'string', 'max:500'],
            'wa_number' => ['required', 'string', 'max:30'],
            'wa_batch_menit' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'wa_batch_jumlah' => ['nullable', 'integer', 'min:1', 'max:1440'],
        ], [
            'wa_url.required' => 'URL API wajib diisi.',
            'wa_url.url' => 'URL API tidak valid.',
            'wa_api_key.required' => 'API Key wajib diisi.',
            'wa_number.required' => 'Number pengirim wajib diisi.',
            'wa_batch_menit.integer' => 'Durasi harus berupa angka (menit).',
            'wa_batch_menit.min' => 'Durasi minimal 1 menit.',
            'wa_batch_menit.max' => 'Durasi maksimal 1440 menit (24 jam).',
            'wa_batch_jumlah.integer' => 'Jumlah pesan harus berupa angka.',
            'wa_batch_jumlah.min' => 'Jumlah pesan minimal 1.',
            'wa_batch_jumlah.max' => 'Jumlah pesan maksimal 1440.',
        ]);

        $nilai = [
            'wa_url' => trim($data['wa_url']),
            'wa_api_key' => trim($data['wa_api_key']),
            'wa_number' => trim($data['wa_number']),
            'wa_batch_menit' => (string) ($data['wa_batch_menit'] ?? AntreanWhatsapp::durasiMenit()),
            'wa_batch_jumlah' => (string) ($data['wa_batch_jumlah'] ?? AntreanWhatsapp::jumlahPesan()),
        ];

        foreach ($nilai as $k => $v) {
            DB::table('tb_pengaturan')->updateOrInsert(['kunci' => $k], ['nilai' => $v, 'updated_at' => now(), 'created_at' => now()]);
        }

        Pengaturan::lupakan();

        $perMenit = max(1, (int) round(AntreanWhatsapp::jumlahPesan() / AntreanWhatsapp::durasiMenit()));

        return redirect()->route('whatsapp.index', ['tab' => 'konfigurasi'])
            ->with('ok', 'Konfigurasi WhatsApp tersimpan. Jadwal kirim: ±' . $perMenit . ' pesan/menit (1 pesan tiap ' . AntreanWhatsapp::jedaDetik() . ' detik).');
    }

    public function uji(Request $request)
    {
        $data = $request->validate([
            'tujuan' => ['required', 'string', 'max:30'],
            'pesan' => ['required', 'string', 'max:1000'],
        ], [
            'tujuan.required' => 'Nomor tujuan wajib diisi.',
            'pesan.required' => 'Pesan wajib diisi.',
        ]);

        $cfg = Pengaturan::ambil();
        $url = trim($cfg['wa_url'] ?? '');
        $apiKey = trim($cfg['wa_api_key'] ?? '');
        $sender = trim($cfg['wa_number'] ?? '');

        if (! $url || ! $apiKey || ! $sender) {
            return redirect()->route('whatsapp.index', ['tab' => 'uji'])->withInput()->withErrors(['msg' => 'Konfigurasi WhatsApp (URL/API Key/Number) belum disimpan.'])->with('wa_hasil', [
                'sukses' => false, 'http' => null, 'tujuan' => trim($data['tujuan']), 'waktu' => now()->toDateTimeString(),
                'ringkasan' => 'Konfigurasi belum lengkap. Isi URL, API Key, dan Number dulu di tab Konfigurasi.',
                'detail' => 'wa_url / wa_api_key / wa_number kosong.',
            ]);
        }

        try {
            $tujuan = trim($data['tujuan']);
            $res = Http::withHeaders(['Content-Type' => 'application/x-www-form-urlencoded'])
                ->asForm()
                ->timeout(30)
                ->post($url, [
                    'nomor_pengirim' => $sender,
                    'api_key' => $apiKey,
                    'nomor_penerima' => $tujuan,
                    'pesan' => $data['pesan'],
                ]);

            $body = (string) $res->body();
            $detail = $body;
            $decoded = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $detail = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
            if (strlen($detail) > 2000) {
                $detail = substr($detail, 0, 2000) . '... (dipotong)';
            }
            $log = 'POST ' . $url . ' [form-urlencoded]' . "\n"
                . 'HTTP ' . $res->status() . "\n" . $detail;

            if ($res->successful()) {
                $ket = 'Berhasil: API merespons HTTP ' . $res->status() . '. Cek detail respons & pastikan pesan masuk di nomor tujuan.';
                if (is_array($decoded)) {
                    foreach (['message', 'msg', 'status', 'result', 'success'] as $k) {
                        if (isset($decoded[$k]) && (is_string($decoded[$k]) || is_numeric($decoded[$k]) || is_bool($decoded[$k]))) {
                            $ket .= ' | ' . $k . ': ' . (is_bool($decoded[$k]) ? ($decoded[$k] ? 'true' : 'false') : $decoded[$k]);
                        }
                    }
                }
                return redirect()->route('whatsapp.index', ['tab' => 'uji'])->withInput()->with('ok', 'Pesan terkirim (HTTP ' . $res->status() . ').')->with('log', $log)->with('wa_hasil', [
                    'sukses' => true, 'http' => $res->status(), 'tujuan' => $tujuan, 'waktu' => now()->toDateTimeString(),
                    'ringkasan' => $ket, 'detail' => $detail,
                ]);
            }

            return redirect()->route('whatsapp.index', ['tab' => 'uji'])->withInput()->withErrors(['msg' => 'Gagal kirim (HTTP ' . $res->status() . '). Periksa detail respons di bawah.'])->with('log', $log)->with('wa_hasil', [
                'sukses' => false, 'http' => $res->status(), 'tujuan' => $tujuan, 'waktu' => now()->toDateTimeString(),
                'ringkasan' => 'Gagal: API merespons HTTP ' . $res->status() . ' (bukan 2xx). Umumnya API Key / sender / format nomor salah, atau kuota habis. Lihat detail respons.',
                'detail' => $detail,
            ]);
        } catch (\Throwable $e) {
            return redirect()->route('whatsapp.index', ['tab' => 'uji'])->withInput()->withErrors(['msg' => 'Gagal kirim: ' . $e->getMessage()])->with('wa_hasil', [
                'sukses' => false, 'http' => null, 'tujuan' => trim($data['tujuan']), 'waktu' => now()->toDateTimeString(),
                'ringkasan' => 'Gagal: koneksi ke API bermasalah (' . $e->getMessage() . '). Cek URL, jaringan server, dan timeout.',
                'detail' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Tes cron hosting: antrekan satu job ping. Ping hanya dieksekusi bila
     * ada worker (cron / queue:work manual / worker kontinu) yang memproses
     * antrean. Hasilnya dibaca lewat cek().
     */
    public function ping(Request $request)
    {
        $id = 'ping-' . now()->format('YmdHis') . '-' . Str::lower(Str::random(4));
        CronPing::dispatch($id);
        session(['cron_ping' => ['id' => $id, 'at' => now()->toDateTimeString()]]);

        return redirect()->route('whatsapp.index', ['tab' => 'cron'])
            ->with('ok', 'Ping uji dikirim ke antrean (' . $id . '). Tunggu ±1 menit (satu siklus cron), JANGAN klik Jalankan Antrean dulu, lalu klik Cek Hasil.');
    }

    public function cek(Request $request)    {
        $kirim = session('cron_ping');
        $cfg = Pengaturan::ambil();
        $status = $this->statusCron($request);
        $cocok = $kirim && ($cfg['cron_ping_id'] ?? '') === $kirim['id'];

        if ($cocok) {
            $latensi = strtotime($cfg['cron_ping_at']) - strtotime($kirim['at']);
            $hasil = [
                'sukses' => true, 'waktu' => now()->toDateTimeString(),
                'ringkasan' => 'AKTIF: ping dieksekusi worker pada ' . $cfg['cron_ping_at'] . ' (selisih ±' . max(0, $latensi) . ' detik dari pengiriman). Cron hosting berjalan benar.',
                'detail' => 'ping_id: ' . $kirim['id'] . "\ndikirim: " . $kirim['at'] . "\ndieksekusi: " . $cfg['cron_ping_at'] . "\nhasil job: " . ($cfg['cron_ping_hasil'] ?? '-'),
            ];

            return redirect()->route('whatsapp.index', ['tab' => 'cron'])->with('ok', 'Cron AKTIF — ping berhasil dieksekusi.')->with('wa_cron_hasil', $hasil);
        }

        $sebab = 'Ping belum dieksekusi worker. Kemungkinan: (1) cron belum dipasang/salah path di panel hosting; (2) siklus cron belum tiba (tunggu ±1 menit lalu Cek lagi); (3) QUEUE_CONNECTION bukan database; (4) job gagal — cek tabel failed_jobs.';
        if (! $kirim) {
            $sebab = 'Belum ada ping yang dikirim dari sesi ini. Klik Kirim Ping Uji dulu.';
        }
        $hasil = [
            'sukses' => false, 'waktu' => now()->toDateTimeString(),
            'ringkasan' => 'BELUM TERDETEKSI: ' . $sebab,
            'detail' => 'ping_terkirim: ' . ($kirim ? $kirim['id'] . ' @ ' . $kirim['at'] : '-') . "\n"
                . 'ping_dieksekusi: ' . ($cfg['cron_ping_id'] ?? '-') . ' @ ' . ($cfg['cron_ping_at'] ?? '-') . "\n"
                . 'worker_terakhir_jalan: ' . ($cfg['cron_last_run'] ?? '-') . "\n"
                . 'jobs_antre: ' . $status['jobs'] . ' | jobs_gagal: ' . $status['gagal'],
        ];

        return redirect()->route('whatsapp.index', ['tab' => 'cron'])->withErrors(['msg' => 'Cron belum terdeteksi aktif.'])->with('wa_cron_hasil', $hasil);
    }

    private function statusCron(Request $request): array
    {
        $cfg = Pengaturan::ambil();
        $data = [
            'queue' => config('queue.default'),
            'jobs' => 0, 'gagal' => 0, 'notif_antre' => 0,
            'last_run' => $cfg['cron_last_run'] ?? null,
            'ping_at' => $cfg['cron_ping_at'] ?? null,
            'ping_id' => $cfg['cron_ping_id'] ?? null,
            'ping_hasil' => $cfg['cron_ping_hasil'] ?? null,
            'kirim' => session('cron_ping'),
            'status' => 'unknown',
        ];
        try {
            $data['jobs'] = DB::table('jobs')->count();
            $data['gagal'] = DB::table('failed_jobs')->count();
            $data['notif_antre'] = DB::table('tb_notifikasi_log')->where('status', 'antre')->count();
        } catch (\Throwable $e) {
        }
        $kirim = $data['kirim'];
        if ($kirim && $data['ping_id'] === $kirim['id']) {
            $data['status'] = 'aktif';
        } elseif ($data['last_run'] && \Carbon\Carbon::parse($data['last_run'])->diffInMinutes(now()) <= 10) {
            $data['status'] = 'mungkin';
        } elseif ($data['last_run']) {
            $data['status'] = 'lama';
        }

        return $data;
    }

    /** Batalkan satu pesan yang belum terkirim. */
    public function batalkan(NotifikasiLog $log)
    {
        if ($log->status !== 'antre') {
            return redirect()->route('whatsapp.index', ['tab' => 'antrean'])
                ->withErrors(['msg' => 'Pesan ini sudah diproses (' . $log->status . '), tidak bisa dibatalkan.']);
        }

        $log->update(['status' => 'batal', 'dibatalkan_at' => now()]);

        return redirect()->route('whatsapp.index', ['tab' => 'antrean'])
            ->with('ok', 'Antrean untuk ' . ($log->siswa->nama ?? $log->nisn) . ' dibatalkan.');
    }

    public function hapusLog(Request $request)
    {
        $jumlah = NotifikasiLog::count();
        NotifikasiLog::query()->delete();

        return redirect()->route('whatsapp.index', ['tab' => 'log'])->with('ok', "Log notifikasi dihapus ({$jumlah} baris).");
    }
}
