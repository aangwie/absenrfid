<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cetak Kartu Pelajar ({{ $siswas->count() }}) - {{ $p['nama_institusi'] ?: 'Absensi RFID' }}</title>
  @php($fav = \App\Models\Pengaturan::nilai('logo_sekolah'))
  @if($fav)
  <link rel="icon" href="{{ $fav }}">@else
  <link rel="icon" href="{{ asset('favicon.ico') }}">@endif
  @include('kartu-pelajar._style')
</head>

<body>
  <div class="toolbar">
    <a class="back" href="{{ route('kartu-pelajar.index') }}">← Kembali</a>
    <span>{{ $siswas->count() }} kartu • A4 • depan kiri & belakang kanan • ukuran KTP 85.6 × 53.98 mm</span>
    <button class="print" onclick="window.print()">🖨 Cetak / Simpan PDF</button>
  </div>
  @foreach ($siswas as $s)
  @php($tglId = $s->tanggal_lahir ? $s->tanggal_lahir->locale('id')->isoFormat('DD MMMM Y') : null)
  @php($tempat = $s->tempat_lahir ? \Illuminate\Support\Str::title($s->tempat_lahir) : null)
  @php($ttl = $tempat && $tglId ? $tempat . ', ' . $tglId : ($tempat ?? ($tglId ?? '-')))
  @php($gender = $s->gender === 'P' ? 'Perempuan' : ($s->gender === 'L' ? 'Laki-laki' : '-'))
  @php($qrText = ($p['nama_institusi'] ?: 'SMK Smart School') . "\nNama: " . ($s->nama ?? '-') . "\nNISN: " . ($s->nisn ?? '-') . "\nTTL: " . $ttl . "\nJenis Kelamin: " . $gender . "\nTahun Masuk: " . ($s->tahun_masuk ?? '-'))
  <div class="page">
    <div>
      <div class="side-label">Depan</div>
      @include('kartu-pelajar._depan')
    </div>
    <div>
      <div class="side-label">Belakang</div>
      @include('kartu-pelajar._belakang')
    </div>
  </div>
  @endforeach
  @include('kartu-pelajar._qr')
  @include('kartu-pelajar._fit-teks')
</body>

</html>