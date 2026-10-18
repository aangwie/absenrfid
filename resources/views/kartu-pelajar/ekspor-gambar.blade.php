<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ekspor Gambar Kartu ({{ $siswas->count() }}) - {{ $p['nama_institusi'] ?: 'Absensi RFID' }}</title>
  @php($fav = \App\Models\Pengaturan::nilai('logo_sekolah'))
  @if($fav)
  <link rel="icon" href="{{ $fav }}">@else
  <link rel="icon" href="{{ asset('favicon.ico') }}">@endif
  @include('kartu-pelajar._style')
  <style>
    body {
      background: #0f172a;
    }

    .bar-wrap {
      height: 6px;
      background: #334155;
      border-radius: 99px;
      overflow: hidden;
      min-width: 120px;
      flex: 1;
      max-width: 260px;
    }

    .bar-wrap i {
      display: block;
      height: 100%;
      width: 0;
      background: #22c55e;
      transition: width .2s;
    }

    .toolbar .dl {
      background: #16a34a;
      color: #fff;
      border-color: #16a34a;
    }

    .sheet {
      max-width: 1100px;
      margin: 0 auto 12px;
      background: #f1f5f9;
      border-radius: 12px;
      padding: 16px;
      display: flex;
      flex-wrap: wrap;
      gap: 14px;
      justify-content: center;
    }

    .hasil {
      max-width: 1100px;
      margin: 0 auto 24px;
      background: #1e293b;
      border-radius: 12px;
      padding: 12px 14px;
      color: #e2e8f0;
      font-size: 12px;
      display: none;
    }

    .hasil.show {
      display: block;
    }

    .hasil ul {
      list-style: none;
      margin: 8px 0 0;
      padding: 0;
      display: flex;
      flex-direction: column;
      gap: 6px;
      max-height: 260px;
      overflow: auto;
    }

    .hasil li {
      display: flex;
      align-items: center;
      gap: 10px;
      background: #0f172a;
      border: 1px solid #334155;
      border-radius: 8px;
      padding: 6px 10px;
    }

    .hasil img {
      width: 86px;
      height: 54px;
      object-fit: contain;
      background: #fff;
      border-radius: 4px;
    }

    .hasil a {
      color: #4ade80;
      font-weight: 700;
      text-decoration: none;
    }
  </style>
</head>

<body>
  <div class="toolbar">
    <a class="back" href="{{ route('kartu-pelajar.index') }}">← Kembali</a>
    <span id="ekspor-status">Menyiapkan {{ $siswas->count() }} siswa • sisi: {{ $sisi }} …</span>
    <div class="bar-wrap"><i id="ekspor-bar"></i></div>
    <button class="print" id="ekspor-ulang" type="button" style="display:none">↻ Proses Ulang</button>
    <a class="dl" id="ekspor-zip" href="#" style="display:none">⬇ Unduh ZIP</a>
  </div>
  <div class="sheet">
    @foreach ($siswas as $s)
    @php($tglId = $s->tanggal_lahir ? $s->tanggal_lahir->locale('id')->isoFormat('DD MMMM Y') : null)
    @php($tempat = $s->tempat_lahir ? \Illuminate\Support\Str::title($s->tempat_lahir) : null)
    @php($ttl = $tempat && $tglId ? $tempat . ', ' . $tglId : ($tempat ?? ($tglId ?? '-')))
    @php($gender = $s->gender === 'P' ? 'Perempuan' : ($s->gender === 'L' ? 'Laki-laki' : '-'))
    @php($qrText = ($p['nama_institusi'] ?: 'SMK Smart School') . "\nNama: " . ($s->nama ?? '-') . "\nNISN: " . ($s->nisn ?? '-') . "\nTTL: " . $ttl . "\nJenis Kelamin: " . $gender . "\nTahun Masuk: " . ($s->tahun_masuk ?? '-'))
    @if ($sisi === 'depan' || $sisi === 'keduanya')
    <div>
      <div class="side-label">Depan — {{ $s->nama }} ({{ $s->nisn }})</div>
      @include('kartu-pelajar._depan')
    </div>
    @endif
    @if ($sisi === 'belakang' || $sisi === 'keduanya')
    <div>
      <div class="side-label">Belakang — {{ $s->nama }} ({{ $s->nisn }})</div>
      @include('kartu-pelajar._belakang')
    </div>
    @endif
    @endforeach
  </div>
  <div class="hasil" id="ekspor-hasil">
    <strong>Hasil gambar</strong> <span id="ekspor-info"></span>
    <ul id="ekspor-list"></ul>
  </div>
  @include('kartu-pelajar._qr')
  @include('kartu-pelajar._fit-teks')
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
  <script>
    var EKSPOR_ZIP_NAMA = @json('kartu-pelajar-' . date('Ymd-His') . '.zip');

    function eksporStatus(teks, persen) {
      document.getElementById('ekspor-status').textContent = teks;
      document.getElementById('ekspor-bar').style.width = (persen || 0) + '%';
    }

    function unduhBlob(url, nama) {
      var a = document.createElement('a');
      a.href = url;
      a.download = nama;
      document.body.appendChild(a);
      a.click();
      setTimeout(function () { document.body.removeChild(a); }, 500);
    }

    function tambahBaris(file) {
      var li = document.createElement('li');
      var img = document.createElement('img');
      img.src = file.url;
      img.alt = file.name;
      var nama = document.createElement('span');
      nama.textContent = file.name;
      var taut = document.createElement('a');
      taut.href = file.url;
      taut.download = file.name;
      taut.textContent = 'Unduh PNG';
      li.appendChild(img);
      li.appendChild(nama);
      li.appendChild(taut);
      document.getElementById('ekspor-list').appendChild(li);
      document.getElementById('ekspor-hasil').classList.add('show');
    }

    (async function () {
      var tombolUlang = document.getElementById('ekspor-ulang');
      try { await (document.fonts ? document.fonts.ready : Promise.resolve()); } catch (e) {}
      var gambars = Array.prototype.slice.call(document.images);
      await Promise.all(gambars.map(function (g) {
        if (g.complete && g.naturalWidth !== 0) return null;
        return new Promise(function (selesai) {
          g.addEventListener('load', selesai, { once: true });
          g.addEventListener('error', selesai, { once: true });
          setTimeout(selesai, 6000);
        });
      }));
      await new Promise(function (r) { setTimeout(r, 500); });

      if (typeof html2canvas === 'undefined') {
        eksporStatus('Gagal memuat pustaka html2canvas (perlu internet).', 0);
        tombolUlang.style.display = '';
        return;
      }

      var kartu = Array.prototype.slice.call(document.querySelectorAll('.card[data-nama-file]'));
      if (!kartu.length) {
        eksporStatus('Tidak ada kartu untuk diekspor.', 0);
        return;
      }
      var skala = kartu.length > 20 ? 2 : 3;
      var berkas = [];
      for (var i = 0; i < kartu.length; i++) {
        eksporStatus('Merender gambar ' + (i + 1) + ' / ' + kartu.length + ' …', Math.round(i / kartu.length * 90));
        try {
          var kanvas = await html2canvas(kartu[i], { scale: skala, backgroundColor: '#ffffff', useCORS: true, logging: false });
          var blob = await new Promise(function (selesai) { kanvas.toBlob(selesai, 'image/png'); });
          if (!blob) throw new Error('kanvas kosong');
          var f = { name: kartu[i].getAttribute('data-nama-file') + '.png', blob: blob, url: URL.createObjectURL(blob) };
          berkas.push(f);
          tambahBaris(f);
        } catch (err) {
          var li = document.createElement('li');
          li.textContent = 'Gagal: ' + kartu[i].getAttribute('data-nama-file') + ' (' + (err && err.message ? err.message : err) + ')';
          document.getElementById('ekspor-list').appendChild(li);
          document.getElementById('ekspor-hasil').classList.add('show');
        }
      }

      tombolUlang.style.display = '';
      if (!berkas.length) {
        eksporStatus('Gagal merender semua kartu.', 0);
        return;
      }
      document.getElementById('ekspor-info').textContent = '— ' + berkas.length + ' gambar @ skala ' + skala + 'x';
      if (berkas.length === 1) {
        eksporStatus('Selesai — mengunduh 1 PNG.', 100);
        unduhBlob(berkas[0].url, berkas[0].name);
      } else {
        if (typeof JSZip === 'undefined') {
          eksporStatus('Selesai ' + berkas.length + ' gambar — JSZip gagal dimuat, unduh manual per gambar di bawah.', 100);
          return;
        }
        eksporStatus('Mengompres ' + berkas.length + ' gambar ke ZIP …', 95);
        try {
          var zip = new JSZip();
          berkas.forEach(function (f) { zip.file(f.name, f.blob); });
          var zipBlob = await zip.generateAsync({ type: 'blob' }, function (meta) {
            eksporStatus('Mengompres … ' + Math.round(meta.percent) + '%', 95);
          });
          var zipUrl = URL.createObjectURL(zipBlob);
          var tombolZip = document.getElementById('ekspor-zip');
          tombolZip.href = zipUrl;
          tombolZip.download = EKSPOR_ZIP_NAMA;
          tombolZip.style.display = '';
          eksporStatus('Selesai — ' + berkas.length + ' gambar dalam ZIP.', 100);
          unduhBlob(zipUrl, EKSPOR_ZIP_NAMA);
        } catch (err) {
          eksporStatus('ZIP gagal — unduh manual per gambar di bawah.', 100);
        }
      }
    })();

    document.getElementById('ekspor-ulang').addEventListener('click', function () { location.reload(); });
  </script>
</body>

</html>
