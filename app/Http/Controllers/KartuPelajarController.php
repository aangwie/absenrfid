<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Pengaturan;
use App\Models\Siswa;
use App\Support\FotoWebp;
use Illuminate\Http\Request;

class KartuPelajarController extends Controller
{
    public function index(Request $request)
    {
        if (Kelas::count() === 0) {
            return response()->view('siswa.need-kelas');
        }
        $siswas = Siswa::with('kelas')
            ->leftJoin('tb_kelas as k', 'k.id', '=', 'tb_siswa.kelas_id')
            ->when($request->filled('kelas_id'), fn ($q) => $q->where('tb_siswa.kelas_id', $request->kelas_id))
            ->when($request->q, fn ($q) => $q->where(function ($w) use ($request) {
                $w->where('tb_siswa.nama', 'like', "%{$request->q}%")
                    ->orWhere('tb_siswa.nisn', 'like', "%{$request->q}%")
                    ->orWhere('k.nama', 'like', "%{$request->q}%");
            }))
            ->select('tb_siswa.*')
            ->orderBy('k.nama')->orderBy('tb_siswa.nama')->paginate(15)->withQueryString();

        return view('kartu-pelajar.index', ['siswas' => $siswas, 'kelasList' => Kelas::orderBy('nama')->get()]);
    }

    public function cetak(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer', 'exists:tb_siswa,id'],
        ], [
            'ids.required' => 'Pilih minimal 1 siswa dulu.',
            'ids.min' => 'Pilih minimal 1 siswa dulu.',
            'ids.max' => 'Maksimal 100 kartu sekali cetak.',
        ]);

        $siswas = Siswa::with('kelas')->whereIn('id', $data['ids'])->orderBy('nama')->get();
        $p = array_merge([
            'nama_institusi' => 'SMK Smart School', 'alamat' => '', 'no_hp' => '', 'logo_sekolah' => '',
            'nama_kepala_sekolah' => '', 'nip_kepala_sekolah' => '',
            'ttd_kepala_sekolah' => '', 'stempel_sekolah' => '',
        ], Pengaturan::ambil());

        return view('kartu-pelajar.cetak', ['siswas' => $siswas, 'p' => $p]);
    }

    public function eksporGambar(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer', 'exists:tb_siswa,id'],
            'sisi' => ['nullable', 'in:depan,belakang,keduanya'],
        ], [
            'ids.required' => 'Pilih minimal 1 siswa dulu.',
            'ids.min' => 'Pilih minimal 1 siswa dulu.',
            'ids.max' => 'Maksimal 100 siswa sekali ekspor.',
            'sisi.in' => 'Sisi tidak valid.',
        ]);

        $siswas = Siswa::with('kelas')->whereIn('id', $data['ids'])->orderBy('nama')->get();
        $p = array_merge([
            'nama_institusi' => 'SMK Smart School', 'alamat' => '', 'no_hp' => '', 'logo_sekolah' => '',
            'nama_kepala_sekolah' => '', 'nip_kepala_sekolah' => '',
            'ttd_kepala_sekolah' => '', 'stempel_sekolah' => '',
        ], Pengaturan::ambil());

        return view('kartu-pelajar.ekspor-gambar', ['siswas' => $siswas, 'p' => $p, 'sisi' => $data['sisi'] ?? 'keduanya']);
    }

    public function foto(Request $request, Siswa $siswa)
    {
        $data = $request->validate([
            'foto' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:300'],
        ], [
            'foto.required' => 'Pilih file foto dulu.',
            'foto.mimes' => 'Pas foto harus jpg/png/webp.',
            'foto.max' => 'Pas foto maks 300 Kb.',
        ]);
        $f = $request->file('foto');
        $siswa->foto = FotoWebp::keDataUri($f);
        $siswa->save();

        return back()->with('ok', 'Pas foto ' . $siswa->nama . ' tersimpan (WebP).');
    }
}
