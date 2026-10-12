@extends('layouts.admin')
@section('title', 'Data Kelas')
@section('content')
@if (session('ok'))<div class="bg-emerald-500/15 border border-emerald-500/40 text-emerald-300 text-xs rounded-xl px-4 py-3">{{ session('ok') }}</div>@endif
@if ($errors->any())<div class="bg-rose-500/15 border border-rose-500/40 text-rose-300 text-xs rounded-xl px-4 py-3">{{ $errors->first() }}</div>@endif
<div class="bg-slate-800/80 border border-slate-700 rounded-2xl p-5">
  <form method="POST" action="{{ route('kelas.store') }}" class="flex flex-col sm:flex-row gap-2 mb-4">@csrf
    <input name="nama" value="{{ old('nama') }}" required maxlength="30" placeholder="Nama kelas baru cth XII RPL 1" class="flex-1 bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">
    <button class="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold px-4 py-2 rounded-xl">+ Tambah Kelas</button>
  </form>
  <form method="GET" class="flex flex-col sm:flex-row gap-2 mb-4">
    <input name="q" value="{{ request('q') }}" placeholder="Cari kelas..." class="flex-1 bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">
    <button class="bg-slate-700 hover:bg-indigo-600 text-white text-sm font-semibold px-4 py-2 rounded-xl">Cari</button>
  </form>
  <div class="overflow-x-auto rounded-xl border border-slate-700/80"><table class="w-full text-left text-xs border-collapse">
    <thead><tr class="bg-slate-900/90 text-slate-300 border-b border-slate-700 font-semibold"><th class="py-3.5 px-4">No</th><th class="py-3.5 px-4">Nama Kelas</th><th class="py-3.5 px-4 text-center">Jml Siswa</th><th class="py-3.5 px-4 text-center">Aksi</th></tr></thead>
    <tbody class="divide-y divide-slate-700/60 text-slate-300">@forelse ($kelas as $i => $k)<tr class="hover:bg-slate-700/30 transition-colors"><td class="py-3.5 px-4 font-mono text-slate-400">{{ $kelas->firstItem() + $i }}</td><td class="py-3.5 px-4"><span class="bg-slate-700/60 px-2.5 py-1 rounded-md border border-slate-600/80 font-semibold text-white">{{ $k->nama }}</span></td><td class="py-3.5 px-4 text-center font-mono text-indigo-300">{{ $k->siswas_count }}</td>
    <td class="py-3.5 px-4 text-center whitespace-nowrap"><form method="POST" action="{{ route('kelas.update', $k) }}" class="inline edit-form" data-nama="{{ $k->nama }}">@csrf @method('PUT')<button title="Edit {{ $k->nama }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-700 hover:bg-indigo-600 text-slate-200 hover:text-white transition-all mr-1"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg></button></form><form method="POST" action="{{ route('kelas.destroy', $k) }}" class="inline del-form" data-nama="{{ $k->nama }}">@csrf @method('DELETE')<button title="Hapus {{ $k->nama }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-rose-600/20 border border-rose-500/40 text-rose-300 hover:bg-rose-600 hover:text-white transition-all"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg></button></form></td></tr>@empty<tr><td colspan="4" class="py-8 text-center text-slate-500">Belum ada kelas. Tambah dulu.</td></tr>@endforelse</tbody>
  </table></div>
  <div class="flex flex-col sm:flex-row items-center justify-between gap-2 mt-4 text-xs text-slate-400">
    <span>Menampilkan {{ $kelas->firstItem() ?? 0 }}-{{ $kelas->lastItem() ?? 0 }} dari {{ $kelas->total() }} data • Hal {{ $kelas->currentPage() }}/{{ $kelas->lastPage() }}</span>
    <div class="flex items-center gap-1">
      @if ($kelas->onFirstPage())<span class="px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-700 opacity-40">‹ Prev</span>@else<a href="{{ $kelas->previousPageUrl() }}" class="px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-700 hover:border-indigo-500">‹ Prev</a>@endif
      @foreach ($kelas->getUrlRange(max(1, $kelas->currentPage() - 2), min($kelas->lastPage(), $kelas->currentPage() + 2)) as $p => $u)@if ($p == $kelas->currentPage())<span class="px-3 py-1.5 rounded-lg border bg-indigo-600 text-white border-indigo-600">{{ $p }}</span>@else<a href="{{ $u }}" class="px-3 py-1.5 rounded-lg border bg-slate-900 border-slate-700 hover:border-indigo-500">{{ $p }}</a>@endif @endforeach
      @if ($kelas->hasMorePages())<a href="{{ $kelas->nextPageUrl() }}" class="px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-700 hover:border-indigo-500">Next ›</a>@else<span class="px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-700 opacity-40">Next ›</span>@endif
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.querySelectorAll('.del-form').forEach(f => f.addEventListener('submit', e => {
  e.preventDefault();
  const nama = f.dataset.nama || 'kelas ini';
  Swal.fire({title: 'Hapus ' + nama + '?', text: 'Kelas dipakai siswa tidak bisa dihapus.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#e11d48', cancelButtonColor: '#475569', confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal', background: '#1e293b', color: '#f1f5f9'}).then(r => { if (r.isConfirmed) f.submit(); });
}));
document.querySelectorAll('.edit-form').forEach(f => f.addEventListener('submit', e => {
  e.preventDefault();
  const nama = f.dataset.nama || '';
  Swal.fire({title: 'Edit kelas ' + nama, input: 'text', inputValue: nama, showCancelButton: true, confirmButtonColor: '#4f46e5', cancelButtonColor: '#475569', confirmButtonText: 'Simpan', cancelButtonText: 'Batal', background: '#1e293b', color: '#f1f5f9', inputValidator: v => !v.trim() ? 'Nama wajib diisi.' : undefined}).then(r => {
    if (r.isConfirmed) {
      const inp = document.createElement('input');
      inp.type = 'hidden'; inp.name = 'nama'; inp.value = r.value.trim().slice(0, 30);
      f.appendChild(inp); f.submit();
    }
  });
}));
@if (request('need'))
Swal.fire({title: 'Belum ada kelas!', text: 'Buat kelas dulu sebelum kelola siswa.', icon: 'info', confirmButtonColor: '#4f46e5', confirmButtonText: 'OK, buat kelas', background: '#1e293b', color: '#f1f5f9'});
@endif
</script>
@endsection