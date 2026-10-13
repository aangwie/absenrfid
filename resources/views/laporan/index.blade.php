@extends('layouts.admin')
@section('title', 'Laporan Absensi')
@section('content')
@if (session('ok'))<div class="bg-emerald-500/15 border border-emerald-500/40 text-emerald-300 text-xs rounded-xl px-4 py-3 mb-3">{{ session('ok') }}</div>@endif
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
  <div class="overflow-x-auto rounded-xl border border-slate-700/80"><table class="w-full text-left text-xs border-collapse">
    <thead><tr class="bg-slate-900/90 text-slate-300 border-b border-slate-700 font-semibold"><th class="py-3.5 px-4">No</th><th class="py-3.5 px-4">Siswa</th><th class="py-3.5 px-4">NISN / RFID</th><th class="py-3.5 px-4">Kelas</th><th class="py-3.5 px-4 text-center">Masuk</th><th class="py-3.5 px-4 text-center">Pulang</th><th class="py-3.5 px-4 text-center">Status</th><th class="py-3.5 px-4 text-center">Aksi</th></tr></thead>
    <tbody class="divide-y divide-slate-700/60 text-slate-300">@forelse ($rows as $i => $r)<tr class="hover:bg-slate-700/30 transition-colors"><td class="py-3.5 px-4 font-mono text-slate-400">{{ $rows->firstItem() + $i }}</td><td class="py-3.5 px-4"><div class="font-semibold text-white text-sm">{{ $r->siswa->nama ?? $r->nisn }}</div></td><td class="py-3.5 px-4 font-mono"><div class="text-slate-200">NISN: {{ $r->nisn }}</div><span class="inline-block text-[10px] px-2 py-0.5 rounded border mt-1 font-semibold bg-slate-900 text-slate-400 border-slate-700">{{ $r->tanggal }}</span></td><td class="py-3.5 px-4"><span class="bg-slate-700/60 px-2.5 py-1 rounded-md border border-slate-600/80 font-medium">{{ $r->siswa->kelas->nama ?? '-' }}</span></td><td class="py-3.5 px-4 text-center">@if ($r->jam_masuk)<div><span class="font-mono {{ $r->status_masuk === 'Terlambat' ? 'text-amber-300' : 'text-emerald-400' }} font-semibold">{{ $r->jam_masuk }}</span><div><span class="text-[10px] px-2 py-0.5 rounded-full mt-1 inline-block {{ $r->status_masuk === 'Terlambat' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' }}">{{ $r->status_masuk }}</span></div></div>@elseif (in_array($r->status_masuk, ['Sakit','Ijin','Izin']))<span class="text-[10px] px-2 py-0.5 rounded-full inline-block bg-sky-500/20 text-sky-300 border border-sky-500/30">{{ $r->status_masuk }}</span>@elseif ($r->status_masuk === 'Di Sekolah')<span class="text-[10px] px-2 py-0.5 rounded-full inline-block bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Di Sekolah</span>@elseif ($r->status_masuk === 'Tanpa Keterangan')<span class="text-[10px] px-2 py-0.5 rounded-full inline-block bg-slate-600/40 text-slate-300 border border-slate-600">Tanpa Keterangan</span>@else<span class="text-slate-500 font-mono">-</span>@endif</td><td class="py-3.5 px-4 text-center">@if ($r->jam_pulang)<div><span class="font-mono text-indigo-400 font-semibold">{{ $r->jam_pulang }}</span><div><span class="text-[10px] bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 px-2 py-0.5 rounded-full mt-1 inline-block">{{ $r->status_pulang }}</span></div></div>@else<span class="text-slate-500 font-mono">-</span>@endif</td><td class="py-3.5 px-4 text-center">@if (in_array($r->status_masuk, ['Sakit','Ijin','Izin']))<span class="bg-sky-500/20 text-sky-300 border border-sky-500/40 px-2.5 py-1 rounded-full text-[11px] font-semibold">{{ $r->status_masuk }}</span>@elseif ($r->jam_pulang)<span class="bg-indigo-500/20 text-indigo-300 border border-indigo-500/40 px-2.5 py-1 rounded-full text-[11px] font-semibold">Selesai Pulang</span>@elseif ($r->jam_masuk)<span class="bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 px-2.5 py-1 rounded-full text-[11px] font-semibold">Di Sekolah</span>@elseif ($r->status_masuk === 'Di Sekolah')<span class="bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 px-2.5 py-1 rounded-full text-[11px] font-semibold">Di Sekolah</span>@elseif ($r->status_masuk === 'Tanpa Keterangan')<span class="bg-slate-600/40 text-slate-300 border border-slate-600 px-2.5 py-1 rounded-full text-[11px] font-semibold">Tanpa Keterangan</span>@else<span class="bg-rose-500/20 text-rose-300 border border-rose-500/40 px-2.5 py-1 rounded-full text-[11px] font-semibold">Belum Absen</span>@endif</td><td class="py-3.5 px-4 text-center"><a href="{{ route('laporan.edit', [$r->id, 'mode' => $mode, 'tanggal' => $tanggal, 'dari' => $dari, 'sampai' => $sampai, 'bulan' => $bulan]) }}" class="bg-slate-700 hover:bg-indigo-600 text-white text-[11px] font-semibold px-3 py-1.5 rounded-lg">Edit</a></td></tr>@empty<tr><td colspan="8" class="py-8 text-center text-slate-500">Tidak ada absensi tanggal ini.</td></tr>@endforelse</tbody>
  </table></div>
  <div class="mt-4 text-slate-300 text-sm">{{ $rows->links() }}</div>
  @if ($mode === 'harian')
  <div class="mt-6 rounded-xl border border-rose-500/30 overflow-hidden"><div class="bg-rose-500/10 px-4 py-3 text-xs font-semibold text-rose-300">Belum Absen ({{ $belum->count() }}) — tanpa baris absensi tanggal {{ $tanggal }}</div><div class="overflow-x-auto"><table class="w-full text-left text-xs border-collapse">
    <thead><tr class="bg-slate-900/90 text-slate-300 border-b border-slate-700 font-semibold"><th class="py-2.5 px-4">No</th><th class="py-2.5 px-4">Siswa</th><th class="py-2.5 px-4">NISN</th><th class="py-2.5 px-4">Kelas</th><th class="py-2.5 px-4 text-center">Aksi</th></tr></thead>
    <tbody class="divide-y divide-slate-700/60 text-slate-300">@forelse ($belum as $i => $s)<tr><td class="py-2.5 px-4 font-mono text-slate-400">{{ $i + 1 }}</td><td class="py-2.5 px-4 font-semibold text-white">{{ $s->nama }}</td><td class="py-2.5 px-4 font-mono">{{ $s->nisn }}</td><td class="py-2.5 px-4">{{ $s->kelas->nama ?? '-' }}</td><td class="py-2.5 px-4 text-center"><form method="POST" action="{{ route('laporan.tandai') }}" class="flex items-center justify-center gap-1.5">@csrf<input type="hidden" name="nisn" value="{{ $s->nisn }}"><input type="hidden" name="tanggal" value="{{ $tanggal }}"><select name="status_masuk" required class="bg-slate-900 border border-slate-700 rounded-lg px-2 py-1.5 text-white text-xs">@foreach (['Di Sekolah','Izin','Sakit','Tanpa Keterangan'] as $o)<option value="{{ $o }}">{{ $o }}</option>@endforeach</select><button class="bg-slate-700 hover:bg-indigo-600 text-white text-[11px] font-semibold px-3 py-1.5 rounded-lg">Tandai</button></form></td></tr>@empty<tr><td colspan="5" class="py-6 text-center text-slate-500">Semua siswa sudah ada baris absensi.</td></tr>@endforelse</tbody>
  </table></div></div>
  @endif
</div>
@endsection
