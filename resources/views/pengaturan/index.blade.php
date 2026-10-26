@extends('layouts.admin')
@section('title', 'Pengaturan')
@section('content')
@if (session('ok'))<div class="bg-emerald-500/15 border border-emerald-500/40 text-emerald-300 text-xs rounded-xl px-4 py-3">{{ session('ok') }}</div>@endif
@if ($errors->any())<div class="bg-rose-500/15 border border-rose-500/40 text-rose-300 text-xs rounded-xl px-4 py-3">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
@if (session('log'))<div class="bg-sky-500/10 border border-sky-500/40 rounded-xl px-4 py-3"><pre class="text-[11px] font-mono text-sky-200 whitespace-pre-wrap break-all">{{ session('log') }}</pre></div>@endif

<div class="bg-slate-800/80 border border-slate-700 rounded-2xl p-5">
  <div class="flex items-center gap-1 bg-slate-900/80 p-1 rounded-xl border border-slate-700/60 overflow-x-auto text-xs font-semibold mb-5">
    @foreach ([['institusi', 'Institusi'], ['waktu', 'Waktu Absensi'], ['update', 'Update GitHub'], ['perawatan', 'Perawatan'], ['backup', 'Backup/Import'], ['api', 'API Perangkat']] as [$v, $label])
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
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1.5">Nama Kepala Sekolah (opsional)</label>
          <input type="text" name="nama_kepala_sekolah" value="{{ old('nama_kepala_sekolah', $p['nama_kepala_sekolah'] ?? '') }}" maxlength="100" placeholder="cth Drs. H. Ahmad Suryana, M.Pd." class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1.5">NIP Kepala Sekolah (opsional)</label>
          <input type="text" name="nip_kepala_sekolah" value="{{ old('nip_kepala_sekolah', $p['nip_kepala_sekolah'] ?? '') }}" maxlength="30" placeholder="cth 19650101 199003 1 002" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">
        </div>
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
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1.5">TTD Kepala Sekolah (opsional)</label>
          @if (! empty($p['ttd_kepala_sekolah']))
          <div class="flex items-center gap-3 mb-2 bg-slate-900/60 border border-slate-700 rounded-xl p-3">
            <img src="{{ $p['ttd_kepala_sekolah'] }}" alt="TTD Kepala Sekolah" class="h-16 w-24 object-contain rounded-lg bg-white/90">
            <label class="flex items-center gap-1.5 text-xs text-slate-300 cursor-pointer"><input type="checkbox" name="hapus_ttd" value="1" class="accent-rose-600"> Hapus</label>
          </div>
          @endif
          <input type="file" name="ttd" accept="image/*" class="w-full text-xs text-slate-300 file:mr-3 file:px-4 file:py-2 file:rounded-lg file:border-0 file:bg-slate-700 file:text-white">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1.5">Stempel Sekolah (opsional)</label>
          @if (! empty($p['stempel_sekolah']))
          <div class="flex items-center gap-3 mb-2 bg-slate-900/60 border border-slate-700 rounded-xl p-3">
            <img src="{{ $p['stempel_sekolah'] }}" alt="Stempel Sekolah" class="h-16 w-16 object-contain rounded-lg bg-white/90">
            <label class="flex items-center gap-1.5 text-xs text-slate-300 cursor-pointer"><input type="checkbox" name="hapus_stempel" value="1" class="accent-rose-600"> Hapus</label>
          </div>
          @endif
          <input type="file" name="stempel" accept="image/*" class="w-full text-xs text-slate-300 file:mr-3 file:px-4 file:py-2 file:rounded-lg file:border-0 file:bg-slate-700 file:text-white">
        </div>
      </div>
      <p class="text-[11px] text-slate-500">TTD & stempel disimpan base64, tampil sebagai preview di atas dan dipakai di tanda tangan belakang kartu pelajar.</p>
      <div><button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold px-4 py-2 rounded-xl">Simpan</button></div>
    </form>
  </div>
  @elseif ($tab === 'waktu')
  @php($namaHari = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'])
  <div class="max-w-5xl flex flex-col gap-8">
    <div>
      <h2 class="text-base font-semibold text-white mb-1">Waktu Absensi Umum</h2>
      <p class="text-[11px] text-slate-500 mb-4">Atur jam absensi rutin untuk setiap hari. Format jam HH:MM (24 jam).</p>
      <form action="{{ route('pengaturan.waktu.umum') }}" method="POST">@csrf
        <div class="overflow-x-auto">
          <table class="w-full text-xs min-w-[760px]">
            <thead>
              <tr class="text-slate-400 border-b border-slate-700">
                <th class="text-left py-2 px-2">Hari</th>
                <th class="text-left py-2 px-2">Masuk <span class="font-normal text-slate-500">(mulai – selesai)</span></th>
                <th class="text-left py-2 px-2">Terlambat <span class="font-normal text-slate-500">(mulai – selesai)</span></th>
                <th class="text-left py-2 px-2">Pulang <span class="font-normal text-slate-500">(mulai – selesai)</span></th>
              </tr>
            </thead>
            <tbody>
              @foreach ($namaHari as $hari => $label)
              <tr class="border-b border-slate-700/50">
                <td class="py-2 px-2 font-semibold text-slate-200 whitespace-nowrap">{{ $label }}</td>
                @foreach (['masuk', 'terlambat', 'pulang'] as $bagian)
                @php($kMulai = $bagian.'_mulai')
                @php($kSelesai = $bagian.'_selesai')
                <td class="py-2 px-2">
                  <div class="flex items-center gap-1">
                    <input type="time" name="umum[{{ $hari }}][{{ $kMulai }}]" value="{{ old("umum.$hari.$kMulai", $jadwalUmum[$hari][$kMulai] ?? $p[$kMulai]) }}" title="Mulai {{ $bagian }}" class="w-28 bg-slate-900 border border-slate-700 rounded-lg px-2 py-1.5 text-xs text-white focus:outline-none focus:border-indigo-500" required>
                    <span class="text-slate-500">–</span>
                    <input type="time" name="umum[{{ $hari }}][{{ $kSelesai }}]" value="{{ old("umum.$hari.$kSelesai", $jadwalUmum[$hari][$kSelesai] ?? $p[$kSelesai]) }}" title="Selesai {{ $bagian }}" class="w-28 bg-slate-900 border border-slate-700 rounded-lg px-2 py-1.5 text-xs text-white focus:outline-none focus:border-indigo-500" required>
                  </div>
                </td>
                @endforeach
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <p class="text-[11px] text-slate-500 mt-2">Jadwal ini berlaku rutin setiap minggu. Contoh: Senin–Kamis masuk 06:30–07:00, terlambat 07:01–13:59, pulang 14:00–16:00.</p>
        <button type="submit" class="mt-3 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold px-4 py-2 rounded-xl">Simpan Jadwal Umum</button>
      </form>
    </div>

    <div>
      <h2 class="text-base font-semibold text-white mb-1">Waktu Absensi Khusus</h2>
      <p class="text-[11px] text-slate-500 mb-4">Berlaku pada rentang tanggal tertentu dan menimpa jadwal umum. Cocok untuk hari ujian, Ramadhan, atau kegiatan khusus.</p>
      <form action="{{ route('pengaturan.waktu.khusus') }}" method="POST" class="bg-slate-900/60 border border-slate-700 rounded-xl p-4 flex flex-col gap-3">@csrf
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Dari Tanggal</label>
            <input type="date" name="tanggal_mulai" value="{{ old('tanggal_mulai') }}" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-indigo-500" required>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Sampai Tanggal</label>
            <input type="date" name="tanggal_selesai" value="{{ old('tanggal_selesai') }}" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-indigo-500" required>
          </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          @foreach ([['masuk', 'Masuk'], ['terlambat', 'Terlambat'], ['pulang', 'Pulang']] as [$bagian, $label])
          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">{{ $label }} <span class="font-normal text-slate-500">(mulai – selesai)</span></label>
            <div class="flex items-center gap-1">
              <input type="time" name="{{ $bagian }}_mulai" value="{{ old($bagian.'_mulai') }}" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-2.5 py-2 text-sm text-white focus:outline-none focus:border-indigo-500" required>
              <span class="text-slate-500">–</span>
              <input type="time" name="{{ $bagian }}_selesai" value="{{ old($bagian.'_selesai') }}" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-2.5 py-2 text-sm text-white focus:outline-none focus:border-indigo-500" required>
            </div>
          </div>
          @endforeach
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1.5">Keterangan (opsional)</label>
          <input type="text" name="keterangan" value="{{ old('keterangan') }}" maxlength="150" placeholder="cth: Ujian semester / Ramadhan" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">
        </div>
        <div><button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold px-4 py-2 rounded-xl">Tambah Jadwal Khusus</button></div>
      </form>

      @if ($jadwalKhusus->isNotEmpty())
      <div class="mt-4 overflow-x-auto">
        <table class="w-full text-xs min-w-[720px]">
          <thead>
            <tr class="text-slate-400 border-b border-slate-700">
              <th class="text-left py-2 px-2">Tanggal</th>
              <th class="text-left py-2 px-2">Masuk</th>
              <th class="text-left py-2 px-2">Terlambat</th>
              <th class="text-left py-2 px-2">Pulang</th>
              <th class="text-left py-2 px-2">Keterangan</th>
              <th class="text-right py-2 px-2">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($jadwalKhusus as $j)
            <tr class="border-b border-slate-700/50">
              <td class="py-2 px-2 whitespace-nowrap text-slate-200">{{ $j->tanggal_mulai?->format('d/m/Y') }} – {{ $j->tanggal_selesai?->format('d/m/Y') }}</td>
              <td class="py-2 px-2 font-mono text-emerald-300">{{ substr($j->masuk_mulai, 0, 5) }}–{{ substr($j->masuk_selesai, 0, 5) }}</td>
              <td class="py-2 px-2 font-mono text-amber-300">{{ substr($j->terlambat_mulai, 0, 5) }}–{{ substr($j->terlambat_selesai, 0, 5) }}</td>
              <td class="py-2 px-2 font-mono text-indigo-300">{{ substr($j->pulang_mulai, 0, 5) }}–{{ substr($j->pulang_selesai, 0, 5) }}</td>
              <td class="py-2 px-2 text-slate-400">{{ $j->keterangan ?: '-' }}</td>
              <td class="py-2 px-2 text-right">
                <form action="{{ route('pengaturan.waktu.khusus.hapus', $j) }}" method="POST" onsubmit="return confirm('Hapus jadwal khusus ini?')">@csrf @method('DELETE')
                  <button type="submit" class="text-rose-300 hover:text-rose-200 text-[11px] font-semibold">Hapus</button>
                </form>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      @else
      <p class="mt-3 text-[11px] text-slate-500">Belum ada jadwal khusus.</p>
      @endif
    </div>
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
    @php($sym = $rawat['symlink'] ?? null)
    @if ($sym)
    <div class="mb-4 bg-slate-900/60 border border-slate-700 rounded-xl p-3 text-xs">
      <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-slate-300"><span class="font-semibold text-white">Symlink storage</span> <span class="text-slate-500">— public/storage → storage/app/public</span></p>
        @if ($sym['ok'])<span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 font-semibold">Aktif</span>
        @elseif ($sym['is_link'])<span class="px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/40 font-semibold">Target salah</span>
        @elseif ($sym['exists'])<span class="px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/40 font-semibold">Folder nyata</span>
        @else<span class="px-2 py-0.5 rounded-full bg-rose-500/20 text-rose-300 border border-rose-500/40 font-semibold">Belum dibuat</span>@endif
      </div>
      <p class="text-[11px] text-slate-500 mt-1">Target saat ini: <span class="font-mono text-slate-400">{{ $sym['current'] ?: ($sym['exists'] ? '(bukan symlink)' : '-') }}</span></p>
      <p class="text-[11px] text-slate-500 mt-1">Foto siswa disimpan di <span class="font-mono text-slate-400">storage/app/public/foto</span> dan disajikan lewat route <span class="font-mono text-slate-400">/media/foto/…</span>, jadi tetap tampil walau symlink belum aktif. Symlink hanya untuk akses langsung <span class="font-mono text-slate-400">/storage/…</span>.</p>
    </div>
    @endif
    <div class="flex flex-col gap-2">
      @foreach ([['clear-cache', 'Bersihkan Cache', 'Hapus cache config/route/view.'], ['migrate', 'Migrasi DB', 'Jalankan migrasi yang tertunda.'], ['symlink', 'Symlink Storage', 'Buat / perbaiki link public/storage → storage/app/public.'], ['queue-work', 'Jalankan Antrean', 'Proses jobs tertunda (queue:work, berhenti saat kosong, maks 55 dtk).']] as [$aksi, $label, $desc])
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
  @elseif ($tab === 'api')
  <div class="flex flex-col gap-5">
    <div>
      <h2 class="text-base font-semibold text-white mb-1">API Perangkat RFID</h2>
      <p class="text-[11px] text-slate-500">Kelola kunci perangkat ESP32 + MFRC522 dan pantau tap terakhir. Setiap perangkat punya kunci sendiri agar bisa dicabut tanpa mengganggu reader lain.</p>
    </div>

    <div class="grid sm:grid-cols-2 gap-3">
      <div class="bg-slate-900/60 border border-slate-700 rounded-xl p-4">
        <p class="text-[10px] uppercase tracking-wide text-slate-500 mb-1">Base URL API</p>
        <code class="text-[12px] font-mono text-sky-300 break-all">{{ $api['base_url'] }}</code>
        <p class="text-[10px] text-slate-500 mt-1.5">Diambil dari <span class="font-mono">APP_URL</span>. Pastikan memakai <span class="text-slate-300">https</span> di hosting.</p>
      </div>
      <div class="bg-slate-900/60 border border-slate-700 rounded-xl p-4">
        <p class="text-[10px] uppercase tracking-wide text-slate-500 mb-1">Kunci Induk (.env)</p>
        @if ($api['master_ada'])
          <code class="text-[12px] font-mono text-emerald-300">{{ $api['master_mask'] }}</code>
          <p class="text-[10px] text-slate-500 mt-1.5">Dipakai untuk uji cepat. Untuk produksi, buat perangkat di bawah dan pakai kunci perangkat.</p>
        @else
          <p class="text-xs text-amber-300">Belum diisi.</p>
          <p class="text-[10px] text-slate-500 mt-1.5">Isi <span class="font-mono">RFID_API_KEY</span> di <span class="font-mono">.env</span> bila ingin memakai kunci induk. Kunci perangkat di bawah tetap bisa dipakai tanpa ini.</p>
        @endif
      </div>
    </div>

    <div class="bg-slate-900/60 border border-slate-700 rounded-xl p-4">
      <h3 class="text-sm font-semibold text-white mb-3">Tambah Perangkat</h3>
      <form action="{{ route('pengaturan.perangkat.tambah') }}" method="POST" class="grid sm:grid-cols-3 gap-2">@csrf
        <input type="text" name="nama" value="{{ old('nama') }}" placeholder="Nama (contoh: Reader Gerbang)" class="bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500" required>
        <input type="text" name="lokasi" value="{{ old('lokasi') }}" placeholder="Lokasi (opsional)" class="bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">
        <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold px-4 py-2 rounded-lg">Tambah Perangkat</button>
      </form>
    </div>

    <div class="flex flex-col gap-3">
      <h3 class="text-sm font-semibold text-white">Perangkat Terdaftar <span class="text-slate-500 font-normal">({{ $perangkat->count() }})</span></h3>
      @forelse ($perangkat as $d)
      <div class="bg-slate-900/60 border border-slate-700 rounded-xl p-4 flex flex-col gap-3">
        <div class="flex items-start justify-between gap-3">
          <div>
            <p class="font-semibold text-white text-sm">{{ $d->nama }}</p>
            <p class="text-[11px] text-slate-500">{{ $d->lokasi ?: 'Tanpa lokasi' }} · terakhir tap: {{ $d->terakhir_tap_at ? $d->terakhir_tap_at->diffForHumans() : 'belum pernah' }}</p>
          </div>
          <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $d->aktif ? 'bg-emerald-500/15 text-emerald-300' : 'bg-slate-700/60 text-slate-400' }}">{{ $d->aktif ? 'AKTIF' : 'NONAKTIF' }}</span>
        </div>

        <div class="bg-slate-950/70 border border-slate-700/60 rounded-lg px-3 py-2">
          <p class="text-[10px] uppercase tracking-wide text-slate-500 mb-0.5">API Key (header X-API-Key)</p>
          <code class="text-[11px] font-mono text-emerald-300 break-all">{{ $d->api_key }}</code>
        </div>

        <form action="{{ route('pengaturan.perangkat.update', $d) }}" method="POST" class="grid sm:grid-cols-4 gap-2 items-center">@csrf @method('PUT')
          <input type="text" name="nama" value="{{ $d->nama }}" class="bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500" required>
          <input type="text" name="lokasi" value="{{ $d->lokasi }}" placeholder="Lokasi" class="bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">
          <label class="flex items-center gap-2 text-[11px] text-slate-300"><input type="checkbox" name="aktif" value="1" class="accent-indigo-600" @checked($d->aktif)> Aktif</label>
          <button type="submit" class="bg-slate-700 hover:bg-slate-600 text-white text-xs font-semibold px-3 py-2 rounded-lg">Simpan</button>
        </form>

        <div class="flex flex-wrap gap-2">
          <form action="{{ route('pengaturan.perangkat.regenerasi', $d) }}" method="POST" onsubmit="return confirm('Buat kunci baru? Kunci lama langsung tidak berlaku.')">@csrf
            <button type="submit" class="bg-amber-600/90 hover:bg-amber-500 text-white text-[11px] font-semibold px-3 py-1.5 rounded-lg">Buat Kunci Baru</button>
          </form>
          <form action="{{ route('pengaturan.perangkat.hapus', $d) }}" method="POST" onsubmit="return confirm('Hapus perangkat ini? Firmware dengan kunci ini akan ditolak.')">@csrf @method('DELETE')
            <button type="submit" class="bg-rose-600/90 hover:bg-rose-500 text-white text-[11px] font-semibold px-3 py-1.5 rounded-lg">Hapus</button>
          </form>
        </div>
      </div>
      @empty
      <div class="bg-slate-900/40 border border-dashed border-slate-700 rounded-xl px-4 py-6 text-center text-xs text-slate-500">Belum ada perangkat. Tambahkan reader pertama di atas.</div>
      @endforelse
    </div>

    <div class="bg-slate-900/60 border border-slate-700 rounded-xl p-4">
      <div class="flex items-center justify-between gap-3 mb-3">
        <h3 class="text-sm font-semibold text-white">Log Tap Terakhir <span class="text-slate-500 font-normal">(50 terbaru)</span></h3>
        @if ($tapLog->isNotEmpty())
        <form action="{{ route('pengaturan.perangkat.log-hapus') }}" method="POST" onsubmit="return confirm('Hapus seluruh log tap perangkat?')">@csrf @method('DELETE')
          <button type="submit" class="text-[11px] text-rose-300 hover:text-rose-200">Hapus semua log</button>
        </form>
        @endif
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-[11px]">
          <thead>
            <tr class="text-left text-slate-500 border-b border-slate-700/60">
              <th class="py-1.5 pr-3 font-medium">Waktu</th>
              <th class="py-1.5 pr-3 font-medium">Perangkat</th>
              <th class="py-1.5 pr-3 font-medium">Tag</th>
              <th class="py-1.5 pr-3 font-medium">Hasil</th>
              <th class="py-1.5 font-medium">IP</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($tapLog as $l)
            <tr class="border-b border-slate-800/60">
              <td class="py-1.5 pr-3 text-slate-400 whitespace-nowrap">{{ $l->created_at->format('d/m H:i:s') }}</td>
              <td class="py-1.5 pr-3 text-slate-300">{{ $l->perangkat->nama ?? 'master' }}</td>
              <td class="py-1.5 pr-3 font-mono text-slate-300">{{ $l->tag !== '' ? $l->tag : '(kosong)' }}</td>
              <td class="py-1.5 pr-3"><span class="px-2 py-0.5 rounded-full font-semibold {{ $l->warnaKode() }}">{{ $l->kode }}</span></td>
              <td class="py-1.5 font-mono text-slate-500">{{ $l->ip }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="py-4 text-center text-slate-500">Belum ada tap dari API.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="bg-sky-500/10 border border-sky-500/30 rounded-xl p-4 text-[11px] text-sky-100/90">
      <p class="font-semibold text-sky-200 mb-1">Cara pakai singkat</p>
      <p class="mb-1">Salin kunci perangkat ke konstanta <span class="font-mono">API_KEY</span> di firmware, lalu tap kartu:</p>
      <pre class="bg-slate-950/60 border border-sky-500/20 rounded-lg p-3 overflow-x-auto text-[10px] font-mono text-sky-200">curl -X POST {{ $api['base_url'] }}/tap \
  -H "X-API-Key: KUNCI_PERANGKAT" \
  -d "tag=2234749610"</pre>
      <p class="mt-1.5">Dokumentasi lengkap ada di menu <span class="text-white font-medium">Dokumentasi → API RFID</span>.</p>
    </div>
  </div>
  @endif
</div>
@endsection
