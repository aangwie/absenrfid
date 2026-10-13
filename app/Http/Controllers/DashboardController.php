<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Siswa;
use App\Support\WaktuAbsensi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function tap(Request $request)
    {
        $tag = trim((string) ($request->input('tag', $request->input('rfid_tag', ''))));
        if ($tag === '') {
            return $request->wantsJson()
                ? response()->json(['ok' => false, 'pesan' => 'Tempel kartu / ketik NISN dulu.'], 422)
                : back()->with('tap', ['ok' => false, 'pesan' => 'Tempel kartu / ketik NISN dulu.']);
        }
        $siswa = Siswa::where('rfid_tag', $tag)->orWhere('nisn', $tag)->first();
        if (! $siswa) {
            return $request->wantsJson()
                ? response()->json(['ok' => false, 'pesan' => "Kartu [$tag] tidak terdaftar!"], 404)
                : back()->with('tap', ['ok' => false, 'pesan' => "Kartu [$tag] tidak terdaftar!"]);
        }
        $today = now()->toDateString();
        $jam = now()->format('H:i:s');
        $abs = Absensi::firstOrNew(['nisn' => $siswa->nisn, 'tanggal' => $today]);
        $kat = WaktuAbsensi::kategori($jam);
        if ($kat === 'Tanpa Keterangan') {
            $out = ['ok' => false, 'pesan' => 'Di luar jam absensi ('.$jam.'), status Tanpa Keterangan.'];
            return $request->wantsJson() ? response()->json($out, 422) : back()->with('tap', $out);
        }
        if (! $abs->exists || ! $abs->jam_masuk) {
            // Tap pulang lebih awal (di rentang pulang tapi belum ada masuk): tolak agar urutan Masuk->Pulang terjaga.
            if ($kat === 'Pulang') {
                $out = ['ok' => false, 'pesan' => $siswa->nama.' belum ada jam masuk, tap pulang ditolak.'];
                return $request->wantsJson() ? response()->json($out, 422) : back()->with('tap', $out);
            }
            $abs->jam_masuk = $jam;
            $abs->status_masuk = $kat === 'Terlambat' ? 'Terlambat' : 'Tepat Waktu';
            $abs->status_pulang = 'Belum';
            $abs->save();
            $out = ['ok' => true, 'type' => 'MASUK', 'nama' => $siswa->nama, 'jam' => $jam, 'status' => $abs->status_masuk];
        } elseif (! $abs->jam_pulang) {
            if ($kat !== 'Pulang') {
                $out = ['ok' => false, 'pesan' => 'Di luar jam pulang ('.$jam.'), tap pulang ditolak.'];
                return $request->wantsJson() ? response()->json($out, 422) : back()->with('tap', $out);
            }
            $abs->jam_pulang = $jam;
            $abs->status_pulang = 'Selesai';
            $abs->save();
            $out = ['ok' => true, 'type' => 'PULANG', 'nama' => $siswa->nama, 'jam' => $jam, 'status' => 'Selesai'];
        } else {
            $out = ['ok' => false, 'pesan' => $siswa->nama.' sudah tap masuk & pulang.'];
        }

        return $request->wantsJson() ? response()->json($out) : back()->with('tap', $out);
    }

    public function rekap()
    {
        $today = now()->toDateString();
        $rows = Siswa::leftJoin('tb_absensi as a', function ($join) use ($today) {
                $join->on('a.nisn', '=', 'tb_siswa.nisn')->whereDate('a.tanggal', $today);
            })
            ->leftJoin('tb_kelas as k', 'k.id', '=', 'tb_siswa.kelas_id')
            ->select('tb_siswa.nama', 'tb_siswa.nisn', 'tb_siswa.rfid_tag', 'tb_siswa.gender', 'k.nama as kelas_nama', 'a.jam_masuk', 'a.jam_pulang', 'a.status_masuk', 'a.status_pulang')
            ->orderBy('k.nama')->orderBy('tb_siswa.nama')->get();
        $total = Siswa::count();
        $masuk = $rows->whereNotNull('jam_masuk')->count();

        return response()->json([
            'total' => $total,
            'masuk' => $masuk,
            'pulang' => $rows->where('status_pulang', 'Selesai')->count(),
            'terlambat' => $rows->where('status_masuk', 'Terlambat')->count(),
            'belum' => $rows->whereNull('status_masuk')->count(),
            'rows' => $rows,
            'terakhir' => Absensi::with('siswa')->whereDate('tanggal', $today)->orderByDesc('updated_at')->limit(8)->get()->map(fn ($t) => ['nama' => $t->siswa->nama ?? $t->nisn, 'jam' => $t->jam_pulang ?? $t->jam_masuk, 'pulang' => (bool) $t->jam_pulang]),
        ]);
    }

    public function index()
    {
        \App\Support\WaktuAbsensi::tandaiTanpaKeterangan();
        $today = now()->toDateString();
        $rentang = \App\Support\WaktuAbsensi::rentang();
        $boleh = \App\Support\WaktuAbsensi::boleh(now()->format('H:i:s'));

        $totalSiswa = Siswa::count();

        // ponytail: query per-siswa tanpa N+1 berat OK untuk skala sekolah (<2rb). Upgrade ke join + paginate bila >10rb.
        $rekap = Siswa::leftJoin('tb_absensi as a', function ($join) use ($today) {
                $join->on('a.nisn', '=', 'tb_siswa.nisn')->whereDate('a.tanggal', $today);
            })
            ->leftJoin('tb_kelas as k', 'k.id', '=', 'tb_siswa.kelas_id')
            ->select('tb_siswa.*', 'k.nama as kelas_nama', 'a.jam_masuk', 'a.jam_pulang', 'a.status_masuk', 'a.status_pulang')
            ->orderBy('k.nama')
            ->orderBy('tb_siswa.nama')
            ->get();

        $masukHariIni = Absensi::whereDate('tanggal', $today)->whereNotNull('jam_masuk')->count();
        $pulangHariIni = Absensi::whereDate('tanggal', $today)->where('status_pulang', 'Selesai')->count();
        $terlambatHariIni = Absensi::whereDate('tanggal', $today)->where('status_masuk', 'Terlambat')->count();
        // Belum = benar-benar tanpa baris absensi (Izin/Sakit/Tanpa/Di Sekolah manual tak dihitung belum).
        $belumAbsen = $rekap->whereNull('status_masuk')->count();

        $perKelas = DB::table('tb_kelas as k')
            ->leftJoin('tb_siswa as s', 's.kelas_id', '=', 'k.id')
            ->select('k.nama as kelas', DB::raw('COUNT(s.id) as total'))
            ->groupBy('k.id', 'k.nama')
            ->orderBy('k.nama')
            ->get();

        $terakhirTap = Absensi::with('siswa')->whereDate('tanggal', $today)
            ->orderByDesc('updated_at')
            ->limit(8)
            ->get();

        return view('dashboard', compact(
            'totalSiswa', 'masukHariIni', 'pulangHariIni',
            'terlambatHariIni', 'belumAbsen', 'rekap', 'perKelas', 'terakhirTap', 'today',
            'rentang', 'boleh'
        ));
    }
}
