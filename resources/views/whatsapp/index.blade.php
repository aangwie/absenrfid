@extends('layouts.admin')
@section('title', 'Whatsapp')
@section('content')
@if (session('ok'))<div class="bg-emerald-500/15 border border-emerald-500/40 text-emerald-300 text-xs rounded-xl px-4 py-3">{{ session('ok') }}</div>@endif
@if ($errors->any())<div class="bg-rose-500/15 border border-rose-500/40 text-rose-300 text-xs rounded-xl px-4 py-3">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
@if (session('log'))<div class="bg-sky-500/10 border border-sky-500/40 rounded-xl px-4 py-3"><pre class="text-[11px] font-mono text-sky-200 whitespace-pre-wrap break-all">{{ session('log') }}</pre></div>@endif

<div class="bg-slate-800/80 border border-slate-700 rounded-2xl p-5">
  <div class="flex items-center gap-1 bg-slate-900/80 p-1 rounded-xl border border-slate-700/60 overflow-x-auto text-xs font-semibold mb-5">
    @foreach ([['konfigurasi', 'Konfigurasi'], ['uji', 'Uji Kirim'], ['log', 'Log Notifikasi'], ['cron', 'Tes Cron']] as [$v, $label])
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

    @if (session('wa_hasil'))
    @php($hasil = session('wa_hasil'))
    <div class="mt-4 rounded-xl border overflow-hidden {{ $hasil['sukses'] ? 'border-emerald-500/40' : 'border-rose-500/40' }}">
      <div class="px-4 py-3 flex items-center gap-2 {{ $hasil['sukses'] ? 'bg-emerald-500/10' : 'bg-rose-500/10' }}">
        <span class="text-[11px] font-bold px-2.5 py-1 rounded-full {{ $hasil['sukses'] ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' : 'bg-rose-500/20 text-rose-300 border border-rose-500/40' }}">{{ $hasil['sukses'] ? 'BERHASIL' : 'GAGAL' }}</span>
        <span class="text-xs text-slate-300">{{ $hasil['waktu'] ?? '' }}</span>
        @if (! is_null($hasil['http'] ?? null))<span class="ml-auto text-[11px] font-mono text-slate-300">HTTP {{ $hasil['http'] }}</span>@endif
      </div>
      <div class="bg-slate-900/60 px-4 py-3 text-xs flex flex-col gap-2">
        <div class="flex gap-2"><span class="text-slate-500 w-20 shrink-0">Tujuan</span><span class="font-mono text-slate-200">{{ $hasil['tujuan'] ?? '-' }}</span></div>
        <div class="flex gap-2"><span class="text-slate-500 w-20 shrink-0">Keterangan</span><span class="text-slate-200">{{ $hasil['ringkasan'] ?? '-' }}</span></div>
        <div>
          <p class="text-slate-500 mb-1.5">Respons API</p>
          <pre class="text-[11px] font-mono text-slate-200 bg-slate-950 border border-slate-700 rounded-lg p-3 whitespace-pre-wrap break-all">{{ $hasil['detail'] ?? '-' }}</pre>
        </div>
      </div>
    </div>
    @endif
  </div>
  @elseif ($tab === 'log')
  <div>
    <div class="flex flex-col sm:flex-row sm:items-center gap-2 mb-1">
      <h2 class="text-base font-semibold text-white flex-1">Log Notifikasi Absensi</h2>
      @if (($logs->total() ?? 0) > 0)
      <form method="POST" action="{{ route('whatsapp.log-hapus') }}" id="hapus-log-form">@csrf @method('DELETE')
        <button type="submit" class="bg-rose-600/20 border border-rose-500/40 text-rose-300 hover:bg-rose-600 hover:text-white text-xs font-semibold px-4 py-2 rounded-xl transition-all">Hapus Semua Log</button>
      </form>
      @endif
    </div>
    <p class="text-[11px] text-slate-500 mb-4">Setiap absensi yang direkam dicatat di sini: terkirim atau gagal, beserta respons API.</p>
    <div class="overflow-x-auto rounded-xl border border-slate-700/80"><table class="w-full text-left text-xs border-collapse">
      <thead><tr class="bg-slate-900/90 text-slate-300 border-b border-slate-700 font-semibold"><th class="py-3.5 px-4">Waktu</th><th class="py-3.5 px-4">Siswa</th><th class="py-3.5 px-4 text-center">Tipe</th><th class="py-3.5 px-4">Tujuan</th><th class="py-3.5 px-4 text-center">Status</th><th class="py-3.5 px-4 text-center">HTTP</th><th class="py-3.5 px-4">Respons</th></tr></thead>
      <tbody class="divide-y divide-slate-700/60 text-slate-300">@forelse ($logs as $l)<tr class="hover:bg-slate-700/30 transition-colors"><td class="py-3 px-4 font-mono text-slate-400 whitespace-nowrap">{{ $l->created_at }}</td><td class="py-3 px-4"><div class="font-semibold text-white text-sm">{{ $l->siswa->nama ?? $l->nisn }}</div><div class="text-[11px] text-slate-400 font-mono">{{ $l->nisn }} • {{ $l->tanggal }}</div></td><td class="py-3 px-4 text-center">@if ($l->tipe === 'pulang')<span class="bg-indigo-500/20 text-indigo-300 border border-indigo-500/40 px-2.5 py-1 rounded-full text-[11px] font-semibold">PULANG</span>@else<span class="bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 px-2.5 py-1 rounded-full text-[11px] font-semibold">MASUK</span>@endif</td><td class="py-3 px-4 font-mono text-slate-200">{{ $l->nomor_tujuan ?? '-' }}</td><td class="py-3 px-4 text-center">@if ($l->status === 'terkirim')<span class="bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 px-2.5 py-1 rounded-full text-[11px] font-semibold">Terkirim</span>@elseif ($l->status === 'antre')<span class="bg-amber-500/20 text-amber-300 border border-amber-500/40 px-2.5 py-1 rounded-full text-[11px] font-semibold">Antre</span>@else<span class="bg-rose-500/20 text-rose-300 border border-rose-500/40 px-2.5 py-1 rounded-full text-[11px] font-semibold">Gagal</span>@endif</td><td class="py-3 px-4 text-center font-mono text-slate-400">{{ $l->http_code ?? '-' }}</td><td class="py-3 px-4 text-[11px] text-slate-400 max-w-xs"><span title="{{ $l->respons }}">{{ \Illuminate\Support\Str::limit($l->respons ?? '-', 120) }}</span></td></tr>@empty<tr><td colspan="7" class="py-8 text-center text-slate-500">Belum ada notifikasi.</td></tr>@endforelse</tbody>
    </table></div>
    <div class="mt-4 text-slate-300 text-sm">{{ $logs->links() }}</div>
  </div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.getElementById('hapus-log-form')?.addEventListener('submit', function (e) {
  e.preventDefault();
  var f = this;
  Swal.fire({title: 'Hapus semua log notifikasi?', text: 'Seluruh riwayat terkirim/gagal akan dihapus permanen.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#e11d48', cancelButtonColor: '#475569', confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal', background: '#1e293b', color: '#f1f5f9'}).then(function (r) { if (r.isConfirmed) f.submit(); });
});
</script>
  @elseif ($tab === 'cron')
  <div class="max-w-2xl">
    <h2 class="text-base font-semibold text-white mb-1">Tes Cron Hosting</h2>
    <p class="text-[11px] text-slate-500 mb-4">Pastikan cron yang memproses antrean WhatsApp benar-benar berjalan di hosting.</p>

    <div class="rounded-xl border border-slate-700 bg-slate-900/60 px-4 py-3 mb-4 flex items-center gap-2">
      @if (($cron['status'] ?? 'unknown') === 'aktif')
      <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">CRON AKTIF</span>
      <span class="text-xs text-slate-300">Ping uji sudah dieksekusi worker.</span>
      @elseif (($cron['status'] ?? 'unknown') === 'mungkin')
      <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/40">WORKER JALAN</span>
      <span class="text-xs text-slate-300">Ada worker jalan &lt; 10 menit lalu ({{ $cron['last_run'] }}), tapi ping uji sesi ini belum dieksekusi.</span>
      @elseif (($cron['status'] ?? 'unknown') === 'lama')
      <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/40">TERAKHIR {{ $cron['last_run'] }}</span>
      <span class="text-xs text-slate-300">Worker pernah jalan tapi sudah lama — cron mungkin mati.</span>
      @else
      <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-slate-600/40 text-slate-300 border border-slate-600">BELUM TERDETEKSI</span>
      <span class="text-xs text-slate-400">Belum ada worker tercatat. Kirim ping uji di bawah.</span>
      @endif
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4 text-xs">
      <div class="bg-slate-900/60 border border-slate-700 rounded-xl p-3"><p class="text-slate-500">Queue driver</p><p class="font-mono font-bold text-white">{{ $cron['queue'] ?? '-' }}</p></div>
      <div class="bg-slate-900/60 border border-slate-700 rounded-xl p-3"><p class="text-slate-500">Jobs antre</p><p class="font-mono font-bold text-amber-300 text-base">{{ $cron['jobs'] ?? 0 }}</p></div>
      <div class="bg-slate-900/60 border border-slate-700 rounded-xl p-3"><p class="text-slate-500">Jobs gagal</p><p class="font-mono font-bold text-rose-300 text-base">{{ $cron['gagal'] ?? 0 }}</p></div>
      <div class="bg-slate-900/60 border border-slate-700 rounded-xl p-3"><p class="text-slate-500">Notifikasi antre</p><p class="font-mono font-bold text-sky-300 text-base">{{ $cron['notif_antre'] ?? 0 }}</p></div>
    </div>

    <div class="flex flex-col sm:flex-row gap-2 mb-4">
      <form action="{{ route('whatsapp.ping') }}" method="POST" class="flex-1">@csrf
        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold px-4 py-2 rounded-xl">Kirim Ping Uji</button>
      </form>
      <form action="{{ route('whatsapp.cek') }}" method="POST" class="flex-1">@csrf
        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold px-4 py-2 rounded-xl">Cek Hasil</button>
      </form>
    </div>
    <p class="text-[11px] text-slate-500 mb-4">Alur: Kirim Ping Uji → tunggu ±1 menit (satu siklus cron, JANGAN klik Jalankan Antrean dulu) → klik Cek Hasil/refresh. Ping hanya dieksekusi bila ada worker yang memproses antrean.</p>

    @if (session('wa_cron_hasil'))
    @php($hasil = session('wa_cron_hasil'))
    <div class="rounded-xl border overflow-hidden {{ $hasil['sukses'] ? 'border-emerald-500/40' : 'border-rose-500/40' }}">
      <div class="px-4 py-3 flex items-center gap-2 {{ $hasil['sukses'] ? 'bg-emerald-500/10' : 'bg-rose-500/10' }}">
        <span class="text-[11px] font-bold px-2.5 py-1 rounded-full {{ $hasil['sukses'] ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' : 'bg-rose-500/20 text-rose-300 border border-rose-500/40' }}">{{ $hasil['sukses'] ? 'CRON AKTIF' : 'CRON BERMASALAH' }}</span>
        <span class="text-xs text-slate-300">{{ $hasil['waktu'] ?? '' }}</span>
      </div>
      <div class="bg-slate-900/60 px-4 py-3 text-xs flex flex-col gap-2">
        <div class="flex gap-2"><span class="text-slate-500 w-20 shrink-0">Keterangan</span><span class="text-slate-200">{{ $hasil['ringkasan'] ?? '-' }}</span></div>
        <div>
          <p class="text-slate-500 mb-1.5">Detail respons</p>
          <pre class="text-[11px] font-mono text-slate-200 bg-slate-950 border border-slate-700 rounded-lg p-3 whitespace-pre-wrap break-all">{{ $hasil['detail'] ?? '-' }}</pre>
        </div>
      </div>
    </div>
    @endif

    <div class="mt-4 bg-slate-900/60 border border-slate-700 rounded-xl p-4 text-xs flex flex-col gap-1.5">
      <p class="font-semibold text-white">Perintah cron yang harus terpasang di hosting (tiap menit)</p>
      <p class="font-mono text-slate-200 bg-slate-950 border border-slate-700 rounded-lg p-2.5 break-all">* * * * * cd /home/username/public_html && php artisan queue:work --stop-when-empty --max-time=50 &gt;/dev/null 2&gt;&amp;1</p>
      <p class="text-slate-500">Sesuaikan path folder proyek. Hasil Cek di atas adalah bukti eksekusinya.</p>
    </div>
  </div>
  @endif
</div>
@endsection
