@extends('layouts.admin')
@section('title', 'Edit Status')
@section('content')
<div class="bg-slate-800/80 border border-slate-700 rounded-2xl p-5 max-w-lg text-sm">
  <p class="text-white font-semibold">{{ $r->siswa->nama ?? $r->nisn }}</p>
  <p class="text-xs text-slate-400 font-mono mb-3">NISN {{ $r->nisn }} | {{ $r->tanggal }} | {{ $r->siswa->kelas->nama ?? '-' }}</p>
  <p class="text-xs text-slate-400 mb-1">Status sekarang: <span class="text-white font-semibold">{{ $r->status_masuk }}</span></p>
  <form method="POST" action="{{ route('laporan.perbarui', $r->id) }}?{{ http_build_query(request()->only(['mode','tanggal','dari','sampai','bulan'])) }}">@csrf @method('PUT')
    <label class="block text-xs text-slate-300 mb-1">Ubah ke</label>
    <select name="status_masuk" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-white mb-3">
      @foreach (['Sakit','Ijin'] as $o)<option value="{{ $o }}" {{ $r->status_masuk === $o ? 'selected' : '' }}>{{ $o }}</option>@endforeach
    </select>
    <div class="flex gap-2">
      <button class="bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-4 py-2 rounded-xl">Simpan</button>
      <a href="{{ route('laporan.index', request()->only(['mode','tanggal','dari','sampai','bulan'])) }}" class="bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-xl">Batal</a>
    </div>
  </form>
</div>
@endsection
