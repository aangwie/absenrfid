@extends('layouts.admin')
@section('title', 'Whatsapp')
@section('content')
@if (session('ok'))<div class="bg-emerald-500/15 border border-emerald-500/40 text-emerald-300 text-xs rounded-xl px-4 py-3">{{ session('ok') }}</div>@endif
@if ($errors->any())<div class="bg-rose-500/15 border border-rose-500/40 text-rose-300 text-xs rounded-xl px-4 py-3">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
@if (session('log'))<div class="bg-sky-500/10 border border-sky-500/40 rounded-xl px-4 py-3"><pre class="text-[11px] font-mono text-sky-200 whitespace-pre-wrap break-all">{{ session('log') }}</pre></div>@endif

<div class="bg-slate-800/80 border border-slate-700 rounded-2xl p-5">
  <div class="flex items-center gap-1 bg-slate-900/80 p-1 rounded-xl border border-slate-700/60 overflow-x-auto text-xs font-semibold mb-5">
    @foreach ([['konfigurasi', 'Konfigurasi'], ['uji', 'Uji Kirim']] as [$v, $label])
    <a href="{{ route('whatsapp.index', ['tab' => $v]) }}" class="px-3 py-1.5 rounded-lg whitespace-nowrap {{ $tab === $v ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white' }}">{{ $label }}</a>
    @endforeach
  </div>

  @if ($tab === 'konfigurasi')
  <div class="max-w-2xl">
    <h2 class="text-base font-semibold text-white mb-1">Konfigurasi WhatsApp API</h2>
    <p class="text-[11px] text-slate-500 mb-4">Pengiriman memakai <span class="font-mono text-slate-300">Content-Type: application/x-www-form-urlencoded</span>.</p>
    <form action="{{ route('whatsapp.simpan') }}" method="POST" class="flex flex-col gap-3">@csrf
      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1.5">URL</label>
        <input type="url" name="wa_url" value="{{ old('wa_url', $cfg['wa_url']) }}" placeholder="https://api.contoh.com/send-message" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500" required>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1.5">API Key</label>
        <input type="text" name="wa_api_key" value="{{ old('wa_api_key', $cfg['wa_api_key']) }}" placeholder="API key dari penyedia" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500" required>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Number (pengirim)</label>
        <input type="text" name="wa_number" value="{{ old('wa_number', $cfg['wa_number']) }}" placeholder="cth 6281234567890" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500" required>
      </div>
      <div><button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold px-4 py-2 rounded-xl">Simpan</button></div>
    </form>
  </div>
  @elseif ($tab === 'uji')
  <div class="max-w-2xl">
    <h2 class="text-base font-semibold text-white mb-1">Uji Pengiriman WhatsApp</h2>
    <p class="text-[11px] text-slate-500 mb-4">Kirim pesan tes ke satu nomor tujuan.</p>
    <form action="{{ route('whatsapp.uji') }}" method="POST" class="flex flex-col gap-3">@csrf
      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Nomor Tujuan</label>
        <input type="text" name="tujuan" value="{{ old('tujuan') }}" placeholder="cth 6281234567890" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500" required>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Pesan</label>
        <textarea name="pesan" rows="4" placeholder="Tulis pesan tes..." class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500" required>{{ old('pesan') }}</textarea>
      </div>
      <div><button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold px-4 py-2 rounded-xl">Kirim Pesan</button></div>
    </form>
  </div>
  @endif
</div>
@endsection
