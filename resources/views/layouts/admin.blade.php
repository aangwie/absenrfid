<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Admin') - Absensi RFID</title>
@include('partials.favicon')
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<style>
#sidebar{transition:width .2s ease,padding .2s ease}
#sidebar .sb-mini{display:none}
@media (min-width:1024px){
  #sidebar.sb-min{width:4.5rem;padding-left:.75rem;padding-right:.75rem}
  #sidebar.sb-min .sb-label{display:none}
  #sidebar.sb-min nav a{justify-content:center;padding-left:0;padding-right:0}
  #sidebar.sb-min #sb-head{flex-direction:column;align-items:center}
  #sidebar.sb-min #sb-brand{justify-content:center;padding-left:0;padding-right:0}
  #sidebar.sb-min #sb-collapse{width:100%;justify-content:center}
  #sidebar.sb-min .sb-mini{display:inline}
  #sidebar.sb-min #sb-kios{justify-content:center}
}
</style>

</head>
<body class="min-h-screen bg-slate-900 text-slate-100 font-sans antialiased">
<div class="flex min-h-screen">
  <div id="sb-overlay" class="fixed inset-0 bg-black/60 z-30 hidden lg:hidden"></div>
  <aside id="sidebar" class="fixed lg:sticky lg:top-0 lg:h-screen z-40 w-60 shrink-0 min-h-screen bg-slate-800/90 border-r border-slate-700 p-4 flex flex-col gap-4 hidden lg:flex">
    @php($namaInstansi = trim(\App\Models\Pengaturan::nilai('nama_institusi')))
    @php($logoInstansi = \App\Models\Pengaturan::nilai('logo_sekolah'))
    <div id="sb-head" class="flex items-center gap-2">
      <a id="sb-brand" href="{{ route('dashboard') }}" class="flex flex-1 min-w-0 items-center gap-3 px-2 py-1">
        @if ($logoInstansi)
        <img src="{{ $logoInstansi }}" alt="Logo" class="w-10 h-10 rounded-xl object-contain bg-white/10 shrink-0">
        @else
        <div class="w-10 h-10 bg-gradient-to-tr from-blue-600 to-indigo-500 rounded-xl flex items-center justify-center shrink-0"><span class="w-2.5 h-2.5 rounded-full bg-white animate-pulse"></span></div>
        @endif
        <div class="sb-label min-w-0"><p class="text-sm font-bold text-white leading-tight">Absensi RFID</p><p class="text-[11px] text-slate-400 truncate" title="{{ $namaInstansi ?: 'SMK Smart School' }}">{{ $namaInstansi ?: 'SMK Smart School' }}</p></div>
      </a>
      <button id="sb-collapse" title="Minimize sidebar" class="hidden lg:inline-flex shrink-0 px-2.5 py-2 rounded-lg border border-slate-700 text-slate-300 hover:border-indigo-500 hover:text-white transition-all text-xs">⟨</button>
    </div>
    <nav class="flex flex-col gap-1 text-sm">
      @php($nav = [['dashboard', 'Dashboard', '/dashboard', 'M4 6h16M4 12h16M4 18h16'], ['siswa.*', 'Siswa', '/siswa', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'], ['kartu-pelajar.*', 'Kartu Pelajar', '/kartu-pelajar', 'M3 7a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7zM7 11h4m-4 4h7'], ['kelas.*', 'Kelas', '/kelas', 'M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z'], ['laporan.*', 'Laporan', '/laporan', 'M9 17v-6m5 6V7M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z'], ['whatsapp.*', 'Whatsapp', '/whatsapp', 'M8 12h.01M12 12h.01M16 12h.01M21 12a9 9 0 01-13.2 7.9L3 21l1.1-4.4A9 9 0 1121 12z'], ['pengaturan.*', 'Pengaturan', '/pengaturan', 'M10.3 4.3a1.7 1.7 0 013.4 0 1.7 1.7 0 002.4 1.6 1.7 1.7 0 012.4 2.4 1.7 1.7 0 001.6 2.4 1.7 1.7 0 010 3.4 1.7 1.7 0 00-1.6 2.4 1.7 1.7 0 01-2.4 2.4 1.7 1.7 0 00-2.4 1.6 1.7 1.7 0 01-3.4 0 1.7 1.7 0 00-2.4-1.6 1.7 1.7 0 01-2.4-2.4 1.7 1.7 0 00-1.6-2.4 1.7 1.7 0 010-3.4 1.7 1.7 0 001.6-2.4 1.7 1.7 0 012.4-2.4 1.7 1.7 0 002.4-1.6zM15 12a3 3 0 11-6 0 3 3 0 016 0z'], ['dokumentasi.*', 'Dokumentasi', '/dokumentasi', 'M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25']])
      @foreach ($nav as [$pat, $label, $url, $path])
      <a href="{{ $url }}" title="{{ $label }}" class="px-3 py-2.5 rounded-xl font-medium transition-all flex items-center gap-3 {{ request()->is(ltrim($url, '/').'*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20' : 'text-slate-300 hover:bg-slate-700/60 hover:text-white' }}"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-5 h-5 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}" /></svg><span class="sb-label">{{ $label }}</span></a>
      @endforeach
    </nav>
    <div class="mt-auto pt-4 border-t border-slate-700/60">
      <a id="sb-kios" href="{{ route('home') }}" target="_blank" rel="noopener" title="Kios Tap RFID" class="flex items-center justify-center gap-2 text-xs text-center px-3 py-2.5 rounded-xl border border-slate-700 text-slate-300 hover:border-indigo-500 hover:text-white transition-all"><span class="sb-label">Kios Tap RFID</span><span class="sb-mini">⌁</span></a>
    </div>
  </aside>
  <div class="flex-1 flex flex-col min-w-0">
    <header class="sticky top-0 z-20 bg-slate-800/90 backdrop-blur border-b border-slate-700 px-4 sm:px-6 py-3 flex items-center gap-3">
      <button id="sb-toggle" class="lg:hidden px-2.5 py-2 rounded-lg border border-slate-700 text-slate-300">☰</button>
      <h1 class="text-sm sm:text-base font-bold text-white truncate">@yield('title', 'Admin')</h1>
      <div class="ml-auto flex items-center gap-2">
        <a href="{{ route('profil') }}" class="flex items-center gap-2 pl-1 pr-3 py-1 rounded-xl border border-slate-700 hover:border-indigo-500 transition-all">
          <span class="w-8 h-8 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">{{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}</span>
          <span class="text-xs text-left hidden sm:block"><span class="block font-semibold text-white leading-tight">{{ auth()->user()->name }}</span><span class="block text-slate-400">Profil Admin</span></span>
        </a>
        <form method="POST" action="{{ route('logout') }}">@csrf
          <button class="text-xs font-semibold px-3 py-2.5 rounded-xl bg-rose-600/20 border border-rose-500/40 text-rose-300 hover:bg-rose-600/30 transition-all">Logout</button>
        </form>
      </div>
    </header>
    <main class="p-3 sm:p-6 flex flex-col gap-6">@yield('content')</main>
  </div>
</div>
<script>
(function(){
  var sb = document.getElementById('sidebar'), ov = document.getElementById('sb-overlay');
  var btnM = document.getElementById('sb-toggle'), btnC = document.getElementById('sb-collapse');
  function isDesktop(){ return window.matchMedia('(min-width:1024px)').matches; }
  function setMin(min, save){
    sb.classList.toggle('sb-min', !!min);
    if (btnC) btnC.textContent = min ? '⟩' : '⟨';
    if (btnC) btnC.title = min ? 'Expand sidebar' : 'Minimize sidebar';
    if (save !== false) try { localStorage.setItem('sb-min', min ? '1' : '0'); } catch(e){}
  }
  try { if (isDesktop() && localStorage.getItem('sb-min') === '1') setMin(true, false); } catch(e){}
  btnM?.addEventListener('click', () => {
    sb.classList.toggle('hidden');
    sb.classList.toggle('flex');
    ov?.classList.toggle('hidden');
  });
  ov?.addEventListener('click', () => {
    sb.classList.add('hidden');
    sb.classList.remove('flex');
    ov.classList.add('hidden');
  });
  btnC?.addEventListener('click', () => setMin(!sb.classList.contains('sb-min')));
})();
</script>
</body>
</html>
