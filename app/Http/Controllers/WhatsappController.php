<?php

namespace App\Http\Controllers;

use App\Jobs\CronPing;
use App\Jobs\KirimNotifikasiAbsensi;
use App\Models\NotifikasiLog;
use App\Models\Pengaturan;
use App\Models\Siswa;
use App\Models\WhatsappTemplate;
use App\Support\AntreanWhatsapp;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WhatsappController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'konfigurasi');
        if (! in_array($tab, ['konfigurasi', 'antrean', 'uji', 'template', 'log', 'cron'], true)) {
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
            'aktif' => AntreanWhatsapp::aktifLabel(),
        ] : null;

        $templates = in_array($tab, ['template', 'uji'], true)
            ? WhatsappTemplate::orderBy('id')->get()
            : collect();

        // Daftar siswa yang punya nomor HP ortu/wali, untuk multi select di
        // tab Uji Kirim. Pencarian dilakukan di browser.
        $siswaWa = $tab === 'uji'
            ? Siswa::with('kelas')
                ->whereNotNull('no_hp_ortu')
                ->where('no_hp_ortu', '!=', '')
                ->orderBy('nama')
                ->get(['nisn', 'nama', 'kelas_id', 'no_hp_ortu'])
            : collect();

        return view('whatsapp.index', compact('tab', 'cfg', 'logs', 'cron', 'antrean', 'jadwal', 'jumlahAntre', 'templates', 'siswaWa'));
    }

    public function simpan(Request $request)
    {
        $data = $request->validate([
            'wa_url' => ['required', 'url', 'max:500'],
            'wa_api_key' => ['required', 'string', 'max:500'],
            'wa_number' => ['required', 'string', 'max:30'],
            'wa_batch_menit' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'wa_batch_jumlah' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'wa_kirim_tepat_waktu' => ['nullable', 'boolean'],
            'wa_kirim_terlambat' => ['nullable', 'boolean'],
            'wa_kirim_pulang' => ['nullable', 'boolean'],
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

        // Checkbox dikirim bersama hidden input bernilai 0, jadi field selalu
        // ada. Bila benar-benar tidak ada (form lama), pertahankan nilai lama.
        foreach (array_keys(AntreanWhatsapp::KATEGORI) as $kategori) {
            $kunci = AntreanWhatsapp::kunciKategori($kategori);
            $nilai[$kunci] = $request->has($kunci)
                ? ($request->boolean($kunci) ? '1' : '0')
                : (Pengaturan::nilai($kunci, '1') === '0' ? '0' : '1');
        }

        foreach ($nilai as $k => $v) {
            DB::table('tb_pengaturan')->updateOrInsert(['kunci' => $k], ['nilai' => $v, 'updated_at' => now(), 'created_at' => now()]);
        }

        Pengaturan::lupakan();

        // Antrean milik status yang baru dinonaktifkan langsung dibatalkan,
        // supaya tidak menunggu worker dan tidak tampil sebagai "Antre".
        $dibatalkan = 0;
        foreach (array_keys(AntreanWhatsapp::KATEGORI) as $kategori) {
            if ($nilai[AntreanWhatsapp::kunciKategori($kategori)] === '0') {
                $dibatalkan += AntreanWhatsapp::batalkanKategori($kategori);
            }
        }

        $perMenit = max(1, (int) round(AntreanWhatsapp::jumlahPesan() / AntreanWhatsapp::durasiMenit()));
        $aktif = AntreanWhatsapp::aktifLabel();

        $pesan = 'Konfigurasi WhatsApp tersimpan. Jadwal kirim: ±'.$perMenit.' pesan/menit (1 pesan tiap '.AntreanWhatsapp::jedaDetik().' detik). '
            .'Status dikirim: '.($aktif ? implode(', ', $aktif) : 'tidak ada').'.';
        if ($dibatalkan > 0) {
            $pesan .= " {$dibatalkan} antrean dibatalkan karena statusnya dinonaktifkan.";
        }

        return redirect()->route('whatsapp.index', ['tab' => 'konfigurasi'])->with('ok', $pesan);
    }

    public function uji(Request $request)
    {
        $data = $request->validate([
            'mode' => ['required', Rule::in(['nomor', 'siswa'])],
            'tujuan' => ['nullable', 'string', 'max:30', 'required_if:mode,nomor'],
            'siswa' => ['nullable', 'array', 'max:50', 'required_if:mode,siswa'],
            'siswa.*' => ['string', 'exists:tb_siswa,nisn'],
            'pesan' => ['required', 'string', 'max:2000'],
        ], [
            'mode.required' => 'Pilih mode tujuan pengiriman.',
            'mode.in' => 'Mode tujuan tidak valid.',
            'tujuan.required_if' => 'Nomor tujuan wajib diisi.',
            'siswa.required_if' => 'Pilih minimal satu siswa.',
            'siswa.array' => 'Pilihan siswa tidak valid.',
            'siswa.max' => 'Maksimal 50 siswa untuk sekali uji kirim.',
            'siswa.*.exists' => 'Siswa yang dipilih tidak ditemukan.',
            'pesan.required' => 'Pesan wajib diisi.',
            'pesan.max' => 'Pesan maksimal 2000 karakter.',
        ]);

        $mode = $data['mode'] ?? 'nomor';

        // Kumpulkan tujuan: satu nomor manual, atau nomor HP ortu/wali milik
        // siswa terpilih (bisa banyak).
        $targets = [];
        if ($mode === 'siswa') {
            $siswas = Siswa::with('kelas')->whereIn('nisn', $data['siswa'] ?? [])->get();
            foreach ($siswas as $siswa) {
                $targets[] = [
                    'nama' => $siswa->nama,
                    'nomor' => KirimNotifikasiAbsensi::normalisasiNomor($siswa->no_hp_ortu),
                    'asli' => trim((string) $siswa->no_hp_ortu),
                    'siswa' => $siswa,
                ];
            }
        } else {
            $targets[] = [
                'nama' => null,
                'nomor' => KirimNotifikasiAbsensi::normalisasiNomor($data['tujuan'] ?? ''),
                'asli' => trim((string) ($data['tujuan'] ?? '')),
                'siswa' => null,
            ];
        }

        if ($targets === []) {
            return redirect()->route('whatsapp.index', ['tab' => 'uji'])->withInput()
                ->withErrors(['msg' => 'Tidak ada siswa valid yang dipilih.']);
        }

        $cfg = Pengaturan::ambil();
        $url = trim($cfg['wa_url'] ?? '');
        $apiKey = trim($cfg['wa_api_key'] ?? '');
        $sender = trim($cfg['wa_number'] ?? '');

        $daftar = [];
        $berhasil = 0;
        $gagal = 0;

        foreach ($targets as $target) {
            $item = [
                'nama' => $target['nama'],
                'tujuan' => $target['nomor'] !== '' ? $target['nomor'] : ($target['asli'] !== '' ? $target['asli'] : '-'),
                'http' => null,
                'sukses' => false,
                'ringkasan' => '',
                'detail' => '',
            ];

            if ($url === '' || $apiKey === '' || $sender === '') {
                $item['ringkasan'] = 'Konfigurasi WhatsApp belum lengkap. Isi URL, API Key, dan Number di tab Konfigurasi.';
                $item['detail'] = 'wa_url / wa_api_key / wa_number kosong.';
            } elseif ($target['nomor'] === '') {
                $item['ringkasan'] = 'Nomor HP kosong atau tidak valid.';
                $item['detail'] = 'Nomor asli: '.($target['asli'] !== '' ? $target['asli'] : '(kosong)');
            } else {
                $pesan = (string) $data['pesan'];
                if ($target['siswa']) {
                    // Placeholder diganti otomatis bila tujuan berasal dari
                    // data siswa (nama, nisn, kelas, tanggal, jam, dst).
                    $pesan = (new WhatsappTemplate(['isi' => $pesan]))->render($target['siswa'], [
                        'tanggal' => now()->toDateString(),
                        'jam' => now()->format('H:i'),
                        'status' => 'Uji Kirim',
                        'tipe' => 'UJI',
                    ]);
                }

                $item = array_merge($item, $this->kirimSatuPesan($url, $apiKey, $sender, $target['nomor'], $pesan));
            }

            if ($item['sukses']) {
                $berhasil++;
            } else {
                $gagal++;
            }
            $daftar[] = $item;
        }

        $total = count($daftar);
        $ringkasan = $gagal === 0
            ? ($berhasil === 1 ? 'Pesan terkirim.' : "{$berhasil} pesan terkirim.")
            : "{$berhasil} berhasil, {$gagal} gagal dari {$total} tujuan.";

        $hasil = [
            'waktu' => now()->toDateTimeString(),
            'total' => $total,
            'berhasil' => $berhasil,
            'gagal' => $gagal,
            'sukses' => $gagal === 0,
            'ringkasan' => $ringkasan,
            'daftar' => $daftar,
        ];

        $redirect = redirect()->route('whatsapp.index', ['tab' => 'uji'])->withInput()->with('wa_hasil', $hasil);

        if ($gagal === 0) {
            $teks = $berhasil === 1 ? 'Pesan uji terkirim.' : "{$berhasil} pesan uji terkirim.";

            return $redirect->with('ok', $teks);
        }

        return $redirect->withErrors(['msg' => $gagal === $total
            ? 'Semua pesan gagal dikirim. Periksa detail respons di bawah.'
            : "{$gagal} dari {$total} pesan gagal dikirim. Periksa detail respons di bawah."]);
    }

    /** Kirim satu pesan ke API WhatsApp; hasilnya siap ditampilkan di UI. */
    private function kirimSatuPesan(string $url, string $apiKey, string $sender, string $tujuan, string $pesan): array
    {
        try {
            $res = Http::withHeaders(['Content-Type' => 'application/x-www-form-urlencoded'])
                ->asForm()
                ->timeout(15)
                ->post($url, [
                    'nomor_pengirim' => $sender,
                    'api_key' => $apiKey,
                    'nomor_penerima' => $tujuan,
                    'pesan' => $pesan,
                ]);

            $body = (string) $res->body();
            $detail = $body;
            $decoded = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $detail = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
            if (strlen($detail) > 1200) {
                $detail = substr($detail, 0, 1200).'... (dipotong)';
            }

            if ($res->successful()) {
                $ket = 'Berhasil: API merespons HTTP '.$res->status().'. Cek detail respons & pastikan pesan masuk di nomor tujuan.';
                if (is_array($decoded)) {
                    foreach (['message', 'msg', 'status', 'result', 'success'] as $k) {
                        if (isset($decoded[$k]) && (is_string($decoded[$k]) || is_numeric($decoded[$k]) || is_bool($decoded[$k]))) {
                            $ket .= ' | '.$k.': '.(is_bool($decoded[$k]) ? ($decoded[$k] ? 'true' : 'false') : $decoded[$k]);
                        }
                    }
                }

                return ['sukses' => true, 'http' => $res->status(), 'ringkasan' => $ket, 'detail' => $detail];
            }

            return [
                'sukses' => false,
                'http' => $res->status(),
                'ringkasan' => 'Gagal: API merespons HTTP '.$res->status().' (bukan 2xx). Umumnya API Key / sender / format nomor salah, atau kuota habis.',
                'detail' => $detail,
            ];
        } catch (\Throwable $e) {
            return [
                'sukses' => false,
                'http' => null,
                'ringkasan' => 'Gagal koneksi ke API: '.$e->getMessage(),
                'detail' => $e->getMessage(),
            ];
        }
    }

    public function simpanTemplate(Request $request)
    {
        $data = $this->validasiTemplate($request);
        $data['aktif'] = $request->boolean('aktif');

        if ($data['aktif']) {
            WhatsappTemplate::where('kategori', $data['kategori'])->update(['aktif' => false]);
        }

        WhatsappTemplate::create($data);

        return redirect()->route('whatsapp.index', ['tab' => 'template'])
            ->with('ok', 'Template "'.$data['nama'].'" ditambahkan.');
    }

    public function perbaruiTemplate(Request $request, WhatsappTemplate $template)
    {
        $data = $this->validasiTemplate($request);
        $data['aktif'] = $request->boolean('aktif');

        if ($data['aktif']) {
            WhatsappTemplate::where('kategori', $data['kategori'])
                ->where('id', '!=', $template->id)
                ->update(['aktif' => false]);
        }

        $template->update($data);

        return redirect()->route('whatsapp.index', ['tab' => 'template'])
            ->with('ok', 'Template "'.$template->nama.'" diperbarui.');
    }

    public function hapusTemplate(WhatsappTemplate $template)
    {
        $nama = $template->nama;
        $template->delete();

        return redirect()->route('whatsapp.index', ['tab' => 'template'])
            ->with('ok', 'Template "'.$nama.'" dihapus.');
    }

    private function validasiTemplate(Request $request): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'kategori' => ['required', Rule::in(array_keys(WhatsappTemplate::KATEGORI))],
            'isi' => ['required', 'string', 'max:4000'],
            'aktif' => ['nullable', 'boolean'],
        ], [
            'nama.required' => 'Nama template wajib diisi.',
            'nama.max' => 'Nama template maksimal 100 karakter.',
            'kategori.required' => 'Kategori wajib dipilih.',
            'kategori.in' => 'Kategori tidak valid.',
            'isi.required' => 'Isi template wajib diisi.',
            'isi.max' => 'Isi template maksimal 4000 karakter.',
        ]);
    }

    /**
     * Tes cron hosting: antrekan satu job ping. Ping hanya dieksekusi bila
     * ada worker (cron / queue:work manual / worker kontinu) yang memproses
     * antrean. Hasilnya dibaca lewat cek().
     */
    public function ping(Request $request)
    {
        $id = 'ping-'.now()->format('YmdHis').'-'.Str::lower(Str::random(4));
        CronPing::dispatch($id);
        session(['cron_ping' => ['id' => $id, 'at' => now()->toDateTimeString()]]);

        return redirect()->route('whatsapp.index', ['tab' => 'cron'])
            ->with('ok', 'Ping uji dikirim ke antrean ('.$id.'). Tunggu ±1 menit (satu siklus cron), JANGAN klik Jalankan Antrean dulu, lalu klik Cek Hasil.');
    }

    public function cek(Request $request)
    {
        $kirim = session('cron_ping');
        $cfg = Pengaturan::ambil();
        $status = $this->statusCron($request);
        $cocok = $kirim && ($cfg['cron_ping_id'] ?? '') === $kirim['id'];

        if ($cocok) {
            $latensi = strtotime($cfg['cron_ping_at']) - strtotime($kirim['at']);
            $hasil = [
                'sukses' => true, 'waktu' => now()->toDateTimeString(),
                'ringkasan' => 'AKTIF: ping dieksekusi worker pada '.$cfg['cron_ping_at'].' (selisih ±'.max(0, $latensi).' detik dari pengiriman). Cron hosting berjalan benar.',
                'detail' => 'ping_id: '.$kirim['id']."\ndikirim: ".$kirim['at']."\ndieksekusi: ".$cfg['cron_ping_at']."\nhasil job: ".($cfg['cron_ping_hasil'] ?? '-'),
            ];

            return redirect()->route('whatsapp.index', ['tab' => 'cron'])->with('ok', 'Cron AKTIF — ping berhasil dieksekusi.')->with('wa_cron_hasil', $hasil);
        }

        $sebab = 'Ping belum dieksekusi worker. Kemungkinan: (1) cron belum dipasang/salah path di panel hosting; (2) siklus cron belum tiba (tunggu ±1 menit lalu Cek lagi); (3) QUEUE_CONNECTION bukan database; (4) job gagal — cek tabel failed_jobs.';
        if (! $kirim) {
            $sebab = 'Belum ada ping yang dikirim dari sesi ini. Klik Kirim Ping Uji dulu.';
        }
        $hasil = [
            'sukses' => false, 'waktu' => now()->toDateTimeString(),
            'ringkasan' => 'BELUM TERDETEKSI: '.$sebab,
            'detail' => 'ping_terkirim: '.($kirim ? $kirim['id'].' @ '.$kirim['at'] : '-')."\n"
                .'ping_dieksekusi: '.($cfg['cron_ping_id'] ?? '-').' @ '.($cfg['cron_ping_at'] ?? '-')."\n"
                .'worker_terakhir_jalan: '.($cfg['cron_last_run'] ?? '-')."\n"
                .'jobs_antre: '.$status['jobs'].' | jobs_gagal: '.$status['gagal'],
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
        } elseif ($data['last_run'] && Carbon::parse($data['last_run'])->diffInMinutes(now()) <= 10) {
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
                ->withErrors(['msg' => 'Pesan ini sudah diproses ('.$log->status.'), tidak bisa dibatalkan.']);
        }

        $log->update(['status' => 'batal', 'dibatalkan_at' => now()]);

        return redirect()->route('whatsapp.index', ['tab' => 'antrean'])
            ->with('ok', 'Antrean untuk '.($log->siswa->nama ?? $log->nisn).' dibatalkan.');
    }

    public function hapusLog(Request $request)
    {
        $jumlah = NotifikasiLog::count();
        NotifikasiLog::query()->delete();

        return redirect()->route('whatsapp.index', ['tab' => 'log'])->with('ok', "Log notifikasi dihapus ({$jumlah} baris).");
    }
}
