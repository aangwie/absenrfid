@extends('layouts.admin')
@section('title', 'Pengaturan')
@section('content')
@if (session('ok'))<div class="bg-emerald-500/15 border border-emerald-500/40 text-emerald-300 text-xs rounded-xl px-4 py-3">{{ session('ok') }}</div>@endif
@if ($errors->any())<div class="bg-rose-500/15 border border-rose-500/40 text-rose-300 text-xs rounded-xl px-4 py-3">{{ $errors->first() }}</div>@endif
<div class="bg-slate-800/80 border border-slate-700 rounded-2xl p-5">
  <div class="flex gap-1 bg-slate-900/80 p-1 rounded-xl border border-slate-700/60 text-xs font-semibold mb-4 w-fit">
    @foreach ([['institusi', 'Institusi'], ['waktu', 'Waktu'], ['update', 'Update'], ['perawatan', 'Perawatan'], ['backup', 'Backup DB'], ['impor', 'Import DB']] as [$k, $label])
    <a href="{{ route('pengaturan.index', ['tab' => $k]) }}" class="px-3 py-1.5 rounded-lg {{ $tab === $k ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white' }}">{{ $label }}</a>
    @endforeach
  </div>
  @if ($tab === 'institusi')
  <form method="POST" action="{{ route('pengaturan.institusi') }}" enctype="multipart/form-data" class="flex flex-col gap-3 text-sm max-w-lg">@csrf
    <div><label class="block text-xs text-slate-300 mb-1">Nama Institusi</label><input name="nama_institusi" value="{{ $p['nama_institusi'] }}" required placeholder="SMK Negeri 1 Contoh" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-white"></div>
    <div><label class="block text-xs text-slate-300 mb-1">Alamat</label><textarea name="alamat" required rows="3" placeholder="Jl. Contoh No.1" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-white">{{ $p['alamat'] }}</textarea></div>
    <div><label class="block text-xs text-slate-300 mb-1">Nomor HP</label><input name="no_hp" value="{{ $p['no_hp'] }}" required placeholder="08123456789" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-white font-mono"></div>
    <div><label class="block text-xs text-slate-300 mb-1">Logo Sekolah (jpg/png/webp, maks 400KB, simpan base64 di DB)</label>
      @if (! empty($p['logo_sekolah']))<img src="{{ $p['logo_sekolah'] }}" alt="Logo" class="h-16 w-16 object-contain bg-white rounded-xl p-1 mb-2">@endif
      <input type="file" name="logo" accept=".jpg,.jpeg,.png,.webp" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-white text-xs file:bg-slate-700 file:border-0 file:rounded-lg file:px-3 file:py-1.5 file:text-white file:mr-3">
      @if (! empty($p['logo_sekolah']))<label class="flex items-center gap-1.5 text-xs text-slate-400 mt-1 cursor-pointer"><input type="checkbox" name="hapus_logo" value="1" class="accent-rose-600"> Hapus logo</label>@endif
    </div>
    <button class="bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-4 py-2.5 rounded-xl w-fit">Simpan Institusi</button>
  </form>
  @elseif ($tab === 'waktu')
  <form method="POST" action="{{ route('pengaturan.waktu') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">@csrf
    @foreach ([['masuk_mulai', 'masuk_selesai', 'Masuk', 'text-emerald-300'], ['terlambat_mulai', 'terlambat_selesai', 'Terlambat', 'text-amber-300'], ['pulang_mulai', 'pulang_selesai', 'Pulang', 'text-indigo-300']] as [$a, $b, $label, $cls])
    <div class="bg-slate-900/60 border border-slate-700 rounded-xl p-3">
      <p class="font-semibold text-xs mb-2 {{ $cls }}">{{ $label }}</p>
      <label class="block text-[11px] text-slate-400 mb-1">Mulai</label>
      <input type="time" name="{{ $a }}" value="{{ $p[$a] }}" required class="w-full bg-slate-950 border border-slate-700 rounded-lg px-2 py-2 text-white text-sm">
      <label class="block text-[11px] text-slate-400 mb-1 mt-2">Selesai</label>
      <input type="time" name="{{ $b }}" value="{{ $p[$b] }}" required class="w-full bg-slate-950 border border-slate-700 rounded-lg px-2 py-2 text-white text-sm">
    </div>
    @endforeach
    <div class="sm:col-span-3 flex flex-col sm:flex-row items-start sm:items-center gap-2">
      <button class="bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-4 py-2 rounded-xl text-sm">Simpan Jam</button>
      <p class="text-[11px] text-slate-500">Di luar rentang = Tanpa Keterangan, tap ditolak.</p>
    </div>
  </form>
  @elseif ($tab === 'update')
  <div class="bg-amber-500/10 border border-amber-500/40 rounded-xl px-4 py-3 text-xs text-amber-200 mb-4">Perhatian: tombol Perbarui menjalankan git fetch + reset --hard ke branch GitHub. Semua perubahan lokal yang belum di-commit akan hilang permanen dan file kerja ditimpa versi GitHub. Lanjutkan hanya jika tidak ada pekerjaan lokal yang perlu disimpan.</div>
  <form method="POST" action="{{ route('pengaturan.update-app') }}" class="flex flex-col gap-3 text-sm max-w-lg" onsubmit="return confirm('Timpa kode lokal dengan versi GitHub? Perubahan lokal hilang.');">@csrf
    <div><label class="block text-xs text-slate-300 mb-1">Repo GitHub (owner/repo atau URL)</label><input name="github_repo" value="{{ $p['github_repo'] }}" required placeholder="owner/absenrfid" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-white"></div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div><label class="block text-xs text-slate-300 mb-1">Branch</label><input name="github_branch" value="{{ $p['github_branch'] }}" placeholder="main" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-white"></div>
      <div><label class="block text-xs text-slate-300 mb-1">Token (github_pat_...)</label><input type="password" name="github_token" required autocomplete="off" placeholder="github_pat_..." class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-white font-mono"></div>
    </div>
    <p class="text-[11px] text-slate-500">Token disimpan di database. Di log tampil sebagai ***.</p>
    <button class="bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-4 py-2.5 rounded-xl w-fit">Perbarui dari GitHub</button>
  </form>
  @if ($log)<div class="mt-4"><p class="text-xs text-slate-300 font-semibold mb-1">Log Terminal</p><pre class="bg-black/70 border border-slate-700 rounded-xl p-3 text-[11px] font-mono text-emerald-200 whitespace-pre-wrap max-h-80 overflow-auto">{{ $log }}</pre></div>@endif
  @elseif ($tab === 'perawatan')
  <div class="bg-amber-500/10 border border-amber-500/40 rounded-xl px-4 py-3 text-xs text-amber-200 mb-4">Perhatian: Force Migrasi mengubah struktur database secara langsung. Pastikan Anda memiliki backup database terbaru sebelum melanjutkan.</div>
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
    @foreach ([['clear-cache', 'Clear Cache', 'Hapus cache config/route/view.'], ['migrate', 'Force Migrasi', 'Jalankan migrate --force.'], ['symlink', 'Symlink Storage', 'Buat link public/storage.']] as [$aksi, $label, $desc])
    <form method="POST" action="{{ route('pengaturan.rawat') }}" @if ($aksi === 'migrate') onsubmit="return confirm('Jalankan migrasi database sekarang? Pastikan sudah backup.');" @endif class="bg-slate-900/60 border border-slate-700 rounded-xl p-4 flex flex-col gap-2">@csrf<input type="hidden" name="aksi" value="{{ $aksi }}">
      <p class="font-semibold text-white">{{ $label }}</p><p class="text-[11px] text-slate-500 flex-1">{{ $desc }}</p>
      <button class="bg-slate-700 hover:bg-indigo-600 text-white font-semibold px-4 py-2 rounded-xl text-sm">Jalankan</button>
    </form>
    @endforeach
  </div>
  @if ($log)<div class="mt-4"><p class="text-xs text-slate-300 font-semibold mb-1">Log Terminal</p><pre class="bg-black/70 border border-slate-700 rounded-xl p-3 text-[11px] font-mono text-emerald-200 whitespace-pre-wrap max-h-80 overflow-auto">{{ $log }}</pre></div>@endif
  @elseif ($tab === 'backup')
  <p class="text-xs text-slate-400 mb-3">Unduh dump .sql berisi struktur + data semua tabel.</p>
  <a href="{{ route('pengaturan.backup') }}" class="bg-emerald-600 hover:bg-emerald-500 text-white font-semibold px-4 py-2.5 rounded-xl text-sm w-fit inline-block">Backup Sekarang</a>
  @elseif ($tab === 'impor')
  <div class="bg-rose-500/10 border border-rose-500/40 rounded-xl px-4 py-3 text-xs text-rose-200 mb-4">Bahaya: import menimpa data sekarang. Backup dulu sebelum lanjut. Maks 10MB, file .sql.</div>
  <form method="POST" action="{{ route('pengaturan.impor') }}" enctype="multipart/form-data" class="flex flex-col gap-3 text-sm max-w-lg" onsubmit="return confirm('Import akan menimpa data sekarang. Lanjut?');">@csrf
    <input type="file" name="file" accept=".sql,.txt" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-white text-xs file:bg-slate-700 file:border-0 file:rounded-lg file:px-3 file:py-1.5 file:text-white file:mr-3">
    <button class="bg-rose-600 hover:bg-rose-500 text-white font-semibold px-4 py-2.5 rounded-xl w-fit">Import Database</button>
  </form>
  @endif
</div>
@endsection
