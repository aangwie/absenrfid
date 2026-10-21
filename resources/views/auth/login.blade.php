@extends('layouts.app')
@section('title', 'Login Admin')
@section('content')
<div class="min-h-screen flex items-center justify-center p-4 bg-slate-900">
  <div class="w-full max-w-md bg-slate-800/90 border border-slate-700 rounded-2xl p-6 sm:p-8 shadow-xl">
    <div class="flex items-center gap-3 mb-6">
      <div class="w-12 h-12 bg-gradient-to-tr from-blue-600 to-indigo-500 rounded-2xl flex items-center justify-center shadow-lg shadow-blue-500/20 shrink-0">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8.5 14a4.5 4.5 0 117 0c-.8.8-1.5 1.2-1.5 2.5M12 4v2m6.36 1.64l-1.42 1.42M4.22 5.64l1.42 1.42M20 12h-2M4 12H2"/></svg>
      </div>
      <div>
        <p class="text-[11px] font-semibold text-blue-400 uppercase tracking-wide">USB HID RFID Reader</p>
        <h1 class="text-xl font-bold text-white">Login Admin</h1>
        <p class="text-xs text-slate-400">Sistem Absensi Siswa RFID</p>
      </div>
    </div>
    @if ($errors->any())
      <div class="mb-4 bg-rose-500/15 border border-rose-500/40 text-rose-300 text-xs rounded-xl px-4 py-3">
        {{ $errors->first() }}
      </div>
    @endif
    <form method="POST" action="{{ route('login.attempt') }}" class="flex flex-col gap-4">
      @csrf
      <div>
        <label for="email" class="block text-xs font-medium text-slate-300 mb-1">Email Admin</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="admin@sekolah.test"
          class="w-full bg-slate-900/90 border border-slate-700 focus:border-indigo-400 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/20 transition-all">
      </div>
      <div>
        <label for="password" class="block text-xs font-medium text-slate-300 mb-1">Password</label>
        <div class="relative">
          <input id="password" type="password" name="password" required placeholder="••••••••"
            class="w-full bg-slate-900/90 border border-slate-700 focus:border-indigo-400 rounded-xl px-4 py-3 pr-16 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/20 transition-all">
          <button type="button" aria-label="Tampil password" onclick="const i=document.getElementById('password'),s=i.type==='password';i.type=s?'text':'password';const a=this.querySelectorAll('svg');a[0].classList.toggle('hidden',!s);a[1].classList.toggle('hidden',s);this.setAttribute('aria-label',s?'Sembunyi password':'Tampil password')" class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white p-1.5"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg></button>
        </div>
      </div>
      <label class="flex items-center gap-2 text-xs text-slate-400 select-none">
        <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }} class="w-4 h-4 rounded accent-indigo-600">
        Ingat saya
      </label>
      <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold px-4 py-3 rounded-xl transition-all shadow-lg shadow-indigo-600/20">
        Masuk Dashboard
      </button>
    </form>
    <div class="mt-5 pt-4 border-t border-slate-700/60 flex items-center justify-between text-[11px] text-slate-500">
      <a href="{{ route('home') }}" class="hover:text-indigo-300 transition-colors">← Absensi</a>
      <span class="font-mono">admin@sekolah.test / admin123</span>
    </div>
  </div>
</div>
@endsection
