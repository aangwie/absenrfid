@extends('layouts.admin')
@section('title', 'Whatsapp')
@section('content')
@if (session('ok'))<div class="bg-emerald-500/15 border border-emerald-500/40 text-emerald-300 text-xs rounded-xl px-4 py-3">{{ session('ok') }}</div>@endif
@if ($errors->any())<div class="bg-rose-500/15 border border-rose-500/40 text-rose-300 text-xs rounded-xl px-4 py-3">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
@if (session('log'))<div class="bg-sky-500/10 border border-sky-500/40 rounded-xl px-4 py-3"><pre class="text-[11px] font-mono text-sky-200 whitespace-pre-wrap break-all">{{ session('log') }}</pre></div>@endif

<div class="bg-slate-800/80 border border-slate-700 rounded-2xl p-5">
  <div class="flex items-center gap-1 bg-slate-900/80 p-1 rounded-xl border border-slate-700/60 overflow-x-auto text-xs font-semibold mb-5">
    @foreach ([['konfigurasi', 'Konfigurasi'], ['antrean', 'Antrean'], ['uji', 'Uji Kirim'], ['template', 'Template'], ['log', 'Log Notifikasi'], ['cron', 'Tes Cron']] as [$v, $label])
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
          <h3 class="text-xs font-bold text-white">Status Pesan yang Dikirim</h3>
          <p class="text-[11px] text-slate-500 mt-1">Centang status yang ingin dikirim. Hanya status tercentang yang dibuatkan notifikasi, dan semuanya dikirim lewat satu antrean dengan kecepatan di bawah. Status yang tidak dicentang tidak dikirim.</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
          @foreach (\App\Support\AntreanWhatsapp::KATEGORI as $kategori => $label)
          @php($kunci = \App\Support\AntreanWhatsapp::kunciKategori($kategori))
          <label class="flex items-center gap-2.5 bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-xs text-slate-200 cursor-pointer hover:border-indigo-500/60">
            <input type="hidden" name="{{ $kunci }}" value="0">
            <input type="checkbox" name="{{ $kunci }}" value="1" class="w-4 h-4 accent-indigo-500" @checked(old($kunci, $cfg[$kunci] ?? '1') === '1')>
            <span class="font-semibold">{{ $label }}</span>
          </label>
          @endforeach
        </div>
        <p class="text-[11px] text-slate-500">Notifikasi dari aksi <span class="text-slate-300 font-medium">Tandai</span> manual di halaman Laporan (Izin, Sakit, Tanpa Keterangan) selalu dikirim karena merupakan tindakan admin.</p>
      </div>

      <div class="border-t border-slate-700/60 pt-4 mt-1 flex flex-col gap-3">
        <div>
          <h3 class="text-xs font-bold text-white">Durasi dan Jumlah Pesan</h3>
          <p class="text-[11px] text-slate-500 mt-1">Pesan yang dicentang dikirim bertahap agar nomor tidak diblokir WhatsApp.</p>
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
        <p class="text-[11px] text-slate-500">Contoh: durasi 10 menit + 10 pesan = 1 pesan tiap 60 detik. Bila ada 90 pesan, pengiriman selesai dalam ±90 menit.</p>
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
    <p class="text-[11px] text-slate-500 mb-4">Pesan dari status yang dicentang di tab Konfigurasi, sudah dijadwalkan tetapi belum terkirim. Semua memakai kecepatan yang sama. Pesan yang belum terkirim bisa dibatalkan di sini.</p>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4 text-xs">
      <div class="bg-slate-900/60 border border-slate-700 rounded-xl p-3"><p class="text-slate-500">Jumlah antre</p><p class="font-mono font-bold text-amber-300 text-base">{{ $jumlahAntre }}</p></div>
      <div class="bg-slate-900/60 border border-slate-700 rounded-xl p-3"><p class="text-slate-500">Jeda per pesan</p><p class="font-mono font-bold text-white text-base">{{ $jadwal['jeda'] }} dtk</p></div>
      <div class="bg-slate-900/60 border border-slate-700 rounded-xl p-3"><p class="text-slate-500">Status dikirim</p><p class="font-semibold text-sky-300 text-sm">{{ $jadwal['aktif'] ? implode(', ', $jadwal['aktif']) : 'Tidak ada' }}</p></div>
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
  <div class="max-w-3xl">
    <h2 class="text-base font-semibold text-white mb-1">Uji Pengiriman WhatsApp</h2>
    <p class="text-[11px] text-slate-500 mb-4">Kirim pesan uji ke satu nomor tertentu, atau ke nomor HP orang tua/wali yang sudah terdaftar di data siswa (bisa banyak sekaligus).</p>
    <form action="{{ route('whatsapp.uji') }}" method="POST" class="flex flex-col gap-4" id="form-uji">@csrf
      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Tujuan Pengiriman</label>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
          <label class="flex items-start gap-2.5 bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 cursor-pointer hover:border-indigo-500/60 has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-500/10 transition-colors">
            <input type="radio" name="mode" value="nomor" class="mt-0.5 w-4 h-4 accent-indigo-500" @checked(old('mode', 'nomor') === 'nomor')>
            <span class="min-w-0">
              <span class="block text-xs font-semibold text-white">Nomor Tertentu</span>
              <span class="block text-[11px] text-slate-500">Ketik satu nomor tujuan secara manual.</span>
            </span>
          </label>
          <label class="flex items-start gap-2.5 bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 cursor-pointer hover:border-indigo-500/60 has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-500/10 transition-colors">
            <input type="radio" name="mode" value="siswa" class="mt-0.5 w-4 h-4 accent-indigo-500" @checked(old('mode') === 'siswa')>
            <span class="min-w-0">
              <span class="block text-xs font-semibold text-white">Nomor Siswa Terdaftar</span>
              <span class="block text-[11px] text-slate-500">Pilih satu atau beberapa siswa dari database.</span>
            </span>
          </label>
        </div>
      </div>

      <div id="mode-nomor" class="{{ old('mode', 'nomor') === 'siswa' ? 'hidden' : '' }}">
        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Nomor Tujuan</label>
        <input type="text" name="tujuan" id="tujuan-uji" value="{{ old('tujuan') }}" placeholder="cth 6281234567890" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">
        <p class="text-[11px] text-slate-500 mt-1.5">Boleh diawali 08, 628, atau +62. Nomor akan dinormalisasi otomatis.</p>
      </div>

      <div id="mode-siswa" class="{{ old('mode') === 'siswa' ? '' : 'hidden' }}">
        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Pilih Siswa</label>
        <div class="relative" id="siswa-picker">
          <div id="siswa-box" class="w-full min-h-[46px] bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 flex flex-wrap gap-1.5 items-center cursor-text focus-within:border-indigo-500">
            <div id="siswa-terpilih" class="flex flex-wrap gap-1.5"></div>
            <input type="text" id="siswa-cari" autocomplete="off" placeholder="Cari nama siswa atau nomor HP..." class="flex-1 min-w-[160px] bg-transparent text-sm text-white placeholder-slate-500 focus:outline-none py-1">
            <button type="button" id="siswa-toggle" class="text-slate-400 hover:text-white px-1 text-xs" title="Buka daftar siswa">▾</button>
          </div>
          <div id="siswa-menu" class="hidden absolute z-30 mt-1 w-full bg-slate-900 border border-slate-700 rounded-xl shadow-2xl max-h-72 overflow-y-auto">
            <div class="sticky top-0 z-10 bg-slate-900/95 backdrop-blur border-b border-slate-700/70 px-3 py-2 flex items-center justify-between gap-2">
              <span id="siswa-jumlah" class="text-[11px] text-slate-500">0 siswa ditampilkan</span>
              <button type="button" id="siswa-pilih-semua" class="text-[11px] font-semibold text-indigo-300 hover:text-white">Pilih semua hasil</button>
            </div>
            <div id="siswa-daftar">
              @forelse ($siswaWa as $s)
              <label class="siswa-item flex items-center gap-2.5 px-3 py-2 hover:bg-slate-800 cursor-pointer border-b border-slate-800/60 last:border-0" data-cari="{{ \Illuminate\Support\Str::lower($s->nama . ' ' . $s->nisn . ' ' . $s->no_hp_ortu) }}">
                <input type="checkbox" name="siswa[]" value="{{ $s->nisn }}" class="js-siswa w-4 h-4 accent-indigo-500 shrink-0" data-nama="{{ $s->nama }}" data-hp="{{ $s->no_hp_ortu }}" @checked(in_array($s->nisn, (array) old('siswa', []), true))>
                <span class="min-w-0">
                  <span class="block text-xs font-semibold text-white truncate">{{ $s->nama }}</span>
                  <span class="block text-[11px] text-slate-400 font-mono truncate">{{ $s->nisn }} • {{ $s->kelas->nama ?? '-' }} • {{ $s->no_hp_ortu }}</span>
                </span>
              </label>
              @empty
              <div class="px-3 py-6 text-center text-xs text-slate-500">Belum ada siswa dengan nomor HP ortu/wali. Lengkapi nomor HP di menu Siswa dulu.</div>
              @endforelse
              <div id="siswa-kosong" class="hidden px-3 py-6 text-center text-xs text-slate-500">Tidak ada siswa yang cocok dengan pencarian.</div>
            </div>
          </div>
        </div>
        <p class="text-[11px] text-slate-500 mt-1.5">Bisa pilih lebih dari satu. Pencarian berdasarkan nama siswa, NISN, atau nomor HP. Maksimal 50 siswa sekali kirim.</p>
        <p id="siswa-info" class="text-[11px] text-indigo-300 mt-1"></p>
      </div>

      <div>
        <div class="flex flex-col sm:flex-row sm:items-center gap-2 mb-1.5">
          <label for="pesan-uji" class="text-xs font-semibold text-slate-300 flex-1">Pesan</label>
          <select id="pilih-template" class="bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-[11px] text-slate-300 focus:outline-none focus:border-indigo-500">
            <option value="">Isi dari template…</option>
            @foreach ($templates as $t)
            <option value="{{ $t->id }}" data-isi="{{ $t->isi }}">{{ $t->nama }}{{ $t->aktif ? '' : ' (nonaktif)' }}</option>
            @endforeach
          </select>
        </div>
        <textarea name="pesan" id="pesan-uji" rows="5" maxlength="2000" placeholder="Tulis pesan uji..." class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500" required>{{ old('pesan') }}</textarea>
        <p class="text-[11px] text-slate-500 mt-1.5">Placeholder seperti <span class="font-mono text-slate-300">{nama}</span>, <span class="font-mono text-slate-300">{nisn}</span>, <span class="font-mono text-slate-300">{kelas}</span> otomatis diganti bila mengirim ke nomor siswa terdaftar.</p>
      </div>

      <div><button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold px-4 py-2 rounded-xl">Kirim Pesan Uji</button></div>
    </form>

    @if (session('wa_hasil') && ! empty(session('wa_hasil')['daftar']))
    @php($hasil = session('wa_hasil'))
    <div class="mt-5 rounded-xl border overflow-hidden {{ $hasil['sukses'] ? 'border-emerald-500/40' : 'border-rose-500/40' }}">
      <div class="px-4 py-3 flex flex-wrap items-center gap-2 {{ $hasil['sukses'] ? 'bg-emerald-500/10' : 'bg-rose-500/10' }}">
        <span class="text-[11px] font-bold px-2.5 py-1 rounded-full {{ $hasil['sukses'] ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' : 'bg-rose-500/20 text-rose-300 border border-rose-500/40' }}">{{ $hasil['sukses'] ? 'BERHASIL' : 'SEBAGIAN GAGAL' }}</span>
        <span class="text-xs text-slate-300">{{ $hasil['waktu'] ?? '' }}</span>
        <span class="ml-auto text-[11px] text-slate-300">{{ $hasil['berhasil'] ?? 0 }} berhasil • {{ $hasil['gagal'] ?? 0 }} gagal • {{ $hasil['total'] ?? 0 }} tujuan</span>
      </div>
      <div class="bg-slate-900/60 px-4 py-3 text-xs flex flex-col gap-2">
        <p class="text-slate-200">{{ $hasil['ringkasan'] ?? '-' }}</p>
        <div class="flex flex-col gap-2">
          @foreach ($hasil['daftar'] as $h)
          <details class="rounded-lg border border-slate-700 bg-slate-950/40" @if (count($hasil['daftar']) === 1) open @endif>
            <summary class="cursor-pointer px-3 py-2 flex flex-wrap items-center gap-2 select-none">
              <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $h['sukses'] ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' : 'bg-rose-500/20 text-rose-300 border border-rose-500/40' }}">{{ $h['sukses'] ? 'BERHASIL' : 'GAGAL' }}</span>
              @if (! empty($h['nama']))<span class="font-semibold text-white">{{ $h['nama'] }}</span>@endif
              <span class="font-mono text-slate-300">{{ $h['tujuan'] }}</span>
              @if (! is_null($h['http'] ?? null))<span class="ml-auto text-[10px] font-mono text-slate-400">HTTP {{ $h['http'] }}</span>@endif
            </summary>
            <div class="px-3 pb-3 flex flex-col gap-1.5 border-t border-slate-800 pt-2">
              <p class="text-slate-300">{{ $h['ringkasan'] }}</p>
              <pre class="text-[11px] font-mono text-slate-300 bg-slate-950 border border-slate-800 rounded-lg p-2.5 whitespace-pre-wrap break-all">{{ $h['detail'] }}</pre>
            </div>
          </details>
          @endforeach
        </div>
      </div>
    </div>
    @endif
  </div>
  <script>
  (function () {
    var form = document.getElementById('form-uji');
    if (!form) return;

    var boxNomor = document.getElementById('mode-nomor');
    var boxSiswa = document.getElementById('mode-siswa');
    var inputNomor = document.getElementById('tujuan-uji');
    var picker = document.getElementById('siswa-picker');
    var menu = document.getElementById('siswa-menu');
    var cari = document.getElementById('siswa-cari');
    var toggle = document.getElementById('siswa-toggle');
    var kotak = document.getElementById('siswa-box');
    var terpilih = document.getElementById('siswa-terpilih');
    var info = document.getElementById('siswa-info');
    var jumlah = document.getElementById('siswa-jumlah');
    var kosong = document.getElementById('siswa-kosong');
    var pilihSemua = document.getElementById('siswa-pilih-semua');
    var items = Array.prototype.slice.call(document.querySelectorAll('.siswa-item'));
    var checks = Array.prototype.slice.call(document.querySelectorAll('.js-siswa'));

    function bukaMenu() { menu.classList.remove('hidden'); }
    function tutupMenu() { menu.classList.add('hidden'); }

    function renderChips() {
      terpilih.innerHTML = '';
      var n = 0;
      checks.forEach(function (c) {
        if (!c.checked) return;
        n++;
        var chip = document.createElement('span');
        chip.className = 'inline-flex items-center gap-1 bg-indigo-500/20 border border-indigo-500/40 text-indigo-200 text-[11px] font-semibold px-2 py-1 rounded-lg max-w-full';
        var teks = document.createElement('span');
        teks.className = 'truncate';
        teks.textContent = c.dataset.nama + ' • ' + c.dataset.hp;
        var hapus = document.createElement('button');
        hapus.type = 'button';
        hapus.className = 'text-indigo-300 hover:text-white leading-none px-0.5';
        hapus.innerHTML = '&times;';
        hapus.title = 'Hapus';
        hapus.addEventListener('click', function () { c.checked = false; renderChips(); });
        chip.appendChild(teks);
        chip.appendChild(hapus);
        terpilih.appendChild(chip);
      });
      info.textContent = n > 0
        ? n + ' siswa dipilih — pesan dikirim ke nomor HP masing-masing.'
        : 'Belum ada siswa dipilih.';
    }

    function filter() {
      var q = (cari.value || '').toLowerCase().trim();
      var tampil = 0;
      items.forEach(function (it) {
        var cocok = q === '' || (it.dataset.cari || '').indexOf(q) !== -1;
        it.classList.toggle('hidden', !cocok);
        if (cocok) tampil++;
      });
      jumlah.textContent = tampil + ' siswa ditampilkan';
      if (kosong) kosong.classList.toggle('hidden', tampil !== 0 || items.length === 0);
    }

    function syncMode() {
      var dipilih = form.querySelector('input[name="mode"]:checked');
      var mode = dipilih ? dipilih.value : 'nomor';
      boxNomor.classList.toggle('hidden', mode !== 'nomor');
      boxSiswa.classList.toggle('hidden', mode !== 'siswa');
      if (inputNomor) inputNomor.required = mode === 'nomor';
      if (mode !== 'siswa') tutupMenu();
    }

    form.querySelectorAll('input[name="mode"]').forEach(function (r) {
      r.addEventListener('change', syncMode);
    });

    checks.forEach(function (c) { c.addEventListener('change', renderChips); });

    cari.addEventListener('focus', bukaMenu);
    cari.addEventListener('input', function () { bukaMenu(); filter(); });
    toggle.addEventListener('click', function () {
      if (menu.classList.contains('hidden')) bukaMenu(); else tutupMenu();
    });
    kotak.addEventListener('click', function (e) {
      if (e.target === kotak || e.target === terpilih) bukaMenu();
    });
    document.addEventListener('click', function (e) {
      if (picker && !picker.contains(e.target)) tutupMenu();
    });
    if (pilihSemua) {
      pilihSemua.addEventListener('click', function () {
        items.forEach(function (it) {
          if (it.classList.contains('hidden')) return;
          var c = it.querySelector('.js-siswa');
          if (c) c.checked = true;
        });
        renderChips();
      });
    }

    form.addEventListener('submit', function (e) {
      var dipilih = form.querySelector('input[name="mode"]:checked');
      var mode = dipilih ? dipilih.value : 'nomor';
      if (mode === 'nomor' && !(inputNomor.value || '').trim()) {
        e.preventDefault();
        inputNomor.focus();
        return;
      }
      if (mode === 'siswa' && !checks.some(function (c) { return c.checked; })) {
        e.preventDefault();
        info.textContent = 'Pilih minimal satu siswa dulu.';
        info.className = 'text-[11px] text-rose-300 mt-1';
        bukaMenu();
        cari.focus();
      }
    });

    var pilihTemplate = document.getElementById('pilih-template');
    var pesan = document.getElementById('pesan-uji');
    if (pilihTemplate && pesan) {
      pilihTemplate.addEventListener('change', function () {
        var opt = pilihTemplate.options[pilihTemplate.selectedIndex];
        var isi = opt ? opt.getAttribute('data-isi') : '';
        if (isi) pesan.value = isi;
      });
    }

    renderChips();
    filter();
    syncMode();
  })();
  </script>
  @elseif ($tab === 'template')
  <div>
    <h2 class="text-base font-semibold text-white mb-1">Template Pesan WhatsApp</h2>
    <p class="text-[11px] text-slate-500 mb-4">Template ini dipakai otomatis untuk notifikasi absensi ke siswa / orang tua. Hanya satu template aktif per kategori; bila tidak ada yang aktif, teks bawaan tetap dipakai.</p>

    <div class="bg-slate-900/60 border border-slate-700 rounded-xl p-4 mb-4 text-xs">
      <p class="font-semibold text-white mb-2">Placeholder yang bisa dipakai</p>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1.5">
        @foreach (\App\Models\WhatsappTemplate::PLACEHOLDER as $placeholder => $keterangan)
        <div class="flex gap-2 items-baseline"><code class="font-mono text-indigo-300 shrink-0">{{ $placeholder }}</code><span class="text-slate-400">{{ $keterangan }}</span></div>
        @endforeach
      </div>
    </div>

    @php($formTambah = old('_form') === 'tambah')
    <details class="rounded-xl border border-slate-700 bg-slate-900/60 mb-4" @if ($formTambah) open @endif>
      <summary class="cursor-pointer px-4 py-3 text-sm font-semibold text-white select-none">+ Tambah Template</summary>
      <form action="{{ route('whatsapp.template.simpan') }}" method="POST" class="px-4 pb-4 flex flex-col gap-3">@csrf
        <input type="hidden" name="_form" value="tambah">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Nama Template</label>
            <input type="text" name="nama" value="{{ $formTambah ? old('nama') : '' }}" maxlength="100" placeholder="cth Notifikasi Pulang" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500" required>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Kategori</label>
            <select name="kategori" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-indigo-500" required>
              @foreach (\App\Models\WhatsappTemplate::KATEGORI as $kategori => $label)
              <option value="{{ $kategori }}" @selected($formTambah && old('kategori') === $kategori)>{{ $label }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1.5">Isi Template</label>
          <textarea name="isi" rows="4" maxlength="4000" placeholder="[Absensi {institusi}] {nama} ({nisn}, {kelas}) ..." class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500" required>{{ $formTambah ? old('isi') : '' }}</textarea>
        </div>
        <label class="flex items-center gap-2.5 text-xs text-slate-200 cursor-pointer">
          <input type="hidden" name="aktif" value="0">
          <input type="checkbox" name="aktif" value="1" class="w-4 h-4 accent-indigo-500" @checked($formTambah ? old('aktif') : true)>
          <span>Jadikan template aktif untuk kategori ini</span>
        </label>
        <div><button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold px-4 py-2 rounded-xl">Simpan Template</button></div>
      </form>
    </details>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-3">
      @forelse ($templates as $t)
      @php($badge = ['tepat_waktu' => 'bg-emerald-500/15 text-emerald-300 border-emerald-500/40', 'terlambat' => 'bg-amber-500/15 text-amber-300 border-amber-500/40', 'pulang' => 'bg-indigo-500/15 text-indigo-300 border-indigo-500/40', 'tandai' => 'bg-sky-500/15 text-sky-300 border-sky-500/40'][$t->kategori] ?? 'bg-slate-500/15 text-slate-300 border-slate-500/40')
      @php($editIni = old('_form') === 'edit-' . $t->id)
      <div class="rounded-xl border {{ $t->aktif ? 'border-indigo-500/40' : 'border-slate-700' }} bg-slate-900/60 flex flex-col">
        <div class="px-4 pt-4 pb-3 border-b border-slate-800">
          <div class="flex items-start gap-2">
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold text-white truncate" title="{{ $t->nama }}">{{ $t->nama }}</p>
              <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full border {{ $badge }}">{{ \App\Models\WhatsappTemplate::KATEGORI[$t->kategori] ?? $t->kategori }}</span>
                @if ($t->aktif)
                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-300 border border-emerald-500/40">Aktif</span>
                @else
                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-slate-500/15 text-slate-400 border border-slate-500/40">Nonaktif</span>
                @endif
              </div>
            </div>
            <span class="text-[10px] font-mono text-slate-500">#{{ $t->id }}</span>
          </div>
        </div>

        <div class="px-4 py-3 flex-1">
          <pre class="text-[11px] text-slate-300 whitespace-pre-wrap break-words font-sans">{{ $t->isi }}</pre>
        </div>

        <details class="border-t border-slate-800" @if ($editIni) open @endif>
          <summary class="cursor-pointer px-4 py-2.5 text-xs font-semibold text-indigo-300 hover:text-indigo-200 select-none">Edit template</summary>
          <form action="{{ route('whatsapp.template.update', $t) }}" method="POST" class="px-4 pb-3 flex flex-col gap-2.5">@csrf @method('PUT')
            <input type="hidden" name="_form" value="edit-{{ $t->id }}">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
              <div>
                <label class="block text-[11px] font-semibold text-slate-400 mb-1">Nama</label>
                <input type="text" name="nama" value="{{ $editIni ? old('nama') : $t->nama }}" maxlength="100" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500" required>
              </div>
              <div>
                <label class="block text-[11px] font-semibold text-slate-400 mb-1">Kategori</label>
                <select name="kategori" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500" required>
                  @foreach (\App\Models\WhatsappTemplate::KATEGORI as $kategori => $label)
                  <option value="{{ $kategori }}" @selected(($editIni ? old('kategori') : $t->kategori) === $kategori)>{{ $label }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div>
              <label class="block text-[11px] font-semibold text-slate-400 mb-1">Isi Template</label>
              <textarea name="isi" rows="4" maxlength="4000" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500" required>{{ $editIni ? old('isi') : $t->isi }}</textarea>
            </div>
            <label class="flex items-center gap-2 text-[11px] text-slate-300 cursor-pointer">
              <input type="hidden" name="aktif" value="0">
              <input type="checkbox" name="aktif" value="1" class="w-3.5 h-3.5 accent-indigo-500" @checked($editIni ? old('aktif') : $t->aktif)>
              <span>Aktifkan template ini untuk kategori {{ \App\Models\WhatsappTemplate::KATEGORI[$t->kategori] ?? $t->kategori }}</span>
            </label>
            <div><button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold px-3.5 py-2 rounded-lg">Simpan Perubahan</button></div>
          </form>
          <form action="{{ route('whatsapp.template.hapus', $t) }}" method="POST" class="px-4 pb-4 js-hapus-template" data-nama="{{ $t->nama }}">@csrf @method('DELETE')
            <button type="submit" class="bg-rose-600/20 border border-rose-500/40 text-rose-300 hover:bg-rose-600 hover:text-white text-xs font-semibold px-3.5 py-2 rounded-lg transition-all">Hapus Template</button>
          </form>
        </details>
      </div>
      @empty
      <div class="xl:col-span-2 rounded-xl border border-slate-700 bg-slate-900/60 px-4 py-8 text-center text-slate-500 text-sm">Belum ada template tersimpan. Template bawaan tetap dipakai untuk notifikasi otomatis.</div>
      @endforelse
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
  document.querySelectorAll('.js-hapus-template').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      e.preventDefault();
      Swal.fire({title: 'Hapus template?', text: 'Template "' + (f.dataset.nama || 'ini') + '" akan dihapus permanen.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#e11d48', cancelButtonColor: '#475569', confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal', background: '#1e293b', color: '#f1f5f9'}).then(function (r) { if (r.isConfirmed) f.submit(); });
    });
  });
  </script>
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
