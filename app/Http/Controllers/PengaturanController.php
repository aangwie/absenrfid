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
            'masuk_mulai' => '06:30', 'masuk_selesai' => '07:30',
            'terlambat_mulai' => '07:31', 'terlambat_selesai' => '13:29',
            'pulang_mulai' => '13:30', 'pulang_selesai' => '14:30',
            'github_repo' => '', 'github_branch' => 'main', 'logo_sekolah' => '',
        ];
        $p = array_merge($def, Pengaturan::ambil());

        return view('pengaturan.index', ['tab' => $tab, 'p' => $p, 'log' => session('log')]);
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
        ], ['*.date_format' => 'Format jam HH:MM.']);
        foreach ($data as $k => $v) {
            DB::table('tb_pengaturan')->updateOrInsert(['kunci' => $k], ['nilai' => $v, 'updated_at' => now(), 'created_at' => now()]);
        }

        return back()->with('ok', 'Jam kategori tersimpan.');
    }

    public function simpanInstitusi(Request $request)
    {
        $data = $request->validate([
            'nama_institusi' => ['required', 'string', 'max:255'],
            'alamat' => ['required', 'string', 'max:500'],
            'no_hp' => ['required', 'string', 'max:20'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:400'],
        ], ['*.required' => 'Lengkapi Nama Institusi, Alamat, dan Nomor HP.']);
        foreach (['nama_institusi' => $data['nama_institusi'], 'alamat' => $data['alamat'], 'no_hp' => $data['no_hp']] as $k => $v) {
            DB::table('tb_pengaturan')->updateOrInsert(['kunci' => $k], ['nilai' => trim($v), 'updated_at' => now(), 'created_at' => now()]);
        }
        if ($request->boolean('hapus_logo')) {
            DB::table('tb_pengaturan')->where('kunci', 'logo_sekolah')->delete();
        } elseif ($request->hasFile('logo')) {
            $f = $request->file('logo');
            $b64 = 'data:' . $f->getMimeType() . ';base64,' . base64_encode(file_get_contents($f->getRealPath()));
            DB::table('tb_pengaturan')->updateOrInsert(['kunci' => 'logo_sekolah'], ['nilai' => $b64, 'updated_at' => now(), 'created_at' => now()]);
        }

        return redirect()->route('pengaturan.index', ['tab' => 'institusi'])->with('ok', 'Data institusi tersimpan.');
    }

    public function updateApp(Request $request)
    {
        $data = $request->validate([
            'github_repo' => ['required', 'string', 'max:255'],
            'github_branch' => ['nullable', 'string', 'max:100'],
            'github_token' => ['required', 'string', 'max:255'],
        ]);
        $branch = trim($data['github_branch'] ?? '') ?: 'main';
        $repo = trim($data['github_repo']);
        if (preg_match('~github\.com[/:]([^/]+/[^/]+?)(\.git)?/?$~i', $repo, $m)) {
            $repo = $m[1];
        }
        $token = trim($data['github_token']);
        foreach (['github_repo' => trim($repo, '/'), 'github_branch' => $branch] as $k => $v) {
            DB::table('tb_pengaturan')->updateOrInsert(['kunci' => $k], ['nilai' => $v, 'updated_at' => now(), 'created_at' => now()]);
        }

        $mask = fn ($s) => str_replace($token, '***', $s);
        $log = ['$ repo: ' . $repo . ' | branch: ' . $branch];
        $run = function ($cmd) use (&$log, $mask) {
            $log[] = '$ ' . $mask(is_array($cmd) ? implode(' ', $cmd) : $cmd);
            $r = Process::path(base_path())->timeout(120)->run($cmd);
            $out = trim($r->output() . "\n" . $r->errorOutput());
            $log[] = $mask($out === '' ? '(tanpa output)' : $out);
            return $r->successful();
        };

        $isRepo = Process::path(base_path())->run('git rev-parse --is-inside-work-tree')->successful();
        if (! $isRepo) {
            $run(['git', 'init', '-b', $branch]);
        }
        $run(['git', 'remote', 'remove', 'origin']);
        $run(['git', 'remote', 'add', 'origin', "https://x-access-token:{$token}@github.com/{$repo}.git"]);
        $ok = $run(['git', 'fetch', 'origin', $branch, '--depth=1', '--prune']);
        if ($ok) {
            $ok = $run(['git', 'reset', '--hard', "origin/{$branch}"]);
        }
        $run(['git', 'remote', 'set-url', 'origin', "https://github.com/{$repo}.git"]);
        $log[] = $ok ? 'SELESAI: kode diperbarui.' : 'GAGAL: cek repo/branch/token.';

        return redirect()->route('pengaturan.index', ['tab' => 'update'])->with('log', implode("\n", $log));
    }

    public function rawat(Request $request)
    {
        $data = $request->validate(['aksi' => ['required', 'in:clear-cache,migrate,symlink']]);
        $log = ['$ aksi: ' . $data['aksi']];
        try {
            if ($data['aksi'] === 'clear-cache') {
                Artisan::call('optimize:clear');
                $log[] = trim(Artisan::output()) ?: 'Cache dibersihkan.';
            } elseif ($data['aksi'] === 'migrate') {
                Artisan::call('migrate', ['--force' => true]);
                $log[] = trim(Artisan::output()) ?: 'Migrasi selesai.';
            } elseif (! (is_link(public_path('storage')) || file_exists(public_path('storage')))) {
                Artisan::call('storage:link');
                $log[] = trim(Artisan::output()) ?: 'Symlink storage dibuat.';
            } else {
                $log[] = 'Link storage sudah ada, lewati.';
            }
        } catch (\Throwable $e) {
            $log[] = 'GAGAL: ' . $e->getMessage();
        }

        return redirect()->route('pengaturan.index', ['tab' => 'perawatan'])->with('log', implode("\n", $log));
    }

    public function unduhBackup()
    {
        // ponytail: dump via PDO per tabel, tanpa mysqldump. Pindah ke mysqldump bila DB besar.
        try {
            $tables = collect(DB::select('SHOW TABLES'))->map(fn ($r) => array_values((array) $r)[0])->all();
            $sql = "-- backup absenrfid " . now()->toDateTimeString() . "\nSET FOREIGN_KEY_CHECKS=0;\n";
            foreach ($tables as $t) {
                $c = DB::selectOne("SHOW CREATE TABLE `$t`");
                $sql .= "DROP TABLE IF EXISTS `$t`;\n" . array_values((array) $c)[1] . ";\n";
                foreach (DB::table($t)->orderBy(DB::raw('1'))->lazy(500) as $row) {
                    $vals = collect((array) $row)->map(fn ($v) => is_null($v) ? 'NULL' : DB::getPdo()->quote((string) $v))->implode(',');
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

            return redirect()->route('pengaturan.index', ['tab' => 'impor'])->with('ok', 'Import DB selesai: ' . count($stmts) . ' query.');
        } catch (\Throwable $e) {
            try {
                DB::unprepared('SET FOREIGN_KEY_CHECKS=1;');
            } catch (\Throwable $t) {
            }

            return redirect()->route('pengaturan.index', ['tab' => 'impor'])->withErrors(['msg' => 'Import gagal: ' . $e->getMessage()]);
        }
    }
}
