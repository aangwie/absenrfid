<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Admin') - Absensi RFID</title>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="min-h-screen bg-slate-900 text-slate-100 font-sans antialiased">
<div class="flex min-h-screen">
  <div id="sb-overlay" class="fixed inset-0 bg-black/60 z-30 hidden lg:hidden"></div>
  <aside id="sidebar" class="fixed lg:static z-40 w-60 shrink-0 min-h-screen bg-slate-800/90 border-r border-slate-700 p-4 flex-col gap-4 hidden lg:flex">
    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-2 py-1">
      <div class="w-10 h-10 bg-gradient-to-tr from-blue-600 to-indigo-500 rounded-xl flex items-center justify-center shrink-0"><span class="w-2.5 h-2.5 rounded-full bg-white animate-pulse"></span></div>
      <div><p class="text-sm font-bold text-white leading-tight">Absensi RFID</p><p class="text-[11px] text-slate-400">SMK Smart School</p></div>
    </a>
    <nav class="flex flex-col gap-1 text-sm">
      @foreach ([['dashboard', 'Dashboard', '/dashboard'], ['siswa.*', 'Siswa', '/siswa'], ['kelas.*', 'Kelas', '/kelas'], ['laporan.*', 'Laporan', '/laporan'], ['pengaturan.*', 'Pengaturan', '/pengaturan']] as [$pat, $label, $url])
      <a href="{{ $url }}" class="px-3 py-2.5 rounded-xl font-medium transition-all {{ request()->is(ltrim($url, '/').'*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20' : 'text-slate-300 hover:bg-slate-700/60 hover:text-white' }}">{{ $label }}</a>
      @endforeach
    </nav>
    <div class="mt-auto pt-4 border-t border-slate-700/60">
      <a href="{{ route('home') }}" class="block text-xs text-center px-3 py-2.5 rounded-xl border border-slate-700 text-slate-300 hover:border-indigo-500 hover:text-white transition-all">Kios Tap RFID</a>
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
document.getElementById('sb-toggle')?.addEventListener('click', () => {
  document.getElementById('sidebar').classList.toggle('hidden');
  document.getElementById('sidebar').classList.toggle('flex');
  document.getElementById('sb-overlay').classList.toggle('hidden');
});
document.getElementById('sb-overlay')?.addEventListener('click', () => {
  document.getElementById('sidebar').classList.add('hidden');
  document.getElementById('sidebar').classList.remove('flex');
  document.getElementById('sb-overlay').classList.add('hidden');
});
</script>
</body>
</html>
