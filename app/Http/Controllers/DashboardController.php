<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Siswa;
use App\Support\TapAbsensi;
use App\Support\WaktuAbsensi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function tap(Request $request, TapAbsensi $tap)
    {
        $tag = trim((string) ($request->input('tag', $request->input('rfid_tag', ''))));
        $hasil = $tap->proses($tag);

        $out = $hasil['ok']
            ? ['ok' => true, 'type' => $hasil['type'], 'nama' => $hasil['nama'], 'jam' => $hasil['jam'], 'status' => $hasil['status']]
            : ['ok' => false, 'pesan' => $hasil['pesan']];

        return $request->wantsJson()
            ? response()->json($out, $hasil['http'])
            : back()->with('tap', $out);
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
        WaktuAbsensi::tandaiTanpaKeterangan();
        $today = now()->toDateString();
        $rentang = WaktuAbsensi::rentang($today);
        $boleh = WaktuAbsensi::boleh(now()->format('H:i:s'), $today);

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
