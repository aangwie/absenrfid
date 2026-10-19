{{-- Paginasi sederhana: [ikon panah kiri] 1 2 … 9 10 [ikon panah kanan] — chevron SVG, untuk paginator Laravel (mis. halaman Laporan). --}}
@php
    $cur = (int) $pg->currentPage();
    $last = max(1, (int) $pg->lastPage());
    $win = 5;
    $mulai = max(1, $cur - intdiv($win, 2));
    $selesai = min($last, $mulai + $win - 1);
    $mulai = max(1, $selesai - $win + 1);
    $nomor = [];
    if ($mulai > 1) {
        $nomor[] = 1;
        if ($mulai > 2) { $nomor[] = '…'; }
    }
    for ($n = $mulai; $n <= $selesai; $n++) { $nomor[] = $n; }
    if ($selesai < $last) {
        if ($selesai < $last - 1) { $nomor[] = '…'; }
        $nomor[] = $last;
    }
    $dasar = 'inline-flex items-center px-3 py-1.5 rounded-lg border text-xs';
    $ikon = 'inline-flex items-center px-2.5 py-1.5 rounded-lg border text-xs';
    $panahKiri = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>';
    $panahKanan = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>';
@endphp
@if ($last > 1)
<div class="flex flex-wrap items-center gap-1">
  @if ($cur > 1)
  <a href="{{ $pg->url($cur - 1) }}" rel="prev" aria-label="Sebelumnya" class="{{ $ikon }} bg-slate-900 border-slate-700 text-slate-200 hover:border-indigo-500">{!! $panahKiri !!}</a>
  @else
  <span aria-hidden="true" class="{{ $ikon }} bg-slate-900 border-slate-700 text-slate-200 opacity-40">{!! $panahKiri !!}</span>
  @endif
  @foreach ($nomor as $n)
    @if ($n === '…')
  <span class="px-2 py-1.5 text-slate-500 text-xs">…</span>
    @elseif ((int) $n === $cur)
  <span aria-current="page" class="{{ $dasar }} bg-indigo-600 border-indigo-600 text-white font-semibold">{{ $n }}</span>
    @else
  <a href="{{ $pg->url($n) }}" class="{{ $dasar }} bg-slate-900 border-slate-700 text-slate-200 hover:border-indigo-500">{{ $n }}</a>
    @endif
  @endforeach
  @if ($cur < $last)
  <a href="{{ $pg->url($cur + 1) }}" rel="next" aria-label="Berikutnya" class="{{ $ikon }} bg-slate-900 border-slate-700 text-slate-200 hover:border-indigo-500">{!! $panahKanan !!}</a>
  @else
  <span aria-hidden="true" class="{{ $ikon }} bg-slate-900 border-slate-700 text-slate-200 opacity-40">{!! $panahKanan !!}</span>
  @endif
</div>
@endif
