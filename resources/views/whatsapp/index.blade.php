@extends('layouts.admin')
@section('title', 'Whatsapp')
@section('content')
@if (session('ok'))<div class="bg-emerald-500/15 border border-emerald-500/40 text-emerald-300 text-xs rounded-xl px-4 py-3">{{ session('ok') }}</div>@endif
@if ($errors->any())<div class="bg-rose-500/15 border border-rose-500/40 text-rose-300 text-xs rounded-xl px-4 py-3">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
@if (session('log'))<div class="bg-sky-500/10 border border-sky-500/40 rounded-xl px-4 py-3"><pre class="text-[11px] font-mono text-sky-200 whitespace-pre-wrap break-all">{{ session('log') }}</pre></div>@endif

<div class="bg-slate-800/80 border border-slate-700 rounded-2xl p-5">
  <div class="flex items-center gap-1 bg-slate-900/80 p-1 rounded-xl border border-slate-700/60 overflow-x-auto text-xs font-semibold mb-5">
    @foreach ([['konfigurasi', 'Konfigurasi'], ['antrean', 'Antrean'], ['uji', 'Uji Kirim'], ['log', 'Log Notifikasi'], ['cron', 'Tes Cron']] as [$v, $label])
    <a href="{{ route('whatsapp.index', ['tab' => $v]) }}" class="px-3 py-1.5 rounded-lg whitespace-nowrap {{ $tab === $v ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white' }}">{{ $label }}@if ($v === 'antrean' && ($jumlahAntre ?? 0) > 0)<span class="ml-1.5 text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/40">{{ $jumlahAntre }}</span>@endif</a>
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

      <div class="border-t border-slate-700/60 pt-4 mt-1 flex flex-col gap-3">
        <div>
          <h3 class="text-xs font-bold text-white">Penjadwalan Notifikasi Masuk Tepat Waktu</h3>
          <p class="text-[11px] text-slate-500 mt-1">Pesan <span class="text-emerald-300 font-medium">MASUK Tepat Waktu</span> ditampung dulu lalu dikirim bertahap agar nomor tidak diblokir WhatsApp. Status lain (Terlambat, Izin, Sakit, Pulang, tandai manual) tetap dikirim segera dengan jeda pengaman 10 detik.</p>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Durasi (menit)</label>
            <input type="number" name="wa_batch_menit" id="wa_batch_menit" min="1" max="1440" step="1" value="{{ old('wa_batch_menit', $cfg['wa_batch_menit']) }}" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-indigo-500" required>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Jumlah pesan per durasi</label>
            <input type="number" name="wa_batch_jumlah" id="wa_batch_jumlah" min="1" max="1440" step="1" value="{{ old('wa_batch_jumlah', $cfg['wa_batch_jumlah']) }}" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-indigo-500" required>
          </div>
        </div>
        <p id="wa-batch-info" class="text-[11px] text-sky-300 bg-sky-500/10 border border-sky-500/30 rounded-lg px-3 py-2"></p>
        <p class="text-[11px] text-slate-500">Contoh: durasi 10 menit + 10 pesan = 1 pesan tiap 60 detik. Bila ada 90 pesan tepat waktu, pengiriman selesai dalam ±90 menit.</p>
      </div>

      <div><button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold px-4 py-2 rounded-xl">Simpan</button></div>
    </form>
  </div>
  <script>
  (function () {
    var m = document.getElementById('wa_batch_menit');
    var j = document.getElementById('wa_batch_jumlah');
    var out = document.getElementById('wa-batch-info');
    if (!m || !j || !out) return;
    function hitung() {
      var menit = Math.max(1, parseInt(m.value || '1', 10));
      var jumlah = Math.max(1, parseInt(j.value || '1', 10));
      var detik = Math.max(1, Math.round(menit * 60 / jumlah));
      var perMenit = Math.round((jumlah / menit) * 10) / 10;
      out.textContent = 'Jadwal: 1 pesan tiap ' + detik + ' detik (± ' + perMenit + ' pesan/menit). 90 pesan selesai dalam ±' + Math.ceil(90 * detik / 60) + ' menit.';
    }
    m.addEventListener('input', hitung);
    j.addEventListener('input', hitung);
    hitung();
  })();
  </script>

  @elseif ($tab === 'antrean')
  <div>
    <div class="flex flex-col sm:flex-row sm:items-center gap-2 mb-1">
      <h2 class="text-base font-semibold text-white flex-1">Antrean Pesan WhatsApp</h2>
    </div>
    <p class="text-[11px] text-slate-500 mb-4">Pesan yang sudah dijadwalkan tetapi belum terkirim. Pengiriman mengikuti durasi dan jumlah pesan di tab Konfigurasi. Pesan yang belum terkirim bisa dibatalkan di sini.</p>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4 text-xs">
      <div class="bg-slate-900/60 border border-slate-700 rounded-xl p-3"><p class="text-slate-500">Jumlah antre</p><p class="font-mono font-bold text-amber-300 text-base">{{ $jumlahAntre }}</p></div>
      <div class="bg-slate-900/60 border border-slate-700 rounded-xl p-3"><p class="text-slate-500">Jeda per pesan</p><p class="font-mono font-bold text-white text-base">{{ $jadwal['jeda'] }} dtk</p></div>
      <div class="bg-slate-900/60 border border-slate-700 rounded-xl p-3"><p class="text-slate-500">Ditampung (tepat waktu)</p><p class="font-mono font-bold text-sky-300 text-base">{{ $jadwal['ditampung'] }}</p></div>
      <div class="bg-slate-900/60 border border-slate-700 rounded-xl p-3"><p class="text-slate-500">Perkiraan pesan terakhir</p><p class="font-mono font-bold text-emerald-300 text-base">{{ $jadwal['terakhir'] ? $jadwal['terakhir']->format('d/m H:i') : '-' }}</p></div>
    </div>

    @if (($antrean->total() ?? 0) > 0)
    <div class="overflow-x-auto rounded-xl border border-slate-700/80"><table class="w-full text-left text-xs border-collapse">
      <thead><tr class="bg-slate-900/90 text-slate-300 border-b border-slate-700 font-semibold"><th class="py-3.5 px-4">Jadwal Kirim</th><th class="py-3.5 px-4">Siswa</th><th class="py-3.5 px-4 text-center">Tipe</th><th class="py-3.5 px-4 text-center">Keterangan</th><th class="py-3.5 px-4">Tujuan</th><th class="py-3.5 px-4">Pesan</th><th class="py-3.5 px-4 text-center">Aksi</th></tr></thead>
      <tbody class="divide-y divide-slate-700/60 text-slate-300">
        @foreach ($antrean as $l)
        <tr class="hover:bg-slate-700/30 transition-colors">
          <td class="py-3 px-4 whitespace-nowrap">
            <div class="font-mono text-slate-200">{{ $l->jadwal_kirim ? $l->jadwal_kirim->format('d/m/Y H:i:s') : '-' }}</div>
            @if ($l->jadwal_kirim && $l->jadwal_kirim->isPast())
            <span class="text-[10px] font-semibold text-amber-300">menunggu worker</span>
            @else
            <span class="text-[10px] text-slate-500">terjadwal</span>
            @endif
          </td>
          <td class="py-3 px-4"><div class="font-semibold text-white text-sm">{{ $l->siswa->nama ?? $l->nisn }}</div><div class="text-[11px] text-slate-400 font-mono">{{ $l->nisn }} • {{ $l->tanggal }}</div></td>
          <td class="py-3 px-4 text-center">@if ($l->tipe === 'pulang')<span class="bg-indigo-500/20 text-indigo-300 border border-indigo-500/40 px-2.5 py-1 rounded-full text-[11px] font-semibold">PULANG</span>@else<span class="bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 px-2.5 py-1 rounded-full text-[11px] font-semibold">MASUK</span>@endif</td>
          <td class="py-3 px-4 text-center text-slate-200">{{ $l->keterangan ?? '-' }}</td>
          <td class="py-3 px-4 font-mono text-slate-200">{{ $l->nomor_tujuan ?? '-' }}</td>
          <td class="py-3 px-4 text-[11px] text-slate-400 max-w-xs"><span title="{{ $l->pesan }}">{{ \Illuminate\Support\Str::limit($l->pesan ?? '-', 90) }}</span></td>
          <td class="py-3 px-4 text-center">
            <form method="POST" action="{{ route('whatsapp.antrean-batal', $l) }}" class="js-batal-form" data-nama="{{ $l->siswa->nama ?? $l->nisn }}">@csrf @method('DELETE')
              <button type="submit" class="bg-rose-600/20 border border-rose-500/40 text-rose-300 hover:bg-rose-600 hover:text-white text-[11px] font-semibold px-3 py-1.5 rounded-lg transition-all">Batalkan</button>
            </form>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table></div>
    <div class="mt-4 text-slate-300 text-sm">{{ $antrean->links() }}</div>
    @else
    <div class="rounded-xl border border-slate-700 bg-slate-900/60 px-4 py-8 text-center text-slate-500 text-sm">Tidak ada pesan yang menunggu. Semua notifikasi sudah diproses.</div>
    @endif
  </div>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
  document.querySelectorAll('.js-batal-form').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      e.preventDefault();
      Swal.fire({title: 'Batalkan antrean pesan?', text: 'Pesan untuk ' + (f.dataset.nama || 'siswa ini') + ' tidak akan dikirim.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#e11d48', cancelButtonColor: '#475569', confirmButtonText: 'Ya, batalkan', cancelButtonText: 'Tidak', background: '#1e293b', color: '#f1f5f9'}).then(function (r) { if (r.isConfirmed) f.submit(); });
    });
  });
  </script>

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
      <tbody class="divide-y divide-slate-700/60 text-slate-300">@forelse ($logs as $l)<tr class="hover:bg-slate-700/30 transition-colors"><td class="py-3 px-4 font-mono text-slate-400 whitespace-nowrap">{{ $l->created_at }}@if ($l->jadwal_kirim)<div class="text-[10px] text-slate-500">jadwal {{ $l->jadwal_kirim->format('d/m H:i') }}</div>@endif</td><td class="py-3 px-4"><div class="font-semibold text-white text-sm">{{ $l->siswa->nama ?? $l->nisn }}</div><div class="text-[11px] text-slate-400 font-mono">{{ $l->nisn }} • {{ $l->tanggal }}</div></td><td class="py-3 px-4 text-center">@if ($l->tipe === 'pulang')<span class="bg-indigo-500/20 text-indigo-300 border border-indigo-500/40 px-2.5 py-1 rounded-full text-[11px] font-semibold">PULANG</span>@else<span class="bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 px-2.5 py-1 rounded-full text-[11px] font-semibold">MASUK</span>@endif</td><td class="py-3 px-4 font-mono text-slate-200">{{ $l->nomor_tujuan ?? '-' }}</td><td class="py-3 px-4 text-center">@if ($l->status === 'terkirim')<span class="bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 px-2.5 py-1 rounded-full text-[11px] font-semibold">Terkirim</span>@elseif ($l->status === 'antre')<span class="bg-amber-500/20 text-amber-300 border border-amber-500/40 px-2.5 py-1 rounded-full text-[11px] font-semibold">Antre</span>@elseif ($l->status === 'batal')<span class="bg-slate-500/20 text-slate-300 border border-slate-500/40 px-2.5 py-1 rounded-full text-[11px] font-semibold">Dibatalkan</span>@else<span class="bg-rose-500/20 text-rose-300 border border-rose-500/40 px-2.5 py-1 rounded-full text-[11px] font-semibold">Gagal</span>@endif</td><td class="py-3 px-4 text-center font-mono text-slate-400">{{ $l->http_code ?? '-' }}</td><td class="py-3 px-4 text-[11px] text-slate-400 max-w-xs"><span title="{{ $l->respons }}">{{ \Illuminate\Support\Str::limit($l->respons ?? '-', 120) }}</span></td></tr>@empty<tr><td colspan="7" class="py-8 text-center text-slate-500">Belum ada notifikasi.</td></tr>@endforelse</tbody>
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
