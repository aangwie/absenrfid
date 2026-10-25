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
    @if (request('per_page'))<input type="hidden" name="per_page" value="{{ request('per_page') }}">@endif
    @if (request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
    @if (request('dir'))<input type="hidden" name="dir" value="{{ request('dir') }}">@endif
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
  @php
    $sortAktif = (string) request('sort', 'kelas');
    if (! in_array($sortAktif, ['nama', 'nisn', 'rfid_tag', 'tempat_lahir', 'tanggal_lahir', 'tahun_masuk', 'kelas', 'no_hp_ortu', 'gender'], true)) {
        $sortAktif = 'kelas';
    }
    $sortDir = strtolower((string) request('dir', 'asc')) === 'desc' ? 'desc' : 'asc';
    $sortUrl = function (string $key) use ($sortAktif, $sortDir) {
        return route('siswa.index', array_filter([
            'q' => request('q'),
            'per_page' => request('per_page'),
            'sort' => $key,
            'dir' => $sortAktif === $key && $sortDir === 'asc' ? 'desc' : 'asc',
        ], fn ($v) => $v !== null && $v !== ''));
    };
    $sortAria = fn (string $key) => $sortAktif === $key ? ($sortDir === 'asc' ? 'ascending' : 'descending') : 'none';
    $sortArrow = function (string $key) use ($sortAktif, $sortDir) {
        if ($sortAktif === $key) {
            return $sortDir === 'asc'
                ? '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3 text-indigo-400"><path d="M10 5l4 5H6l4-5z"/></svg>'
                : '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3 text-indigo-400"><path d="M10 15l-4-5h8l-4 5z"/></svg>';
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3 text-slate-600 group-hover:text-slate-400"><path d="M10 3l3 4H7l3-4z"/><path d="M10 17l-3-4h6l-3 4z"/></svg>';
    };
  @endphp
  <div class="overflow-x-auto rounded-xl border border-slate-700/80"><table class="w-full text-left text-xs border-collapse">
    <thead><tr class="bg-slate-900/90 text-slate-300 border-b border-slate-700 font-semibold">
      <th class="py-3.5 px-4 w-10"><input type="checkbox" id="check-all-head" class="w-4 h-4 accent-indigo-600 rounded"></th>
      <th class="py-3.5 px-4">Foto</th>
      <th class="py-3.5 px-4" aria-sort="{{ $sortAria('nama') }}"><a href="{{ $sortUrl('nama') }}" class="group inline-flex items-center gap-1 hover:text-white transition-colors">Siswa {!! $sortArrow('nama') !!}</a></th>
      <th class="py-3.5 px-4" aria-sort="{{ $sortAria('nisn') }}"><a href="{{ $sortUrl('nisn') }}" class="group inline-flex items-center gap-1 hover:text-white transition-colors">NISN / RFID {!! $sortArrow('nisn') !!}</a><div class="text-[9px] font-normal text-slate-500 normal-case">klik 2x untuk edit</div></th>
      <th class="py-3.5 px-4" aria-sort="{{ $sortAria('tanggal_lahir') }}"><a href="{{ $sortUrl('tanggal_lahir') }}" class="group inline-flex items-center gap-1 hover:text-white transition-colors">TTL / Thn Masuk {!! $sortArrow('tanggal_lahir') !!}</a></th>
      <th class="py-3.5 px-4" aria-sort="{{ $sortAria('kelas') }}"><a href="{{ $sortUrl('kelas') }}" class="group inline-flex items-center gap-1 hover:text-white transition-colors">Kelas {!! $sortArrow('kelas') !!}</a></th>
      <th class="py-3.5 px-4" aria-sort="{{ $sortAria('no_hp_ortu') }}"><a href="{{ $sortUrl('no_hp_ortu') }}" class="group inline-flex items-center gap-1 hover:text-white transition-colors">HP Ortu/Wali {!! $sortArrow('no_hp_ortu') !!}</a></th>
      <th class="py-3.5 px-4 text-center" aria-sort="{{ $sortAria('gender') }}"><a href="{{ $sortUrl('gender') }}" class="group inline-flex items-center justify-center gap-1 hover:text-white transition-colors">Gender {!! $sortArrow('gender') !!}</a></th>
      <th class="py-3.5 px-4 text-center">Aksi</th>
    </tr></thead>
    <tbody class="divide-y divide-slate-700/60 text-slate-300">@forelse ($siswas as $s)<tr class="hover:bg-slate-700/30 transition-colors"><td class="py-3.5 px-4"><input type="checkbox" name="ids[]" value="{{ $s->id }}" form="bulk-form" class="row-check w-4 h-4 accent-indigo-600 rounded cursor-pointer"></td>
        <td class="py-3 px-4">
          @if ($s->foto_url)<img src="{{ $s->foto_url }}" alt="Foto {{ $s->nama }}" class="h-14 w-11 object-cover rounded-md border border-slate-600 bg-white/10">
          @else<span class="inline-flex h-14 w-11 items-center justify-center rounded-md border border-dashed border-slate-600 text-[10px] text-slate-500 text-center">Belum<br>ada</span>@endif
        </td><td class="py-3.5 px-4"><div class="font-semibold text-white text-sm">{{ $s->nama }}</div><div class="text-[11px] text-slate-400">Gender: {{ $s->gender === 'P' ? 'Perempuan' : 'Laki-laki' }}</div></td><td class="py-3.5 px-4 font-mono"><div class="identitas-cell group cursor-pointer rounded-lg -m-1 p-1 transition-colors hover:bg-indigo-500/10 focus:outline-none focus:ring-2 focus:ring-indigo-500/60" role="button" tabindex="0" title="Klik 2x untuk edit NISN / RFID" data-id="{{ $s->id }}" data-nama="{{ $s->nama }}" data-nisn="{{ $s->nisn }}" data-rfid="{{ $s->rfid_tag }}" data-action="{{ route('siswa.identitas', $s) }}"><div class="text-slate-200">NISN: <span class="js-nisn">{{ $s->nisn }}</span></div><span class="js-rfid inline-block text-[10px] px-2 py-0.5 rounded border mt-1 font-semibold bg-slate-900 text-slate-400 border-slate-700 group-hover:border-indigo-500/60 group-hover:text-indigo-300">RFID: {{ $s->rfid_tag }}</span></div></td><td class="py-3.5 px-4 text-[11px]"><div class="text-slate-200">{{ $s->tempat_lahir && $s->tanggal_lahir ? $s->tempat_lahir.', '.$s->tanggal_lahir->format('Y-m-d') : ($s->tempat_lahir ?? ($s->tanggal_lahir ? $s->tanggal_lahir->format('Y-m-d') : '-')) }}</div><div class="text-slate-400 mt-0.5">Masuk: {{ $s->tahun_masuk ?? '-' }}</div></td><td class="py-3.5 px-4"><span class="bg-slate-700/60 px-2.5 py-1 rounded-md border border-slate-600/80 font-medium">{{ $s->kelas->nama ?? '-' }}</span></td><td class="py-3.5 px-4 font-mono text-slate-200">{{ $s->no_hp_ortu ?? '-' }}</td><td class="py-3.5 px-4 text-center text-slate-300">{{ $s->gender }}</td>
    <td class="py-3.5 px-4 text-center whitespace-nowrap"><a href="{{ route('siswa.edit', $s) }}" title="Edit {{ $s->nama }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-700 hover:bg-indigo-600 text-slate-200 hover:text-white transition-all mr-1"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg></a><form method="POST" action="{{ route('siswa.destroy', $s) }}" class="inline del-form" data-nama="{{ $s->nama }}">@csrf @method('DELETE')<button title="Hapus {{ $s->nama }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-rose-600/20 border border-rose-500/40 text-rose-300 hover:bg-rose-600 hover:text-white transition-all"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg></button></form></td></tr>@empty<tr><td colspan="9" class="py-8 text-center text-slate-500">Tidak ada data.</td></tr>@endforelse</tbody>
  </table></div>
  <div class="flex flex-col sm:flex-row items-center justify-between gap-3 mt-4 text-xs text-slate-400">
    <div class="flex flex-wrap items-center justify-center gap-x-3 gap-y-2">
      <span>{{ $siswas->total() > 0 ? 'Menampilkan '.$siswas->firstItem().'–'.$siswas->lastItem().' dari '.$siswas->total().' data' : 'Tidak ada data' }}</span>
      <form method="GET" class="flex items-center gap-1.5">
        @if (request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
        @if (request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
        @if (request('dir'))<input type="hidden" name="dir" value="{{ request('dir') }}">@endif
        <label for="per-page" class="text-[11px] text-slate-500">Tampilkan</label>
        <select id="per-page" name="per_page" onchange="this.form.submit()" class="bg-slate-900 border border-slate-700 rounded-lg px-2 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 cursor-pointer">
          @foreach ([5, 10, 15, 20, 50, 100] as $n)
          <option value="{{ $n }}" @selected($siswas->perPage() === $n)>{{ $n }}</option>
          @endforeach
        </select>
        <span class="text-[11px] text-slate-500">siswa</span>
      </form>
    </div>
    @include('partials.paginasi', ['pg' => $siswas])
  </div>
</div>
<div id="modal-identitas" class="hidden fixed inset-0 z-50 items-center justify-center p-4">
  <div class="absolute inset-0 bg-slate-950/75 backdrop-blur-sm" data-identitas-close></div>
  <div class="relative w-full max-w-md bg-slate-800 border border-slate-700 rounded-2xl shadow-2xl">
    <div class="flex items-center justify-between px-5 pt-4 pb-3 border-b border-slate-700/70">
      <h3 class="text-sm font-semibold text-white">Edit NISN / RFID</h3>
      <button type="button" data-identitas-close aria-label="Tutup" class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-slate-400 hover:text-white hover:bg-slate-700 transition-colors">&times;</button>
    </div>
    <form id="form-identitas" class="p-5 flex flex-col gap-3 text-sm">@csrf @method('PATCH')
      <div>
        <label class="block text-xs text-slate-300 mb-1">Nama Siswa</label>
        <input id="identitas-nama" type="text" readonly tabindex="-1" class="w-full bg-slate-900/60 border border-slate-700 rounded-xl px-3 py-2.5 text-slate-400 cursor-not-allowed focus:outline-none">
      </div>
      <div>
        <label for="identitas-rfid" class="block text-xs text-slate-300 mb-1">RFID Tag</label>
        <input id="identitas-rfid" name="rfid_tag" type="text" required autocomplete="off" maxlength="50" class="w-full bg-slate-900 border border-indigo-500/60 rounded-xl px-3 py-2.5 text-indigo-300 font-mono font-bold focus:outline-none focus:border-indigo-400">
      </div>
      <div>
        <label for="identitas-nisn" class="block text-xs text-slate-300 mb-1">NISN (angka, maks 10 digit)</label>
        <input id="identitas-nisn" name="nisn" type="text" required inputmode="numeric" pattern="[0-9]{1,10}" maxlength="10" title="NISN hanya angka, maks 10 digit" autocomplete="off" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-white font-mono focus:outline-none focus:border-indigo-500">
      </div>
      <p id="identitas-error" class="hidden text-[11px] text-rose-300 bg-rose-500/10 border border-rose-500/30 rounded-lg px-3 py-2"></p>
      <div class="flex gap-2 pt-1">
        <button type="button" data-identitas-close class="px-4 py-2.5 rounded-xl bg-slate-700 hover:bg-slate-600 text-slate-300 text-sm">Batal</button>
        <button type="submit" id="identitas-save" class="flex-1 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-4 py-2.5 rounded-xl">Simpan</button>
      </div>
    </form>
  </div>
</div>
<script>
(function () {
  var modal = document.getElementById('modal-identitas');
  if (!modal) return;
  var form = document.getElementById('form-identitas');
  var nama = document.getElementById('identitas-nama');
  var rfid = document.getElementById('identitas-rfid');
  var nisn = document.getElementById('identitas-nisn');
  var err = document.getElementById('identitas-error');
  var save = document.getElementById('identitas-save');
  var csrf = document.querySelector('meta[name="csrf-token"]');
  var cell = null;
  var lastFocused = null;

  function setError(msg) {
    if (!msg) { err.textContent = ''; err.classList.add('hidden'); return; }
    err.textContent = msg;
    err.classList.remove('hidden');
  }

  function open(c) {
    cell = c;
    lastFocused = document.activeElement;
    nama.value = c.dataset.nama || '';
    rfid.value = c.dataset.rfid || '';
    nisn.value = c.dataset.nisn || '';
    form.action = c.dataset.action;
    setError('');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.classList.add('overflow-hidden');
    // Sesuai permintaan: kursor langsung fokus & menyeleksi isi RFID.
    setTimeout(function () { rfid.focus(); rfid.select(); }, 30);
  }

  function close() {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
    if (lastFocused && lastFocused.focus) lastFocused.focus();
    cell = null;
  }

  document.querySelectorAll('.identitas-cell').forEach(function (c) {
    c.addEventListener('dblclick', function () { open(c); });
    c.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(c); }
    });
  });

  modal.querySelectorAll('[data-identitas-close]').forEach(function (b) {
    b.addEventListener('click', close);
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !modal.classList.contains('hidden')) close();
  });

  rfid.addEventListener('input', function () { setError(''); });
  nisn.addEventListener('input', function () {
    nisn.value = nisn.value.replace(/[^0-9]/g, '').slice(0, 10);
    setError('');
  });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    if (!cell) return;
    save.disabled = true;
    save.classList.add('opacity-60');
    setError('');

    fetch(form.action, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrf ? csrf.content : '',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({ nisn: nisn.value.trim(), rfid_tag: rfid.value.trim() })
    }).then(function (r) {
      return r.json().catch(function () { return {}; }).then(function (j) {
        return { status: r.status, body: j };
      });
    }).then(function (res) {
      if (res.status >= 200 && res.status < 300) {
        var updated = cell;
        updated.dataset.nisn = res.body.nisn;
        updated.dataset.rfid = res.body.rfid_tag;
        updated.querySelector('.js-nisn').textContent = res.body.nisn;
        updated.querySelector('.js-rfid').textContent = 'RFID: ' + res.body.rfid_tag;
        updated.classList.add('ring-2', 'ring-emerald-400/70');
        setTimeout(function () { updated.classList.remove('ring-2', 'ring-emerald-400/70'); }, 1200);
        close();
        return;
      }
      var fields = res.body.errors || {};
      setError((fields.rfid_tag && fields.rfid_tag[0]) || (fields.nisn && fields.nisn[0]) || res.body.message || ('Gagal menyimpan (HTTP ' + res.status + ').'));
    }).catch(function () {
      setError('Jaringan gagal, coba lagi.');
    }).finally(function () {
      save.disabled = false;
      save.classList.remove('opacity-60');
    });
  });
})();
</script>
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

