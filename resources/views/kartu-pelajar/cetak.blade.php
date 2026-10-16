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
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: Arial, Helvetica, sans-serif;
      background: #64748b;
      color: #0f172a;
    }

    @page {
      size: A4;
      margin: 10mm;
    }

    .toolbar {
      max-width: 210mm;
      margin: 12px auto;
      background: #1e293b;
      color: #e2e8f0;
      border-radius: 12px;
      padding: 10px 14px;
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 12px;
    }

    .toolbar a,
    .toolbar button {
      font-size: 12px;
      font-weight: 700;
      border-radius: 8px;
      padding: 8px 14px;
      border: 1px solid #475569;
      cursor: pointer;
      text-decoration: none;
    }

    .toolbar .back {
      background: #334155;
      color: #e2e8f0;
    }

    .toolbar .print {
      background: #4f46e5;
      color: #fff;
      border-color: #4f46e5;
    }

    .page {
      width: 210mm;
      min-height: 296mm;
      margin: 0 auto 12px;
      background: #fff;
      padding: 10mm;
      display: flex;
      gap: 8mm;
      align-items: flex-start;
    }

    .side-label {
      font-size: 9px;
      font-weight: 700;
      letter-spacing: .08em;
      color: #64748b;
      margin-bottom: 2mm;
      text-transform: uppercase;
    }

    .card {
      width: 85.6mm;
      height: 53.98mm;
      border-radius: 3.55mm;
      overflow: hidden;
      border: .4mm solid #1e3a8a;
      position: relative;
      background: #fff;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
      flex-shrink: 0;
    }

    .kop {
      background: linear-gradient(135deg, #1e3a8a, #4f46e5);
      color: #fff;
      display: flex;
      align-items: center;
      gap: 2.2mm;
      padding: 1.8mm 2.5mm;
    }

    .kop img {
      width: 8mm;
      height: 8mm;
      object-fit: contain;
      background: rgba(255, 255, 255, .92);
      border-radius: 1.2mm;
      flex-shrink: 0;
    }

    .kop .kt {
      flex: 1;
      min-width: 0;
      text-align: center;
    }

    .kop .k1 {
      font-size: 7pt;
      font-weight: 800;
      letter-spacing: .12em;
      line-height: 1.15;
    }

    .kop .k2 {
      font-size: 8pt;
      font-weight: 700;
      line-height: 1.2;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .kop .k3 {
      font-size: 5.4pt;
      opacity: .92;
      line-height: 1.25;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .card .body {
      display: flex;
      gap: 2.5mm;
      padding: 2mm 2.5mm;
    }

    .foto {
      width: 20mm;
      height: 26.5mm;
      border-radius: 1.5mm;
      border: .3mm solid #94a3b8;
      object-fit: cover;
      background: #e2e8f0;
      flex-shrink: 0;
    }

    .foto-ph {
      width: 20mm;
      height: 26.5mm;
      border-radius: 1.5mm;
      border: .3mm dashed #94a3b8;
      background: #f1f5f9;
      color: #64748b;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 11pt;
      font-weight: 800;
      flex-shrink: 0;
    }

    .rows {
      flex: 1;
      min-width: 0;
      font-size: 6.6pt;
      line-height: 1.35;
    }

    .rows .nama {
      font-size: 8pt;
      font-weight: 800;
      color: #0f172a;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .rows .lbl {
      color: #64748b;
      font-size: 5.6pt;
      text-transform: uppercase;
      letter-spacing: .04em;
    }

    .rows .val {
      font-weight: 700;
      color: #0f172a;
    }

    .back-title {
      text-align: center;
      font-size: 7pt;
      font-weight: 800;
      letter-spacing: .08em;
      color: #1e3a8a;
      border-bottom: .35mm solid #1e3a8a;
      padding: 1.6mm 2mm 1.2mm;
    }

    .tatib {
      padding: 1.4mm 2.5mm 0 5.5mm;
      font-size: 5.7pt;
      line-height: 1.4;
      color: #1e293b;
    }

    .tatib li {
      margin-bottom: .5mm;
    }

    .ttd {
      display: flex;
      justify-content: flex-end;
      padding: .8mm 2.5mm 0;
    }

    .ttd div {
      text-align: center;
      font-size: 5.7pt;
      color: #334155;
      line-height: 1.35;
    }

    .ttd .cap {
      display: flex;
      align-items: flex-end;
      gap: 1.5mm;
    }

    .ttd img.stempel {
      width: 11mm;
      height: 11mm;
      object-fit: contain;
      opacity: .92;
    }

    .ttd img.ttd-img {
      height: 7mm;
      max-width: 20mm;
      object-fit: contain;
      display: block;
      margin: 0 auto;
    }

    .foot {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      background: #1e3a8a;
      color: #fff;
      font-size: 5.4pt;
      text-align: center;
      padding: .9mm;
      letter-spacing: .05em;
    }

    @media print {
      body {
        background: #fff;
      }

      .toolbar {
        display: none !important;
      }

      .page {
        margin: 0;
        padding: 0;
        width: auto;
        min-height: auto;
        page-break-after: always;
      }

      .page:last-child {
        page-break-after: auto;
      }
    }
  </style>
</head>

<body>
  <div class="toolbar">
    <a class="back" href="{{ route('kartu-pelajar.index') }}">← Kembali</a>
    <span>{{ $siswas->count() }} kartu • A4 • depan kiri & belakang kanan • ukuran KTP 85.6 × 53.98 mm</span>
    <button class="print" onclick="window.print()">🖨 Cetak / Simpan PDF</button>
  </div>
  @foreach ($siswas as $s)
  @php($ttl = $s->tempat_lahir && $s->tanggal_lahir ? $s->tempat_lahir . ', ' . $s->tanggal_lahir->format('d-m-Y') : ($s->tempat_lahir ?? ($s->tanggal_lahir ? $s->tanggal_lahir->format('d-m-Y') : '-')))
  <div class="page">
    <div>
      <div class="side-label">Depan</div>
      <div class="card">
        <div class="kop">
          @if (! empty($p['logo_sekolah']))<img src="{{ $p['logo_sekolah'] }}" alt="Logo">@endif
          <div class="kt">
            <div class="k1">KARTU PELAJAR</div>
            <div class="k2">{{ strtoupper(mb_strimwidth($p['nama_institusi'] ?: 'SMK Smart School', 0, 40, '…')) }}</div>
            <div class="k3">{{ mb_strimwidth($p['alamat'] ?: '-', 0, 60, '…') }}</div>
          </div>
        </div>
        <div class="body">
          @if ($s->foto)<img class="foto" src="{{ $s->foto }}" alt="Foto {{ $s->nama }}">
          @else<div class="foto-ph">{{ strtoupper(substr($s->nama ?? 'S', 0, 1)) }}</div>@endif
          <div class="rows">
            <div class="nama" title="{{ $s->nama }}">{{ $s->nama }}</div>
            <div><span class="lbl">TTL — </span><span class="val">{{ $ttl }}</span></div>
            <div><span class="lbl">NISN — </span><span class="val">{{ $s->nisn }}</span></div>
            <div><span class="lbl">Tahun Masuk — </span><span class="val">{{ $s->tahun_masuk ?? '-' }}</span></div>
            <div><span class="lbl">Kelas — </span><span class="val">{{ $s->kelas->nama ?? '-' }}</span></div>
          </div>
        </div>
        <div class="foot">NISN {{ $s->nisn }} • TAP ABSENSI RFID</div>
      </div>
    </div>
    <div>
      <div class="side-label">Belakang</div>
      <div class="card">
        <div class="back-title">TATA TERTIB PEMEGANG KARTU</div>
        <ol class="tatib">
          <li>Kartu ini identitas resmi peserta didik, wajib dibawa setiap mengikuti kegiatan sekolah.</li>
          <li>Wajib tap kartu pada mesin absensi saat masuk dan pulang sekolah.</li>
          <li>Dilarang meminjamkan atau memindahtangankan kartu kepada orang lain.</li>
          <li>Kehilangan/kerusakan segera lapor ke wali kelas atau Tata Usaha.</li>
          <li>Penggantian kartu hilang/rusak dikenakan biaya administrasi.</li>
          <li>Kartu yang ditemukan harap dikembalikan ke sekolah yang bersangkutan.</li>
        </ol>
        <div class="ttd">
          <div class="cap">
            @if(!empty($p['stempel_sekolah']))<img class="stempel" src="{{ $p['stempel_sekolah'] }}" alt="Stempel">@endif
            <div>Mengetahui,<br>Kepala Sekolah<br>@if(!empty($p['ttd_kepala_sekolah']))<img class="ttd-img" src="{{ $p['ttd_kepala_sekolah'] }}" alt="TTD">@else<br><br>@endif<u>{{ $p['nama_kepala_sekolah'] ?: '( ............................ )' }}</u>@if(!empty($p['nip_kepala_sekolah']))<br>NIP. {{ $p['nip_kepala_sekolah'] }}@endif</div>
          </div>
        </div>
        <div class="foot">{{ $s->nama }} • {{ $s->nisn }}</div>
      </div>
    </div>
  </div>
  @endforeach
</body>

</html>