<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use Illuminate\Http\Request;

class KelasController extends Controller
{
    public function index(Request $request)
    {
        $kelas = Kelas::withCount('siswas')
            ->when($request->q, fn ($q) => $q->where('nama', 'like', "%{$request->q}%"))
            ->orderBy('nama')->paginate(10)->withQueryString();

        return view('kelas.index', compact('kelas'));
    }

    public function store(Request $request)
    {
        $request->validate(['nama' => ['required', 'string', 'max:30', 'unique:tb_kelas,nama']], [
            'nama.required' => 'Nama kelas wajib diisi.',
            'nama.unique' => 'Kelas sudah ada.',
        ]);

        Kelas::create(['nama' => trim($request->nama)]);

        return back()->with('ok', 'Kelas ditambah.');
    }

    public function update(Request $request, Kelas $kelas)
    {
        $request->validate(['nama' => ['required', 'string', 'max:30', 'unique:tb_kelas,nama,' . $kelas->id]], [
            'nama.required' => 'Nama kelas wajib diisi.',
            'nama.unique' => 'Kelas sudah ada.',
        ]);

        $kelas->update(['nama' => trim($request->nama)]);

        return back()->with('ok', 'Kelas diupdate.');
    }

    public function destroy(Kelas $kelas)
    {
        if ($kelas->siswas()->exists()) {
            return back()->withErrors(['kelas' => "Kelas {$kelas->nama} dipakai {$kelas->siswas()->count()} siswa, tidak bisa dihapus."]);
        }
        $kelas->delete();

        return back()->with('ok', 'Kelas dihapus.');
    }
}
