@extends('layouts.admin')
@section('title', 'Laporan Absensi')
@section('content')
@if (session('ok'))<div class="bg-emerald-500/15 border border-emerald-500/40 text-emerald-300 text-xs rounded-xl px-4 py-3 mb-3">{{ session('ok') }}</div>@endif
@if ($errors->any())<div class="bg-rose-500/15 border border-rose-500/40 text-rose-300 text-xs rounded-xl px-4 py-3 mb-3">{{ $errors->first() }}</div>@endif
<div class="bg-slate-800/80 border border-slate-700 rounded-2xl p-5">
  <form method="GET" class="flex flex-col gap-3 mb-4 text-sm">
    <div class="flex flex-wrap gap-4 text-xs font-semibold">
      @foreach ([['harian', 'Harian'], ['periode', 'Periode'], ['bulanan', 'Bulanan']] as [$v, $label])
      <label class="flex items-center gap-1.5 cursor-pointer {{ $mode === $v ? 'text-white' : 'text-slate-400' }}"><input type="radio" name="mode" value="{{ $v }}" {{ $mode === $v ? 'checked' : '' }} class="accent-indigo-600" onchange="this.form.submit()"> {{ $label }}</label>
      @endforeach
      <span class="ml-auto text-slate-500 font-normal">{{ $judul }}</span>
    </div>
    <div class="flex flex-col sm:flex-row gap-2">
      @if ($mode === 'harian')
      <input type="date" name="tanggal" value="{{ $tanggal }}" class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-indigo-500">
      @elseif ($mode === 'periode')
      <input type="date" name="dari" value="{{ $dari }}" class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-indigo-500">
      <span class="text-slate-500 text-xs self-center">s.d.</span>
      <input type="date" name="sampai" value="{{ $sampai }}" class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-indigo-500">
      @else
      <input type="month" name="bulan" value="{{ $bulan }}" class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-indigo-500">
      @endif
      <button class="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold px-4 py-2 rounded-xl">Tampil</button>
    </div>
  </form>
  <form method="GET" action="{{ route('laporan.ekspor') }}" target="_blank" rel="noopener" class="flex flex-col sm:flex-row sm:items-center gap-3 mb-4 bg-slate-900/60 border border-slate-700 rounded-xl p-3 text-xs">
    <input type="hidden" name="mode" value="{{ $mode }}">
    <input type="hidden" name="tanggal" value="{{ $tanggal }}">
    <input type="hidden" name="dari" value="{{ $dari }}">
    <input type="hidden" name="sampai" value="{{ $sampai }}">
    <input type="hidden" name="bulan" value="{{ $bulan }}">
    <span class="font-semibold text-slate-300">Ekspor:</span>
    <label class="flex items-center gap-1.5 text-slate-300 cursor-pointer"><input type="radio" name="format" value="xlsx" checked class="accent-indigo-600"> Excel (.xlsx)</label>
    <label class="flex items-center gap-1.5 text-slate-300 cursor-pointer"><input type="radio" name="format" value="pdf" class="accent-indigo-600"> PDF</label>
    <span class="text-slate-600">|</span>
    <label class="flex items-center gap-1.5 text-slate-300 cursor-pointer"><input type="radio" name="orientasi" value="portrait" checked class="accent-indigo-600"> Portrait</label>
    <label class="flex items-center gap-1.5 text-slate-300 cursor-pointer"><input type="radio" name="orientasi" value="landscape" class="accent-indigo-600"> Landscape</label>
    <button class="sm:ml-auto bg-emerald-600 hover:bg-emerald-500 text-white font-semibold px-4 py-2 rounded-xl text-sm">Unduh</button>
  </form>
  <form id="bulk-form" method="POST" action="{{ route('laporan.bulk-hapus') }}" class="flex flex-col sm:flex-row sm:items-center gap-2 mb-3 bg-slate-900/60 border border-slate-700 rounded-xl p-3">@csrf
    <label class="flex items-center gap-2 text-xs text-slate-300 font-semibold whitespace-nowrap cursor-pointer"><input type="checkbox" id="check-all" class="w-4 h-4 accent-indigo-600 rounded"> Pilih semua</label>
    <span id="bulk-count" class="text-[11px] text-slate-500">Pilih data via checklist</span>
    <button type="submit" id="btn-bulk-hapus" class="sm:ml-auto bg-rose-600 hover:bg-rose-500 text-white text-xs font-semibold px-4 py-2 rounded-xl">Hapus (<span id="bulk-del-n">0</span>)</button>
  </form>
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
    <h2 class="text-sm font-semibold text-white">Sudah Absen ({{ $rows->total() }})<span class="text-slate-500 font-normal"> • {{ $judul }}</span></h2>
    <form method="GET" class="flex items-center gap-2 w-full sm:w-auto">
      @foreach (['mode' => $mode, 'tanggal' => $tanggal, 'dari' => $dari, 'sampai' => $sampai, 'bulan' => $bulan, 'cari_belum' => $cariBelum] as $n => $v)
      <input type="hidden" name="{{ $n }}" value="{{ $v }}">
      @endforeach
      <input type="search" name="cari" value="{{ $cari }}" placeholder="Cari nama / NISN / RFID / kelas..." class="w-full sm:w-64 bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">
      <button class="bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold px-3 py-2 rounded-xl whitespace-nowrap">Cari</button>
    </form>
  </div>
  <div class="overflow-x-auto rounded-xl border border-slate-700/80"><table class="w-full text-left text-xs border-collapse">
    <thead><tr class="bg-slate-900/90 text-slate-300 border-b border-slate-700 font-semibold"><th class="py-3.5 px-4 w-10"><input type="checkbox" id="check-all-head" class="w-4 h-4 accent-indigo-600 rounded"></th><th class="py-3.5 px-4">No</th><th class="py-3.5 px-4">Siswa</th><th class="py-3.5 px-4">NISN / RFID</th><th class="py-3.5 px-4">Kelas</th><th class="py-3.5 px-4 text-center">Masuk</th><th class="py-3.5 px-4 text-center">Pulang</th><th class="py-3.5 px-4 text-center">Status</th><th class="py-3.5 px-4 text-center">Aksi</th></tr></thead>
    <tbody class="divide-y divide-slate-700/60 text-slate-300">@forelse ($rows as $i => $r)<tr class="hover:bg-slate-700/30 transition-colors"><td class="py-3.5 px-4"><input type="checkbox" name="ids[]" value="{{ $r->id }}" form="bulk-form" class="row-check w-4 h-4 accent-indigo-600 rounded cursor-pointer"></td><td class="py-3.5 px-4 font-mono text-slate-400">{{ $rows->firstItem() + $i }}</td><td class="py-3.5 px-4"><div class="font-semibold text-white text-sm">{{ $r->siswa->nama ?? $r->nisn }}</div></td><td class="py-3.5 px-4 font-mono"><div class="text-slate-200">NISN: {{ $r->nisn }}</div><span class="inline-block text-[10px] px-2 py-0.5 rounded border mt-1 font-semibold bg-slate-900 text-slate-400 border-slate-700">{{ $r->tanggal }}</span></td><td class="py-3.5 px-4"><span class="bg-slate-700/60 px-2.5 py-1 rounded-md border border-slate-600/80 font-medium">{{ $r->siswa->kelas->nama ?? '-' }}</span></td><td class="py-3.5 px-4 text-center">@if ($r->jam_masuk)<div><span class="font-mono {{ $r->status_masuk === 'Terlambat' ? 'text-amber-300' : 'text-emerald-400' }} font-semibold">{{ $r->jam_masuk }}</span><div><span class="text-[10px] px-2 py-0.5 rounded-full mt-1 inline-block {{ $r->status_masuk === 'Terlambat' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' }}">{{ $r->status_masuk }}</span></div></div>@elseif (in_array($r->status_masuk, ['Sakit','Ijin','Izin']))<span class="text-[10px] px-2 py-0.5 rounded-full inline-block bg-sky-500/20 text-sky-300 border border-sky-500/30">{{ $r->status_masuk }}</span>@elseif ($r->status_masuk === 'Di Sekolah')<span class="text-[10px] px-2 py-0.5 rounded-full inline-block bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Di Sekolah</span>@elseif ($r->status_masuk === 'Tanpa Keterangan')<span class="text-[10px] px-2 py-0.5 rounded-full inline-block bg-slate-600/40 text-slate-300 border border-slate-600">Tanpa Keterangan</span>@else<span class="text-slate-500 font-mono">-</span>@endif</td><td class="py-3.5 px-4 text-center">@if ($r->jam_pulang)<div><span class="font-mono text-indigo-400 font-semibold">{{ $r->jam_pulang }}</span><div><span class="text-[10px] bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 px-2 py-0.5 rounded-full mt-1 inline-block">{{ $r->status_pulang }}</span></div></div>@else<span class="text-slate-500 font-mono">-</span>@endif</td><td class="py-3.5 px-4 text-center">@if (in_array($r->status_masuk, ['Sakit','Ijin','Izin']))<span class="bg-sky-500/20 text-sky-300 border border-sky-500/40 px-2.5 py-1 rounded-full text-[11px] font-semibold">{{ $r->status_masuk }}</span>@elseif ($r->jam_pulang)<span class="bg-indigo-500/20 text-indigo-300 border border-indigo-500/40 px-2.5 py-1 rounded-full text-[11px] font-semibold">Selesai Pulang</span>@elseif ($r->jam_masuk)<span class="bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 px-2.5 py-1 rounded-full text-[11px] font-semibold">Di Sekolah</span>@elseif ($r->status_masuk === 'Di Sekolah')<span class="bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 px-2.5 py-1 rounded-full text-[11px] font-semibold">Di Sekolah</span>@elseif ($r->status_masuk === 'Tanpa Keterangan')<span class="bg-slate-600/40 text-slate-300 border border-slate-600 px-2.5 py-1 rounded-full text-[11px] font-semibold">Tanpa Keterangan</span>@else<span class="bg-rose-500/20 text-rose-300 border border-rose-500/40 px-2.5 py-1 rounded-full text-[11px] font-semibold">Belum Absen</span>@endif</td><td class="py-3.5 px-4 text-center"><a href="{{ route('laporan.edit', [$r->id, 'mode' => $mode, 'tanggal' => $tanggal, 'dari' => $dari, 'sampai' => $sampai, 'bulan' => $bulan]) }}" class="bg-slate-700 hover:bg-indigo-600 text-white text-[11px] font-semibold px-3 py-1.5 rounded-lg">Edit</a> <form method="POST" action="{{ route('laporan.hapus', $r->id) }}" class="inline del-form" data-nama="{{ $r->siswa->nama ?? $r->nisn }} {{ $r->tanggal }}">@csrf @method('DELETE')<button title="Hapus data {{ $r->siswa->nama ?? $r->nisn }}" class="inline-flex items-center justify-center px-2.5 h-8 rounded-lg bg-rose-600/20 border border-rose-500/40 text-rose-300 hover:bg-rose-600 hover:text-white transition-all text-[11px] font-semibold">Hapus</button></form></td></tr>@empty<tr><td colspan="9" class="py-8 text-center text-slate-500">Tidak ada absensi tanggal ini.</td></tr>@endforelse</tbody>
  </table></div>
  <div class="mt-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs text-slate-400">
    <span>{{ $rows->total() > 0 ? 'Menampilkan '.$rows->firstItem().'–'.$rows->lastItem().' dari '.$rows->total().' data' : 'Tidak ada data' }}</span>
    @include('partials.paginasi', ['pg' => $rows])
  </div>
  @if ($mode === 'harian')
  <div class="mt-6 rounded-xl border border-rose-500/30 overflow-hidden">
    <div class="bg-rose-500/10 px-4 py-3 flex flex-col gap-3">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <span class="text-xs font-semibold text-rose-300">Belum Absen ({{ $belum->total() }}) — tanpa baris absensi tanggal {{ $tanggal }}</span>
        <form method="GET" class="flex items-center gap-2 w-full sm:w-auto">
          @foreach (['mode' => $mode, 'tanggal' => $tanggal, 'dari' => $dari, 'sampai' => $sampai, 'bulan' => $bulan, 'cari' => $cari] as $n => $v)
          <input type="hidden" name="{{ $n }}" value="{{ $v }}">
          @endforeach
          <input type="search" name="cari_belum" value="{{ $cariBelum }}" placeholder="Cari nama / NISN / RFID / kelas..." class="w-full sm:w-56 bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">
          <button class="bg-rose-600 hover:bg-rose-500 text-white text-xs font-semibold px-3 py-2 rounded-xl whitespace-nowrap">Cari</button>
        </form>
      </div>
      <form id="bulk-tandai-form" method="POST" action="{{ route('laporan.tandai-bulk') }}" class="flex flex-col sm:flex-row sm:items-center gap-2 bg-slate-900/50 border border-rose-500/20 rounded-xl px-3 py-2 text-xs">
        @csrf
        <input type="hidden" name="tanggal" value="{{ $tanggal }}">
        <label class="flex items-center gap-2 text-slate-300 font-semibold whitespace-nowrap cursor-pointer"><input type="checkbox" id="check-all-belum" class="w-4 h-4 accent-indigo-600 rounded"> Pilih semua</label>
        <span id="bulk-belum-count" class="text-[11px] text-slate-500">Pilih siswa via checklist</span>
        <div class="sm:ml-auto flex items-center gap-2">
          <select name="status_masuk" required class="bg-slate-900 border border-slate-700 rounded-lg px-2 py-1.5 text-white text-xs">
            @foreach (['Di Sekolah' => 'Hadir di Sekolah', 'Izin' => 'Izin', 'Sakit' => 'Sakit', 'Tanpa Keterangan' => 'Tanpa Keterangan'] as $val => $label)
            <option value="{{ $val }}">{{ $label }}</option>
            @endforeach
          </select>
          <button type="submit" id="btn-bulk-tandai" class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold px-4 py-2 rounded-xl">Tandai (<span id="bulk-tandai-n">0</span>)</button>
        </div>
      </form>
    </div>
    <div class="overflow-x-auto"><table class="w-full text-left text-xs border-collapse">
    <thead><tr class="bg-slate-900/90 text-slate-300 border-b border-slate-700 font-semibold"><th class="py-2.5 px-4 w-10"><input type="checkbox" id="check-all-belum-head" class="w-4 h-4 accent-indigo-600 rounded"></th><th class="py-2.5 px-4">No</th><th class="py-2.5 px-4">Siswa</th><th class="py-2.5 px-4">NISN</th><th class="py-2.5 px-4">Kelas</th><th class="py-2.5 px-4 text-center">Aksi</th></tr></thead>
    <tbody class="divide-y divide-slate-700/60 text-slate-300">@forelse ($belum as $i => $s)<tr><td class="py-2.5 px-4"><input type="checkbox" name="nisn[]" value="{{ $s->nisn }}" form="bulk-tandai-form" class="belum-check w-4 h-4 accent-indigo-600 rounded cursor-pointer"></td><td class="py-2.5 px-4 font-mono text-slate-400">{{ $belum->firstItem() + $i }}</td><td class="py-2.5 px-4 font-semibold text-white">{{ $s->nama }}</td><td class="py-2.5 px-4 font-mono">{{ $s->nisn }}</td><td class="py-2.5 px-4">{{ $s->kelas->nama ?? '-' }}</td><td class="py-2.5 px-4 text-center"><form method="POST" action="{{ route('laporan.tandai') }}" class="flex items-center justify-center gap-1.5">@csrf<input type="hidden" name="nisn" value="{{ $s->nisn }}"><input type="hidden" name="tanggal" value="{{ $tanggal }}"><select name="status_masuk" required class="bg-slate-900 border border-slate-700 rounded-lg px-2 py-1.5 text-white text-xs">@foreach (['Di Sekolah' => 'Hadir di Sekolah', 'Izin' => 'Izin', 'Sakit' => 'Sakit', 'Tanpa Keterangan' => 'Tanpa Keterangan'] as $val => $label)<option value="{{ $val }}">{{ $label }}</option>@endforeach</select><button class="bg-slate-700 hover:bg-indigo-600 text-white text-[11px] font-semibold px-3 py-1.5 rounded-lg">Tandai</button></form></td></tr>@empty<tr><td colspan="6" class="py-6 text-center text-slate-500">Semua siswa sudah ada baris absensi.</td></tr>@endforelse</tbody>
  </table></div>
  <div class="px-4 py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs text-slate-400 border-t border-rose-500/20">
    <span>{{ $belum->total() > 0 ? 'Menampilkan '.$belum->firstItem().'–'.$belum->lastItem().' dari '.$belum->total().' data' : 'Tidak ada data' }}</span>
    @include('partials.paginasi', ['pg' => $belum])
  </div></div>
  @endif
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Konfirmasi hapus per-baris (fallback native bila CDN Swal diblokir hosting).
(function () {
  function ask(title, text) {
    if (window.Swal && Swal.fire) return Swal.fire({title: title, text: text, icon: 'warning', showCancelButton: true, confirmButtonColor: '#e11d48', cancelButtonColor: '#475569', confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal', background: '#1e293b', color: '#f1f5f9'});
    return Promise.resolve({isConfirmed: window.confirm(title + '\n' + (text || ''))});
  }
  document.querySelectorAll('.del-form').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      e.preventDefault();
      ask('Hapus data ' + (f.dataset.nama || 'absensi ini') + '?', 'Baris absensi ikut terhapus & tidak bisa dibatalkan.').then(function (r) { if (r.isConfirmed) f.submit(); });
    });
  });
})();
</script>
<script>
// Bulk checklist: form submit native (tetap jalan walau fetch/CDN gagal).
// Tombol SENGAJA selalu aktif; validasi kosong ditangani JS + server.
(function () {
  function initBulk() {
    var form = document.getElementById('bulk-form');
    if (!form) return;
    var countEl = document.getElementById('bulk-count');
    var delN = document.getElementById('bulk-del-n');
    var all1 = document.getElementById('check-all');
    var all2 = document.getElementById('check-all-head');
    function rows() { return Array.prototype.slice.call(document.querySelectorAll('.row-check')); }
    function selected() { return rows().filter(function (c) { return c.checked; }); }
    function refresh() {
      var n = selected().length;
      if (countEl) countEl.textContent = n > 0 ? n + ' dipilih' : 'Pilih data via checklist';
      if (delN) delN.textContent = n;
      var list = rows();
      var all = n > 0 && list.length > 0 && n === list.length;
      if (all1 && all1.checked !== all) all1.checked = all;
      if (all2 && all2.checked !== all) all2.checked = all;
    }
    function setAll(v) { rows().forEach(function (c) { c.checked = !!v; }); refresh(); }
    function ask(title, text) {
      if (window.Swal && Swal.fire) return Swal.fire({title: title, text: text, icon: 'warning', showCancelButton: true, confirmButtonColor: '#e11d48', cancelButtonColor: '#475569', confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal', background: '#1e293b', color: '#f1f5f9'});
      return Promise.resolve({isConfirmed: window.confirm(title + '\n' + (text || ''))});
    }
    function info(title) {
      if (window.Swal && Swal.fire) return Swal.fire({title: title, icon: 'warning', background: '#1e293b', color: '#f1f5f9'});
      window.alert(title);
      return Promise.resolve();
    }
    document.addEventListener('change', function (e) {
      var t = e.target;
      if (!t || !t.classList) return;
      if (t.classList.contains('row-check')) refresh();
      else if (t.id === 'check-all' || t.id === 'check-all-head') setAll(t.checked);
    });
    document.addEventListener('click', function (e) {
      var t = e.target;
      if (!t || !t.classList) return;
      if (t.classList.contains('row-check') || t.id === 'check-all' || t.id === 'check-all-head') setTimeout(refresh, 0);
    });
    form.addEventListener('submit', function (e) {
      var ids = selected();
      if (!ids.length) { e.preventDefault(); info('Centang minimal 1 data dulu.'); return; }
      e.preventDefault();
      ask('Hapus ' + ids.length + ' data absensi?', 'Baris absensi ikut terhapus & tidak bisa dibatalkan.').then(function (r) { if (r.isConfirmed) form.submit(); });
    });
    refresh();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initBulk);
  else initBulk();
})();
</script>
<script>
// Checklist tabel Belum Absen: tandai banyak siswa sekaligus.
(function () {
  function init() {
    var form = document.getElementById('bulk-tandai-form');
    if (!form) return;
    var countEl = document.getElementById('bulk-belum-count');
    var btnN = document.getElementById('bulk-tandai-n');
    var all1 = document.getElementById('check-all-belum');
    var all2 = document.getElementById('check-all-belum-head');
    function rows() { return Array.prototype.slice.call(document.querySelectorAll('.belum-check')); }
    function selected() { return rows().filter(function (c) { return c.checked; }); }
    function refresh() {
      var n = selected().length;
      if (countEl) countEl.textContent = n > 0 ? n + ' dipilih' : 'Pilih siswa via checklist';
      if (btnN) btnN.textContent = n;
      var list = rows();
      var all = n > 0 && list.length > 0 && n === list.length;
      if (all1 && all1.checked !== all) all1.checked = all;
      if (all2 && all2.checked !== all) all2.checked = all;
    }
    function setAll(v) { rows().forEach(function (c) { c.checked = !!v; }); refresh(); }
    function ask(title, text) {
      if (window.Swal && Swal.fire) return Swal.fire({title: title, text: text, icon: 'question', showCancelButton: true, confirmButtonColor: '#059669', cancelButtonColor: '#475569', confirmButtonText: 'Ya, tandai', cancelButtonText: 'Batal', background: '#1e293b', color: '#f1f5f9'});
      return Promise.resolve({isConfirmed: window.confirm(title + '\n' + (text || ''))});
    }
    function info(title) {
      if (window.Swal && Swal.fire) return Swal.fire({title: title, icon: 'warning', background: '#1e293b', color: '#f1f5f9'});
      window.alert(title);
      return Promise.resolve();
    }
    document.addEventListener('change', function (e) {
      var t = e.target;
      if (!t || !t.classList) return;
      if (t.classList.contains('belum-check')) refresh();
      else if (t.id === 'check-all-belum' || t.id === 'check-all-belum-head') setAll(t.checked);
    });
    document.addEventListener('click', function (e) {
      var t = e.target;
      if (!t || !t.classList) return;
      if (t.classList.contains('belum-check') || t.id === 'check-all-belum' || t.id === 'check-all-belum-head') setTimeout(refresh, 0);
    });
    form.addEventListener('submit', function (e) {
      var list = selected();
      if (!list.length) { e.preventDefault(); info('Centang minimal 1 siswa dulu.'); return; }
      e.preventDefault();
      var status = form.querySelector('[name="status_masuk"]');
      var label = status ? status.value : '';
      ask('Tandai ' + list.length + ' siswa sebagai ' + label + '?', 'Status absensi siswa terpilih akan diubah dan notifikasi WhatsApp masuk antrean.').then(function (r) { if (r.isConfirmed) form.submit(); });
    });
    refresh();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
</script>
@endsection
