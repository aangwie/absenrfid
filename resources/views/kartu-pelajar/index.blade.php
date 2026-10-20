@extends('layouts.admin')
@section('title', 'Kartu Pelajar')
@section('content')
@if (session('ok'))<div class="bg-emerald-500/15 border border-emerald-500/40 text-emerald-300 text-xs rounded-xl px-4 py-3">{{ session('ok') }}</div>@endif
@if ($errors->any())<div class="bg-rose-500/15 border border-rose-500/40 text-rose-300 text-xs rounded-xl px-4 py-3">{{ $errors->first() }}</div>@endif
<div class="bg-slate-800/80 border border-slate-700 rounded-2xl p-5">
  <div class="flex flex-col sm:flex-row sm:items-center gap-2 mb-1">
    <div><h2 class="text-base font-semibold text-white">Kartu Pelajar</h2><p class="text-[11px] text-slate-500">Centang siswa lalu klik Cetak — halaman cetak terbuka di tab baru, 1 siswa = 1 lembar A4 (depan kiri, belakang kanan).</p></div>
  </div>
  <form method="GET" class="flex flex-col sm:flex-row gap-2 my-4">
    <input name="q" value="{{ request('q') }}" placeholder="Cari nama / NISN / kelas..." class="flex-1 bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">
    <select name="kelas_id" class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
      <option value="">-- Semua kelas --</option>
      @foreach ($kelasList as $k)<option value="{{ $k->id }}" @selected(request('kelas_id')==$k->id)>{{ $k->nama }}</option>@endforeach
    </select>
    <button class="bg-slate-700 hover:bg-indigo-600 text-white text-sm font-semibold px-4 py-2 rounded-xl">Cari</button>
  </form>
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 mb-3">
  <form id="cetak-form" method="GET" action="{{ route('kartu-pelajar.cetak') }}" target="_blank" class="flex flex-col md:flex-row md:items-center gap-2 bg-slate-900/60 border border-slate-700 rounded-xl p-3">
    <label class="flex items-center gap-2 text-xs text-slate-300 font-semibold whitespace-nowrap cursor-pointer"><input type="checkbox" id="check-all" class="w-4 h-4 accent-indigo-600 rounded"> Pilih semua</label>
    <span id="bulk-count" class="text-[11px] text-slate-500">Pilih siswa via checklist</span>
    <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold px-4 py-2 rounded-xl md:ml-auto">🖨 Cetak Terpilih (<span id="bulk-n">0</span>)</button>
  </form>
  <div class="flex flex-col md:flex-row md:items-center gap-2 bg-slate-900/60 border border-slate-700 rounded-xl p-3">
    <span class="text-xs text-slate-300 font-semibold whitespace-nowrap">Unduh gambar:</span>
    <select id="ekspor-sisi" class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-500">
      <option value="depan">Halaman depan</option>
      <option value="belakang">Halaman belakang</option>
      <option value="keduanya" selected>Depan + belakang</option>
    </select>
    <button id="ekspor-btn" type="button" class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold px-4 py-2 rounded-xl md:ml-auto">⬇ Unduh Gambar (<span id="ekspor-n">0</span>)</button>
    <!--span class="text-[8px] text-slate-500">1 gambar → PNG langsung • banyak → ZIP</span-->
  </div>
  </div>
  <div class="overflow-x-auto rounded-xl border border-slate-700/80"><table class="w-full text-left text-xs border-collapse">
    <thead><tr class="bg-slate-900/90 text-slate-300 border-b border-slate-700 font-semibold"><th class="py-3.5 px-4 w-10"><input type="checkbox" id="check-all-head" class="w-4 h-4 accent-indigo-600 rounded"></th><th class="py-3.5 px-4">Foto</th><th class="py-3.5 px-4">Siswa</th><th class="py-3.5 px-4">TTL / Thn Masuk</th><th class="py-3.5 px-4">Kelas</th><th class="py-3.5 px-4 text-center">Aksi</th></tr></thead>
    <tbody class="divide-y divide-slate-700/60 text-slate-300">
    @forelse ($siswas as $s)
      <tr class="hover:bg-slate-700/30 transition-colors">
        <td class="py-3 px-4"><input type="checkbox" name="ids[]" value="{{ $s->id }}" form="cetak-form" class="row-check w-4 h-4 accent-indigo-600 rounded cursor-pointer"></td>
        <td class="py-3 px-4">
          @if ($s->foto_url)<img src="{{ $s->foto_url }}" alt="Foto" class="h-14 w-11 object-cover rounded-md border border-slate-600 bg-white/10">
          @else<span class="inline-flex h-14 w-11 items-center justify-center rounded-md border border-dashed border-slate-600 text-[10px] text-slate-500 text-center">Belum<br>ada</span>@endif
        </td>
        <td class="py-3 px-4"><div class="font-semibold text-white text-sm">{{ $s->nama }}</div><div class="font-mono text-[11px] text-slate-400">NISN: {{ $s->nisn }}</div></td>
        <td class="py-3 px-4 text-[11px]"><div class="text-slate-200">{{ $s->tempat_lahir && $s->tanggal_lahir ? $s->tempat_lahir.', '.$s->tanggal_lahir->format('Y-m-d') : ($s->tempat_lahir ?? ($s->tanggal_lahir ? $s->tanggal_lahir->format('Y-m-d') : '-')) }}</div><div class="text-slate-400 mt-0.5">Masuk: {{ $s->tahun_masuk ?? '-' }}</div></td>
        <td class="py-3 px-4"><span class="bg-slate-700/60 px-2.5 py-1 rounded-md border border-slate-600/80 font-medium">{{ $s->kelas->nama ?? '-' }}</span></td>
        <td class="py-3 px-4 text-center whitespace-nowrap">
          <a href="{{ route('kartu-pelajar.cetak', ['ids' => [$s->id]]) }}" target="_blank" rel="noopener" title="Cetak {{ $s->nama }}" class="inline-flex items-center gap-1 px-3 h-8 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-[11px] font-semibold mr-1">🖨 Cetak</a>
          <a href="{{ route('kartu-pelajar.ekspor-gambar', ['ids' => [$s->id], 'sisi' => 'keduanya']) }}" target="_blank" rel="noopener" title="Unduh gambar {{ $s->nama }} (PNG)" class="inline-flex items-center gap-1 px-3 h-8 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-[11px] font-semibold mr-1">⬇ PNG</a>
          <form method="POST" action="{{ route('kartu-pelajar.foto', $s) }}" enctype="multipart/form-data" class="inline">@csrf<label title="Upload pas foto 3x4 {{ $s->nama }} (maks 300 Kb, otomatis jadi WebP)" class="inline-flex items-center gap-1 px-3 h-8 rounded-lg bg-slate-700 hover:bg-slate-600 text-slate-200 text-[11px] font-semibold cursor-pointer">📷 Foto<input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp" class="hidden" onchange="this.form.submit()"></label></form>
        </td>
      </tr>
    @empty<tr><td colspan="6" class="py-8 text-center text-slate-500">Tidak ada data.</td></tr>@endforelse
    </tbody>
  </table></div>
  <div class="flex flex-col sm:flex-row items-center justify-between gap-2 mt-4 text-xs text-slate-400">
    <span>{{ $siswas->total() > 0 ? 'Menampilkan '.$siswas->firstItem().'–'.$siswas->lastItem().' dari '.$siswas->total().' data' : 'Tidak ada data' }}</span>
    <div class="flex items-center gap-1">
      <form method="GET" class="flex items-center gap-1">
        @if (request()->filled('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
        @if (request()->filled('kelas_id'))<input type="hidden" name="kelas_id" value="{{ request('kelas_id') }}">@endif
        <select name="per_page" onchange="this.form.submit()" title="Jumlah data per halaman" aria-label="Jumlah data per halaman" class="bg-slate-900 border border-slate-700 rounded-lg px-2 py-1.5 text-xs text-white focus:outline-none focus:border-indigo-500">
          @foreach ([10, 15, 20, 50, 100] as $n)<option value="{{ $n }}" @selected($siswas->perPage() == $n)>{{ $n }}</option>@endforeach
        </select>
      </form>
      @include('partials.paginasi', ['pg' => $siswas])
    </div>
  </div>
  <p class="text-[11px] text-slate-500 mt-3">Logo, nama & alamat institusi diambil dari Pengaturan → Institusi. Pas foto bisa diupload lewat tombol 📷 Foto atau form Siswa (jpg/png/webp maks 300 Kb, otomatis dikonversi ke WebP dan disimpan di storage/app/public/foto). Foto kosong = tampil inisial nama di kartu.</p>
</div>
<script>
(function () {
  var form = document.getElementById('cetak-form');
  if (!form) return;
  var countEl = document.getElementById('bulk-count'), nEl = document.getElementById('bulk-n');
  var all1 = document.getElementById('check-all'), all2 = document.getElementById('check-all-head');
  function rows() { return Array.prototype.slice.call(document.querySelectorAll('.row-check')); }
  function selected() { return rows().filter(function (c) { return c.checked; }); }
  function refresh() {
    var n = selected().length, list = rows();
    if (countEl) countEl.textContent = n > 0 ? n + ' dipilih' : 'Pilih siswa via checklist';
    if (nEl) nEl.textContent = n;
    var all = n > 0 && list.length > 0 && n === list.length;
    if (all1 && all1.checked !== all) all1.checked = all;
    if (all2 && all2.checked !== all) all2.checked = all;
  }
  function setAll(v) { rows().forEach(function (c) { c.checked = !!v; }); refresh(); }
  document.addEventListener('change', function (e) {
    var t = e.target;
    if (!t || !t.classList) return;
    if (t.classList.contains('row-check')) refresh();
    else if (t.id === 'check-all' || t.id === 'check-all-head') setAll(t.checked);
  });
  form.addEventListener('submit', function (e) {
    if (!selected().length) { e.preventDefault(); alert('Centang minimal 1 siswa dulu.'); }
  });
  refresh();
})();
</script>
<script>
(function () {
  var btn = document.getElementById('ekspor-btn');
  var sisi = document.getElementById('ekspor-sisi');
  var nEl = document.getElementById('ekspor-n');
  if (!btn) return;
  function selected() {
    return Array.prototype.slice.call(document.querySelectorAll('.row-check')).filter(function (c) { return c.checked; });
  }
  document.addEventListener('change', function (e) {
    var t = e.target;
    if (!t || !t.classList) return;
    if (t.classList.contains('row-check') || t.id === 'check-all' || t.id === 'check-all-head') {
      if (nEl) nEl.textContent = selected().length;
    }
  });
  if (nEl) nEl.textContent = selected().length;
  btn.addEventListener('click', function () {
    var rows = selected();
    if (!rows.length) { alert('Centang minimal 1 siswa dulu.'); return; }
    var params = rows.map(function (c) { return 'ids[]=' + encodeURIComponent(c.value); }).join('&');
    params += '&sisi=' + encodeURIComponent(sisi ? sisi.value : 'keduanya');
    window.open('{{ route('kartu-pelajar.ekspor-gambar') }}?' + params, '_blank', 'noopener');
  });
})();
</script>
@endsection
