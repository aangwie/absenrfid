<div class="card" data-nama-file="{{ $s->nisn }}_depan">
  <div class="wm">@for ($i = 0; $i < 40; $i++)<span>{{ $p['nama_institusi'] ?: 'SMK Smart School' }}</span>@endfor</div>
  <div class="kop">
    @if (! empty($p['logo_sekolah']))
    <img src="{{ $p['logo_sekolah'] }}" alt="Logo">
    @endif
    <div class="kt">
      <div class="k1">KARTU PELAJAR</div>
      <div class="k2">{{ strtoupper(mb_strimwidth($p['nama_institusi'] ?: 'SMK Smart School', 0, 40, '…')) }}</div>
      <div class="k3">{{ mb_strimwidth($p['alamat'] ?: '-', 0, 60, '…') }}</div>
    </div>
  </div>
  <div class="body">
    @if ($s->foto)
    <img class="foto" src="{{ $s->foto }}" alt="Foto {{ $s->nama }}">
    @else
    <div class="foto-ph">{{ strtoupper(substr($s->nama ?? 'S', 0, 1)) }}</div>
    @endif
    <div class="rows">
      <div class="nama" title="{{ $s->nama }}">{{ $s->nama }}</div>
      <table class="biodata">
        <tr><td class="k">TTL</td><td class="s">:</td><td class="v">{{ $ttl }}</td></tr>
        <tr><td class="k">NISN</td><td class="s">:</td><td class="v">{{ $s->nisn }}</td></tr>
        <tr><td class="k">Jenis Kelamin</td><td class="s">:</td><td class="v">{{ $gender }}</td></tr>
        <tr><td class="k">Tahun Masuk</td><td class="s">:</td><td class="v">{{ $s->tahun_masuk ?? '-' }}</td></tr>
      </table>
    </div>
  </div>
  <div class="qr-box">
    <div class="qr" data-qr="{{ $qrText }}"></div>
    <div class="cap">{{ $s->nisn }}</div>
  </div>
  <div class="foot">NISN {{ $s->nisn }} • TAP ABSENSI RFID</div>
</div>
