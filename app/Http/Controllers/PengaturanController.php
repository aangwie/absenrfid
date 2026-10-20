<?php

namespace App\Http\Controllers;

use App\Models\Pengaturan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;

class PengaturanController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'institusi');
        if (! in_array($tab, ['institusi', 'waktu', 'update', 'perawatan', 'backup', 'impor'])) {
            $tab = 'institusi';
        }
        $def = [
            'nama_institusi' => '', 'alamat' => '', 'no_hp' => '',
            'nama_kepala_sekolah' => '', 'nip_kepala_sekolah' => '',
            'ttd_kepala_sekolah' => '', 'stempel_sekolah' => '',
            'masuk_mulai' => '06:30', 'masuk_selesai' => '07:30',
            'terlambat_mulai' => '07:31', 'terlambat_selesai' => '13:29',
            'pulang_mulai' => '13:30', 'pulang_selesai' => '14:30',
            'github_repo' => '', 'github_branch' => 'main', 'github_token' => '', 'logo_sekolah' => '',
        ];
        $p = array_merge($def, Pengaturan::ambil());
        $rawat = ['jobs' => 0, 'gagal' => 0, 'notif_antre' => 0];
        if ($tab === 'perawatan') {
            try {
                $rawat['jobs'] = DB::table('jobs')->count();
                $rawat['gagal'] = DB::table('failed_jobs')->count();
                $rawat['notif_antre'] = DB::table('tb_notifikasi_log')->where('status', 'antre')->count();
            } catch (\Throwable $e) {
                // tabel antrean belum ada: tampilkan 0
            }
            $rawat['symlink'] = $this->statusSymlink();
        }

        return view('pengaturan.index', ['tab' => $tab, 'p' => $p, 'log' => session('log'), 'rawat' => $rawat]);
    }

    public function simpanInstitusi(Request $request)
    {
        $data = $request->validate([
            'nama_institusi' => ['required', 'string', 'max:255'],
            'alamat' => ['required', 'string', 'max:500'],
            'no_hp' => ['required', 'string', 'max:20'],
            'nama_kepala_sekolah' => ['nullable', 'string', 'max:100'],
            'nip_kepala_sekolah' => ['nullable', 'string', 'max:30'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:400'],
            'ttd' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:400'],
            'stempel' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:400'],
        ], ['*.required' => 'Lengkapi Nama Institusi, Alamat, dan Nomor HP.']);

        foreach (['nama_institusi' => $data['nama_institusi'], 'alamat' => $data['alamat'], 'no_hp' => $data['no_hp'], 'nama_kepala_sekolah' => trim((string) ($data['nama_kepala_sekolah'] ?? '')), 'nip_kepala_sekolah' => trim((string) ($data['nip_kepala_sekolah'] ?? ''))] as $k => $v) {
            DB::table('tb_pengaturan')->updateOrInsert(['kunci' => $k], ['nilai' => trim($v), 'updated_at' => now(), 'created_at' => now()]);
        }
        foreach ([['logo', 'logo_sekolah', 'hapus_logo'], ['ttd', 'ttd_kepala_sekolah', 'hapus_ttd'], ['stempel', 'stempel_sekolah', 'hapus_stempel']] as [$input, $kunci, $hapus]) {
            if ($request->boolean($hapus)) {
                DB::table('tb_pengaturan')->where('kunci', $kunci)->delete();
            } elseif ($request->hasFile($input)) {
                $f = $request->file($input);
                $b64 = 'data:' . $f->getMimeType() . ';base64,' . base64_encode(file_get_contents($f->getRealPath()));
                DB::table('tb_pengaturan')->updateOrInsert(['kunci' => $kunci], ['nilai' => $b64, 'updated_at' => now(), 'created_at' => now()]);
            }
        }

        Pengaturan::lupakan();

        return redirect()->route('pengaturan.index', ['tab' => 'institusi'])->with('ok', 'Data institusi tersimpan.');
    }

    public function simpanWaktu(Request $request)
    {
        $data = $request->validate([
            'masuk_mulai' => ['required', 'date_format:H:i'],
            'masuk_selesai' => ['required', 'date_format:H:i'],
            'terlambat_mulai' => ['required', 'date_format:H:i'],
            'terlambat_selesai' => ['required', 'date_format:H:i'],
            'pulang_mulai' => ['required', 'date_format:H:i'],
            'pulang_selesai' => ['required', 'date_format:H:i'],
        ], ['*.required' => 'Format jam HH:MM.', '*.date_format' => 'Format jam HH:MM.']);

        foreach ($data as $k => $v) {
            DB::table('tb_pengaturan')->updateOrInsert(['kunci' => $k], ['nilai' => $v, 'updated_at' => now(), 'created_at' => now()]);
        }

        Pengaturan::lupakan();

        return redirect()->route('pengaturan.index', ['tab' => 'waktu'])->with('ok', 'Waktu absensi tersimpan.');
    }

    public function simpanUpdateConfig(Request $request)
    {
        $data = $request->validate([
            'github_repo' => ['required', 'string', 'max:255'],
            'github_branch' => ['nullable', 'string', 'max:100'],
            'github_token' => ['required', 'string', 'max:255'],
        ]);

        $branch = trim($data['github_branch']) ?: 'main';
        $repo = trim($data['github_repo']);
        if (preg_match('~github\.com[/:]([^/]+/[^/]+?)(\.git)?/?$~i', $repo, $m)) {
            $repo = $m[1];
        }

        foreach (['github_repo' => $repo, 'github_branch' => $branch, 'github_token' => trim($data['github_token'])] as $k => $v) {
            DB::table('tb_pengaturan')->updateOrInsert(['kunci' => $k], ['nilai' => $v, 'updated_at' => now(), 'created_at' => now()]);
        }

        Pengaturan::lupakan();

        return redirect()->route('pengaturan.index', ['tab' => 'update'])->with('ok', 'Konfigurasi update tersimpan.');
    }

    public function updateApp(Request $request)
    {
        $p = Pengaturan::ambil();
        $repo = $p['github_repo'] ?? '';
        $branch = $p['github_branch'] ?? 'main';
        $token = $p['github_token'] ?? '';

        if (! $repo || ! $token) {
            return redirect()->route('pengaturan.index', ['tab' => 'update'])->withErrors(['msg' => 'Konfigurasi GitHub (repo/token) belum disimpan.']);
        }

        $log = [];
        try {
            $base = base_path();
            $publicUrl = "https://github.com/{$repo}.git";
            $authedUrl = "https://x-access-token:{$token}@github.com/{$repo}.git";
            $jalan = fn (array $cmd, int $batas = 60) => Process::path($base)->timeout($batas)->run($cmd);
            $tampil = function ($hasil) {
                $out = trim(($hasil->output() ?? '') . "\n" . ($hasil->errorOutput() ?? ''));
                return $out === '' ? '(tidak ada output, exit ' . $hasil->exitCode() . ')' : $out . ' [exit ' . $hasil->exitCode() . ']';
            };

            $log[] = '$ git remote set-url origin ' . $publicUrl . ' (pakai token, disembunyikan)';
            $jalan(['git', 'remote', 'set-url', 'origin', $authedUrl], 30);

            $log[] = '$ git fetch origin';
            $fetch = $jalan(['git', 'fetch', 'origin'], 120);
            $log[] = $tampil($fetch);

            $log[] = '$ git reset --hard origin/' . $branch;
            $reset = $jalan(['git', 'reset', '--hard', 'origin/' . $branch], 120);
            $log[] = $tampil($reset);

            $log[] = '$ git remote set-url origin ' . $publicUrl . ' (bersihkan token)';
            $jalan(['git', 'remote', 'set-url', 'origin', $publicUrl], 30);

            $ok = $fetch->successful() && $reset->successful();
            if ($ok) {
                Artisan::call('optimize:clear');
                $log[] = trim(Artisan::output()) ?: 'Cache dibersihkan.';
                $log[] = 'SELESAI: kode diperbarui.';
            } else {
                $log[] = 'GAGAL: cek repo/branch/token.';
            }
        } catch (\Throwable $e) {
            $log[] = 'GAGAL: ' . $e->getMessage();
        }

        return redirect()->route('pengaturan.index', ['tab' => 'update'])->with('log', implode("\n", $log));
    }

    public function rawat(Request $request)
    {
        $data = $request->validate(['aksi' => ['required', 'in:clear-cache,migrate,symlink,queue-work']]);
        $log = ['$ aksi: ' . $data['aksi']];
        try {
            if ($data['aksi'] === 'clear-cache') {
                Artisan::call('optimize:clear');
                $log[] = trim(Artisan::output()) ?: 'Cache dibersihkan.';
            } elseif ($data['aksi'] === 'migrate') {
                Artisan::call('migrate', ['--force' => true]);
                $log[] = trim(Artisan::output()) ?: 'Migrasi selesai.';
            } elseif ($data['aksi'] === 'queue-work') {
                if (function_exists('set_time_limit')) {
                    @set_time_limit(120);
                }
                $sebelum = DB::table('jobs')->count();
                $log[] = "Antrean jobs sebelum: {$sebelum}. Menjalankan queue:work (berhenti saat kosong, maks 55 dtk)...";
                Pengaturan::lupakan();
                Artisan::call('queue:work', ['--stop-when-empty' => true, '--sleep' => 1, '--tries' => 3, '--max-time' => 55]);
                Pengaturan::lupakan();
                $out = trim(Artisan::output());
                if ($out !== '') {
                    $log[] = $out;
                }
                $sesudah = DB::table('jobs')->count();
                $log[] = "Antrean jobs sesudah: {$sesudah}.";
                $log[] = $sesudah < $sebelum
                    ? 'SELESAI: antrean diproses.'
                    : 'SELESAI: tidak ada job jatuh tempo. Job tertunda (delay) diproses saat waktunya tiba atau oleh worker kontinu: php artisan queue:work.';
            } elseif ($data['aksi'] === 'symlink') {
                $log[] = $this->buatSymlink();
            }
        } catch (\Throwable $e) {
            $log[] = 'GAGAL: ' . $e->getMessage();
        }

        return redirect()->route('pengaturan.index', ['tab' => 'perawatan'])->with('log', implode("\n", $log));
    }

    /**
     * Status symlink public/storage -> storage/app/public (dipakai foto siswa).
     */
    private function statusSymlink(): array
    {
        $link = public_path('storage');
        $target = storage_path('app/public');
        clearstatcache(true, $link);
        $isLink = is_link($link);
        $exists = $isLink || file_exists($link);
        $real = $exists ? realpath($link) : false;
        $ok = $real !== false && $real === realpath($target);
        $current = $isLink ? (readlink($link) ?: null) : ($ok ? $target : null);

        return [
            'link' => $link,
            'target' => $target,
            'exists' => $exists,
            'is_link' => $isLink,
            'current' => $current,
            'ok' => $ok,
        ];
    }

    /**
     * Buat / buat ulang symlink. Link lama (termasuk yang salah target)
     * dihapus dulu; folder nyata tidak dihapus agar data aman.
     */
    private function buatSymlink(): string
    {
        $link = public_path('storage');
        $target = storage_path('app/public');

        if (! is_dir($target) && ! @mkdir($target, 0755, true)) {
            return 'GAGAL: folder storage/app/public tidak bisa dibuat.';
        }

        if ($this->statusSymlink()['ok']) {
            return 'SELESAI: symlink/junction public/storage → storage/app/public sudah aktif.';
        }

        clearstatcache(true, $link);
        if (is_link($link)) {
            if (! @unlink($link)) {
                return 'GAGAL: symlink lama tidak bisa dihapus (periksa izin folder public).';
            }
        } elseif (file_exists($link)) {
            // Junction Windows yang salah target bisa dihapus dengan rmdir tanpa menghapus isinya.
            if (DIRECTORY_SEPARATOR === '\\' && is_dir($link)) {
                @rmdir($link);
            }
            if (file_exists($link)) {
                return 'GAGAL: public/storage sudah ada sebagai folder/dokumen nyata. Rename atau hapus manual dulu, lalu klik lagi.';
            }
        }

        if (! @symlink($target, $link)) {
            // Windows tanpa hak admin: symlink() ditolak, pakai junction (mklink /J).
            if (DIRECTORY_SEPARATOR === '\\') {
                try {
                    Process::timeout(30)->run(['cmd', '/c', 'mklink', '/J', $link, $target]);
                } catch (\Throwable $e) {
                    // lanjut ke pesan gagal di bawah
                }
                clearstatcache(true, $link);
                if (is_dir($link) && realpath($link) === realpath($target)) {
                    return 'SELESAI: junction public/storage → storage/app/public dibuat. Foto siswa di storage/app/public/foto kini bisa dibuka dari browser.';
                }
            }
            $err = error_get_last()['message'] ?? 'symlink() ditolak.';

            return 'GAGAL: ' . $err . ' Hosting mungkin menonaktifkan symlink(); aktifkan fungsi symlink atau arahkan document root ke folder public.';
        }

        return 'SELESAI: symlink public/storage → storage/app/public dibuat. Foto siswa di storage/app/public/foto kini bisa dibuka dari browser.';
    }

    public function unduhBackup()
    {
        try {
            $tables = collect(DB::select('SHOW TABLES'))->map(fn ($r) => array_values((array) $r)[0])->all();
            $sql = '-- backup absenrfid ' . now()->toDateTimeString() . "\nSET FOREIGN_KEY_CHECKS=0;\n";
            foreach ($tables as $t) {
                $create = DB::selectOne("SHOW CREATE TABLE `$t`");
                $sql .= "DROP TABLE IF EXISTS `$t`;\n" . $create->{'Create Table'} . ";\n\n";
                foreach (DB::table($t)->orderBy(DB::raw('1'))->lazy(500) as $row) {
                    $vals = collect($row)->map(fn ($v) => is_null($v) ? 'NULL' : DB::getPdo()->quote((string) $v))->implode(',');
                    $sql .= "INSERT INTO `$t` VALUES ($vals);\n";
                }
            }
            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
            $nama = 'backup-absenrfid-' . now()->format('Ymd-His') . '.sql';

            return response($sql, 200, ['Content-Type' => 'application/sql', 'Content-Disposition' => 'attachment; filename="' . $nama . '"']);
        } catch (\Throwable $e) {
            return redirect()->route('pengaturan.index', ['tab' => 'backup'])->withErrors(['msg' => 'Backup gagal: ' . $e->getMessage()]);
        }
    }

    public function imporDb(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:sql,txt', 'max:10240']], ['file.required' => 'Pilih file .sql dulu.', 'file.mimes' => 'File harus .sql.']);
        try {
            $isi = file_get_contents($request->file('file')->getRealPath());
            $isi = preg_replace('/^\xEF\xBB\xBF/', '', $isi);
            $stmts = array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', $isi)));
            DB::unprepared('SET FOREIGN_KEY_CHECKS=0;');
            foreach (array_chunk($stmts, 200) as $batch) {
                DB::transaction(fn () => array_map(fn ($s) => DB::unprepared($s), $batch));
            }
            DB::unprepared('SET FOREIGN_KEY_CHECKS=1;');
            Pengaturan::lupakan();

            return redirect()->route('pengaturan.index', ['tab' => 'backup'])->with('ok', 'Import database selesai.');
        } catch (\Throwable $e) {
            DB::unprepared('SET FOREIGN_KEY_CHECKS=1;');
            return redirect()->route('pengaturan.index', ['tab' => 'backup'])->withErrors(['msg' => 'Import gagal: ' . $e->getMessage()]);
        }
    }
}
