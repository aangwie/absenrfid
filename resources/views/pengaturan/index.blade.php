@extends('layouts.admin')
@section('title', 'Pengaturan')
@section('content')
@if (session('ok'))<div class="bg-emerald-500/15 border border-emerald-500/40 text-emerald-300 text-xs rounded-xl px-4 py-3">{{ session('ok') }}</div>@endif
@if ($errors->any())<div class="bg-rose-500/15 border border-rose-500/40 text-rose-300 text-xs rounded-xl px-4 py-3">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
@if (session('log'))<div class="bg-sky-500/10 border border-sky-500/40 rounded-xl px-4 py-3"><pre class="text-[11px] font-mono text-sky-200 whitespace-pre-wrap break-all">{{ session('log') }}</pre></div>@endif

<div class="bg-slate-800/80 border border-slate-700 rounded-2xl p-5">
  <div class="flex items-center gap-1 bg-slate-900/80 p-1 rounded-xl border border-slate-700/60 overflow-x-auto text-xs font-semibold mb-5">
    @foreach ([['institusi', 'Institusi'], ['waktu', 'Waktu Absensi'], ['update', 'Update GitHub'], ['perawatan', 'Perawatan'], ['backup', 'Backup/Import']] as [$v, $label])
    <a href="{{ route('pengaturan.index', ['tab' => $v]) }}" class="px-3 py-1.5 rounded-lg whitespace-nowrap {{ $tab === $v ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white' }}">{{ $label }}</a>
    @endforeach
  </div>

  @if ($tab === 'institusi')
  <div class="max-w-2xl">
    <h2 class="text-base font-semibold text-white mb-1">Data Institusi</h2>
    <p class="text-[11px] text-slate-500 mb-4">Nama, alamat, dan kontak yang tampil di kios & laporan.</p>
    <form action="{{ route('pengaturan.institusi') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-3">@csrf
      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Nama Institusi</label>
        <input type="text" name="nama_institusi" value="{{ old('nama_institusi', $p['nama_institusi']) }}" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500" required>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Alamat</label>
        <textarea name="alamat" rows="3" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500" required>{{ old('alamat', $p['alamat']) }}</textarea>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Nomor HP</label>
        <input type="text" name="no_hp" value="{{ old('no_hp', $p['no_hp']) }}" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500" required>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Logo</label>
        @if ($p['logo_sekolah'])
        <div class="flex items-center gap-3 mb-2 bg-slate-900/60 border border-slate-700 rounded-xl p-3">
          <img src="{{ $p['logo_sekolah'] }}" alt="Logo" class="h-16 w-16 object-contain rounded-lg bg-white/10">
          <label class="flex items-center gap-1.5 text-xs text-slate-300 cursor-pointer"><input type="checkbox" name="hapus_logo" value="1" class="accent-rose-600"> Hapus logo</label>
        </div>
        @endif
        <input type="file" name="logo" accept="image/*" class="w-full text-xs text-slate-300 file:mr-3 file:px-4 file:py-2 file:rounded-lg file:border-0 file:bg-slate-700 file:text-white">
      </div>
      <div><button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold px-4 py-2 rounded-xl">Simpan</button></div>
    </form>
  </div>
  @elseif ($tab === 'waktu')
  <div class="max-w-2xl">
    <h2 class="text-base font-semibold text-white mb-1">Waktu Absensi</h2>
    <p class="text-[11px] text-slate-500 mb-4">Format jam HH:MM (24 jam).</p>
    <form action="{{ route('pengaturan.waktu') }}" method="POST">@csrf
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        @foreach ([['masuk_mulai', 'Masuk Mulai'], ['masuk_selesai', 'Masuk Selesai'], ['terlambat_mulai', 'Terlambat Mulai'], ['terlambat_selesai', 'Terlambat Selesai'], ['pulang_mulai', 'Pulang Mulai'], ['pulang_selesai', 'Pulang Selesai']] as [$name, $label])
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1.5">{{ $label }}</label>
          <input type="time" name="{{ $name }}" value="{{ old($name, $p[$name]) }}" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-indigo-500" required>
        </div>
        @endforeach
      </div>
      <button type="submit" class="mt-4 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold px-4 py-2 rounded-xl">Simpan</button>
    </form>
  </div>
  @elseif ($tab === 'update')
  <div class="max-w-2xl flex flex-col gap-4">
    <div>
      <h2 class="text-base font-semibold text-white mb-1">Update dari GitHub</h2>
      <p class="text-[11px] text-slate-500 mb-4">Simpan konfigurasi repo dulu, lalu jalankan pembaruan.</p>
      <form action="{{ route('pengaturan.update-config') }}" method="POST" class="flex flex-col gap-3 bg-slate-900/60 border border-slate-700 rounded-xl p-4">@csrf
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1.5">Repository (owner/repo)</label>
          <input type="text" name="github_repo" value="{{ old('github_repo', $p['github_repo']) }}" placeholder="owner/repo" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500" required>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1.5">Branch</label>
          <input type="text" name="github_branch" value="{{ old('github_branch', $p['github_branch']) }}" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1.5">Token</label>
          <input type="password" name="github_token" value="{{ old('github_token', $p['github_token']) }}" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500" required>
        </div>
        <div><button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold px-4 py-2 rounded-xl">Simpan Config</button></div>
      </form>
    </div>
    <form action="{{ route('pengaturan.update-app') }}" method="POST" class="bg-slate-900/60 border border-slate-700 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center gap-2">@csrf
      <p class="text-xs text-slate-400 flex-1">Jalankan <span class="font-mono text-slate-200">git fetch + reset --hard</span> ke branch tersimpan.</p>
      <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold px-4 py-2 rounded-xl">Perbarui Sekarang</button>
    </form>
  </div>
  @elseif ($tab === 'perawatan')
  <div class="max-w-2xl">
    <h2 class="text-base font-semibold text-white mb-1">Perawatan</h2>
    <p class="text-[11px] text-slate-500 mb-4">Operasi pemeliharaan aplikasi.</p>
    <div class="flex gap-3 mb-4 bg-slate-900/60 border border-slate-700 rounded-xl p-3 text-xs">
      <div class="flex-1"><p class="text-slate-500">Jobs antre</p><p class="font-mono font-bold text-amber-300 text-base">{{ $rawat['jobs'] ?? 0 }}</p></div>
      <div class="flex-1"><p class="text-slate-500">Jobs gagal</p><p class="font-mono font-bold text-rose-300 text-base">{{ $rawat['gagal'] ?? 0 }}</p></div>
      <div class="flex-1"><p class="text-slate-500">Notifikasi antre</p><p class="font-mono font-bold text-sky-300 text-base">{{ $rawat['notif_antre'] ?? 0 }}</p></div>
    </div>
    <div class="flex flex-col gap-2">
      @foreach ([['clear-cache', 'Bersihkan Cache', 'Hapus cache config/route/view.'], ['migrate', 'Migrasi DB', 'Jalankan migrasi yang tertunda.'], ['symlink', 'Symlink Storage', 'Buat link public/storage bila belum ada.'], ['queue-work', 'Jalankan Antrean', 'Proses jobs tertunda (queue:work, berhenti saat kosong, maks 55 dtk).']] as [$aksi, $label, $desc])
      <form action="{{ route('pengaturan.rawat') }}" method="POST" class="flex flex-col sm:flex-row sm:items-center gap-2 bg-slate-900/60 border border-slate-700 rounded-xl p-3">@csrf
        <input type="hidden" name="aksi" value="{{ $aksi }}">
        <p class="text-xs text-slate-300 flex-1"><span class="font-semibold text-white">{{ $label }}</span> <span class="text-slate-500">— {{ $desc }}</span></p>
        <button type="submit" class="bg-amber-600 hover:bg-amber-500 text-white text-sm font-semibold px-4 py-2 rounded-xl">{{ $label }}</button>
      </form>
      @endforeach
    </div>
  </div>
  @elseif ($tab === 'backup')
  <div class="max-w-2xl flex flex-col gap-4">
    <div>
      <h2 class="text-base font-semibold text-white mb-1">Backup & Import</h2>
      <p class="text-[11px] text-slate-500 mb-4">Unduh salinan database atau pulihkan dari file .sql.</p>
    </div>
    <div class="bg-slate-900/60 border border-slate-700 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center gap-2">
      <p class="text-xs text-slate-300 flex-1"><span class="font-semibold text-white">Unduh Backup</span> <span class="text-slate-500">— ekspor seluruh tabel (.sql).</span></p>
      <a href="{{ route('pengaturan.backup') }}" class="bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold px-4 py-2 rounded-xl text-center">Unduh (.sql)</a>
    </div>
    <form action="{{ route('pengaturan.impor') }}" method="POST" enctype="multipart/form-data" class="bg-slate-900/60 border border-slate-700 rounded-xl p-4 flex flex-col gap-3">@csrf
      <p class="text-xs text-slate-300"><span class="font-semibold text-white">Import</span> <span class="text-slate-500">— pulihkan dari file .sql / .txt (maks 10 MB).</span></p>
      <input type="file" name="file" accept=".sql,.txt" class="w-full text-xs text-slate-300 file:mr-3 file:px-4 file:py-2 file:rounded-lg file:border-0 file:bg-slate-700 file:text-white" required>
      <div><button type="submit" class="bg-rose-600 hover:bg-rose-500 text-white text-sm font-semibold px-4 py-2 rounded-xl">Import</button></div>
    </form>
  </div>
  @endif
</div>
@endsection
