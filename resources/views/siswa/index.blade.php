@extends('layouts.admin')
@section('title', 'Data Siswa')
@section('content')
@if (session('ok'))<div class="bg-emerald-500/15 border border-emerald-500/40 text-emerald-300 text-xs rounded-xl px-4 py-3">{{ session('ok') }}</div>@endif
@if ($errors->any())<div class="bg-rose-500/15 border border-rose-500/40 text-rose-300 text-xs rounded-xl px-4 py-3">{{ $errors->first() }}</div>@endif
@if (session('fail'))
<div class="bg-amber-500/10 border border-amber-500/40 text-amber-200 text-xs rounded-xl px-4 py-3"><ul class="list-disc pl-4">@foreach (session('fail') as $f)<li>{{ $f }}</li>@endforeach</ul></div>
@endif
<div class="bg-slate-800/80 border border-slate-700 rounded-2xl p-5">
  <div class="flex flex-col sm:flex-row gap-2 mb-4">
    <a href="{{ route('siswa.create') }}" class="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold px-4 py-2 rounded-xl text-center">+ Tambah Siswa</a>
    <a href="{{ route('siswa.template') }}" class="border border-slate-600 hover:border-indigo-500 text-slate-200 text-sm font-semibold px-4 py-2 rounded-xl text-center">⬇ Template .xlsx</a>
  </div>
  <form id="import-form" method="POST" action="{{ route('siswa.import') }}" enctype="multipart/form-data" class="flex flex-col sm:flex-row gap-2 mb-4 bg-slate-900/60 border border-dashed border-slate-600 rounded-xl p-3">@csrf
    <input id="import-file" type="file" name="file" accept=".xlsx" required class="flex-1 text-xs text-slate-300 file:mr-3 file:px-4 file:py-2 file:rounded-lg file:border-0 file:bg-slate-700 file:text-white">
    <button id="import-btn" class="bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold px-4 py-2 rounded-xl">Import .xlsx</button>
  </form>
  <div id="import-progress" class="hidden mb-4 bg-slate-900/60 border border-slate-700 rounded-xl p-3">
    <div class="flex justify-between text-[11px] text-slate-400 mb-1.5"><span id="import-label">Menyiapkan...</span><span id="import-pct" class="font-mono">0%</span></div>
    <div class="h-2.5 bg-slate-950 rounded-full overflow-hidden"><div id="import-bar" class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-indigo-500 transition-all duration-200" style="width:0%"></div></div>
  </div>
  <script>
  (function () {
    var form = document.getElementById('import-form');
    if (!form) return;
    var wrap = document.getElementById('import-progress'), bar = document.getElementById('import-bar'),
        pct = document.getElementById('import-pct'), label = document.getElementById('import-label'),
        btn = document.getElementById('import-btn'), file = document.getElementById('import-file');
    function set(p, msg) { bar.style.width = p + '%'; pct.textContent = p + '%'; label.textContent = msg; }
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      if (!file.files.length) { wrap.classList.remove('hidden'); set(0, 'Pilih file .xlsx dulu.'); return; }
      btn.disabled = true; btn.classList.add('opacity-60'); wrap.classList.remove('hidden'); set(0, 'Mengunggah 0%...');
      var xhr = new XMLHttpRequest();
      xhr.open('POST', form.action, true);
      xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
      xhr.upload.onprogress = function (ev) {
        if (!ev.lengthComputable) return;
        var p = Math.round(ev.loaded / ev.total * 100);
        set(p, p < 100 ? 'Mengunggah ' + p + '%...' : 'File terkirim, memproses di server...');
      };
      xhr.onload = function () {
        if (xhr.status >= 200 && xhr.status < 400) { set(100, 'Selesai, memuat hasil...'); document.open(); document.write(xhr.responseText); document.close(); }
        else { set(0, 'Gagal import (HTTP ' + xhr.status + ').'); btn.disabled = false; btn.classList.remove('opacity-60'); }
      };
      xhr.onerror = function () { set(0, 'Jaringan gagal, coba lagi.'); btn.disabled = false; btn.classList.remove('opacity-60'); };
      xhr.send(new FormData(form));
    });
  })();
  </script>
  <p class="text-[11px] text-slate-500 mb-4">Kolom template: NISN | RFID_TAG | NAMA | KELAS | GENDER (L/P) | NO_HP_ORTU | TEMPAT_LAHIR | TANGGAL_LAHIR (YYYY-MM-DD) | TAHUN_MASUK. NISN dobel = update. File lama 6 kolom tetap bisa diimport.</p>
  <!--form method="GET" class="flex flex-col sm:flex-row gap-2 mb-4">
    <input name="q" value="{{ request('q') }}" placeholder="Cari nama / NISN / RFID / kelas..." class="flex-1 bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">
    <button class="bg-slate-700 hover:bg-slate-600 text-white text-sm font-semibold px-4 py-2 rounded-xl">Cari</button>
  </form-->
  <form method="GET" class="flex flex-col sm:flex-row gap-2 mb-4">
    <input name="q" value="{{ request('q') }}" placeholder="Cari nama / NISN / RFID / kelas..." class="flex-1 bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">
    <button class="bg-slate-700 hover:bg-indigo-600 text-white text-sm font-semibold px-4 py-2 rounded-xl">Cari</button>
  </form>
  <form id="bulk-form" method="POST" action="{{ route('siswa.bulk-pindah') }}" class="flex flex-col md:flex-row md:items-center gap-2 mb-3 bg-slate-900/60 border border-slate-700 rounded-xl p-3">@csrf
    <label class="flex items-center gap-2 text-xs text-slate-300 font-semibold whitespace-nowrap cursor-pointer"><input type="checkbox" id="check-all" class="w-4 h-4 accent-indigo-600 rounded"> Pilih semua</label>
    <span id="bulk-count" class="text-[11px] text-slate-500">Pilih siswa via checklist</span>
    <div class="flex flex-col sm:flex-row gap-2 md:ml-auto w-full md:w-auto">
      <select id="bulk-kelas" name="kelas_id" class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
        <option value="">-- Kelas tujuan --</option>
        @foreach ($kelasList as $k)<option value="{{ $k->id }}">{{ $k->nama }}</option>@endforeach
      </select>
      <button type="submit" id="btn-bulk-pindah" formaction="{{ route('siswa.bulk-pindah') }}" class="bg-amber-600 hover:bg-amber-500 text-white text-xs font-semibold px-4 py-2 rounded-xl">Pindah Kelas (<span id="bulk-move-n">0</span>)</button>
      <button type="submit" id="btn-bulk-hapus" formaction="{{ route('siswa.bulk-hapus') }}" class="bg-rose-600 hover:bg-rose-500 text-white text-xs font-semibold px-4 py-2 rounded-xl">Hapus (<span id="bulk-del-n">0</span>)</button>
    </div>
  </form>
  <div class="overflow-x-auto rounded-xl border border-slate-700/80"><table class="w-full text-left text-xs border-collapse">
    <thead><tr class="bg-slate-900/90 text-slate-300 border-b border-slate-700 font-semibold"><th class="py-3.5 px-4 w-10"><input type="checkbox" id="check-all-head" class="w-4 h-4 accent-indigo-600 rounded"></th><th class="py-3.5 px-4">Foto</th><th class="py-3.5 px-4">Siswa</th><th class="py-3.5 px-4">NISN / RFID</th><th class="py-3.5 px-4">TTL / Thn Masuk</th><th class="py-3.5 px-4">Kelas</th><th class="py-3.5 px-4">HP Ortu/Wali</th><th class="py-3.5 px-4 text-center">Gender</th><th class="py-3.5 px-4 text-center">Aksi</th></tr></thead>
    <tbody class="divide-y divide-slate-700/60 text-slate-300">@forelse ($siswas as $s)<tr class="hover:bg-slate-700/30 transition-colors"><td class="py-3.5 px-4"><input type="checkbox" name="ids[]" value="{{ $s->id }}" form="bulk-form" class="row-check w-4 h-4 accent-indigo-600 rounded cursor-pointer"></td>
        <td class="py-3 px-4">
          @if ($s->foto)<img src="{{ $s->foto }}" alt="Foto {{ $s->nama }}" class="h-14 w-11 object-cover rounded-md border border-slate-600 bg-white/10">
          @else<span class="inline-flex h-14 w-11 items-center justify-center rounded-md border border-dashed border-slate-600 text-[10px] text-slate-500 text-center">Belum<br>ada</span>@endif
        </td><td class="py-3.5 px-4"><div class="font-semibold text-white text-sm">{{ $s->nama }}</div><div class="text-[11px] text-slate-400">Gender: {{ $s->gender === 'P' ? 'Perempuan' : 'Laki-laki' }}</div></td><td class="py-3.5 px-4 font-mono"><div class="text-slate-200">NISN: {{ $s->nisn }}</div><span class="inline-block text-[10px] px-2 py-0.5 rounded border mt-1 font-semibold bg-slate-900 text-slate-400 border-slate-700">RFID: {{ $s->rfid_tag }}</span></td><td class="py-3.5 px-4 text-[11px]"><div class="text-slate-200">{{ $s->tempat_lahir && $s->tanggal_lahir ? $s->tempat_lahir.', '.$s->tanggal_lahir->format('Y-m-d') : ($s->tempat_lahir ?? ($s->tanggal_lahir ? $s->tanggal_lahir->format('Y-m-d') : '-')) }}</div><div class="text-slate-400 mt-0.5">Masuk: {{ $s->tahun_masuk ?? '-' }}</div></td><td class="py-3.5 px-4"><span class="bg-slate-700/60 px-2.5 py-1 rounded-md border border-slate-600/80 font-medium">{{ $s->kelas->nama ?? '-' }}</span></td><td class="py-3.5 px-4 font-mono text-slate-200">{{ $s->no_hp_ortu ?? '-' }}</td><td class="py-3.5 px-4 text-center text-slate-300">{{ $s->gender }}</td>
    <td class="py-3.5 px-4 text-center whitespace-nowrap"><a href="{{ route('siswa.edit', $s) }}" title="Edit {{ $s->nama }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-700 hover:bg-indigo-600 text-slate-200 hover:text-white transition-all mr-1"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg></a><form method="POST" action="{{ route('siswa.destroy', $s) }}" class="inline del-form" data-nama="{{ $s->nama }}">@csrf @method('DELETE')<button title="Hapus {{ $s->nama }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-rose-600/20 border border-rose-500/40 text-rose-300 hover:bg-rose-600 hover:text-white transition-all"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg></button></form></td></tr>@empty<tr><td colspan="9" class="py-8 text-center text-slate-500">Tidak ada data.</td></tr>@endforelse</tbody>
  </table></div>
  <div class="flex flex-col sm:flex-row items-center justify-between gap-2 mt-4 text-xs text-slate-400">
    <span>{{ $siswas->total() > 0 ? 'Menampilkan '.$siswas->firstItem().'–'.$siswas->lastItem().' dari '.$siswas->total().' data' : 'Tidak ada data' }}</span>
    @include('partials.paginasi', ['pg' => $siswas])
  </div>
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
      ask('Hapus ' + (f.dataset.nama || 'siswa ini') + '?', 'Data siswa beserta absensinya ikut terhapus.').then(function (r) { if (r.isConfirmed) f.submit(); });
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
    var moveN = document.getElementById('bulk-move-n');
    var selKelas = document.getElementById('bulk-kelas');
    var all1 = document.getElementById('check-all');
    var all2 = document.getElementById('check-all-head');
    function rows() { return Array.prototype.slice.call(document.querySelectorAll('.row-check')); }
    function selected() { return rows().filter(function (c) { return c.checked; }); }
    function refresh() {
      var n = selected().length;
      if (countEl) countEl.textContent = n > 0 ? n + ' dipilih' : 'Pilih siswa via checklist';
      if (delN) delN.textContent = n;
      if (moveN) moveN.textContent = n;
      var list = rows();
      var all = n > 0 && list.length > 0 && n === list.length;
      if (all1 && all1.checked !== all) all1.checked = all;
      if (all2 && all2.checked !== all) all2.checked = all;
    }
    function setAll(v) { rows().forEach(function (c) { c.checked = !!v; }); refresh(); }
    function ask(title, text, danger) {
      if (window.Swal && Swal.fire) return Swal.fire({title: title, text: text, icon: danger ? 'warning' : 'question', showCancelButton: true, confirmButtonColor: danger ? '#e11d48' : '#d97706', cancelButtonColor: '#475569', confirmButtonText: 'Ya, lanjut', cancelButtonText: 'Batal', background: '#1e293b', color: '#f1f5f9'});
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
    var lastBtn = null;
    form.querySelectorAll('button[type=submit]').forEach(function (b) {
      b.addEventListener('click', function () { lastBtn = b; });
    });
    form.addEventListener('submit', function (e) {
      var btn = (e.submitter || lastBtn || document.activeElement);
      var isHapus = btn && btn.id === 'btn-bulk-hapus';
      var ids = selected();
      if (!ids.length) { e.preventDefault(); info('Centang minimal 1 siswa dulu.'); return; }
      if (!isHapus) {
        if (selKelas && !selKelas.value) { e.preventDefault(); info('Pilih kelas tujuan dulu.'); return; }
      }
      e.preventDefault();
      var action = btn && btn.getAttribute('formaction');
      if (action) form.setAttribute('action', action);
      var namaKelas = (selKelas && selKelas.options[selKelas.selectedIndex]) ? selKelas.options[selKelas.selectedIndex].text : '';
      var msg = isHapus
        ? ask('Hapus ' + ids.length + ' siswa?', 'Data absensi ikut terhapus & tidak bisa dibatalkan.', true)
        : ask('Pindah ' + ids.length + ' siswa ke ' + namaKelas + '?', '', false);
      msg.then(function (r) { if (r.isConfirmed) form.submit(); });
    });
    refresh();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initBulk);
  else initBulk();
})();
</script>
@endsection

