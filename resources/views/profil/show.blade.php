@extends('layouts.admin')
@section('title', 'Profil Admin')
@section('content')
<div class="bg-slate-800/80 border border-slate-700 rounded-2xl p-5 max-w-lg">
  @if (session('ok'))<div class="mb-4 bg-emerald-500/15 border border-emerald-500/40 text-emerald-300 text-xs rounded-xl px-4 py-3">{{ session('ok') }}</div>@endif
  @if ($errors->any())<div class="mb-4 bg-rose-500/15 border border-rose-500/40 text-rose-300 text-xs rounded-xl px-4 py-3">{{ $errors->first() }}</div>@endif
  <form method="POST" action="{{ route('profil.update') }}" class="flex flex-col gap-4 text-sm">@csrf @method('PUT')
    <div><label class="block text-xs text-slate-300 mb-1">Nama</label><input name="name" value="{{ old('name', auth()->user()->name) }}" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-white focus:outline-none focus:border-indigo-500"></div>
    <div><label class="block text-xs text-slate-300 mb-1">Email</label><input type="email" name="email" value="{{ old('email', auth()->user()->email) }}" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-white focus:outline-none focus:border-indigo-500"></div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div><label class="block text-xs text-slate-300 mb-1">Password baru (opsional)</label><input type="password" name="password" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-white focus:outline-none focus:border-indigo-500"></div>
      <div><label class="block text-xs text-slate-300 mb-1">Konfirmasi password</label><input type="password" name="password_confirmation" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-white focus:outline-none focus:border-indigo-500"></div>
    </div>
    <button class="bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-4 py-2.5 rounded-xl">Simpan Profil</button>
  </form>
</div>
@endsection
