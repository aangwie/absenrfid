{{-- Helper Web NFC (Chrome Android + HTTPS). Dipakai halaman kios & dashboard. --}}
<script>
window.NfcHelper = (function () {
  function supported() {
    return ('NDEFReader' in window) && (window.isSecureContext !== false);
  }
  function recordText(rec) {
    try {
      if (typeof rec.data === 'string') return rec.data;
      var bytes = new Uint8Array(rec.data.buffer, rec.data.byteOffset, rec.data.byteLength);
      if (rec.recordType === 'text') {
        var utf16 = (rec.encoding || '').toLowerCase().indexOf('utf-16') !== -1;
        var skip = 1 + (bytes[0] & 63);
        return new TextDecoder(utf16 ? 'utf-16' : 'utf-8').decode(bytes.slice(skip));
      }
      return new TextDecoder().decode(bytes);
    } catch (e) { return ''; }
  }
  function pesanError(err) {
    var n = err && err.name ? err.name : '';
    var m = (err && err.message ? err.message : '').toLowerCase();
    if (n === 'NotSupportedError') return 'Perangkat/browser tidak mendukung Web NFC (butuh Chrome Android).';
    if (n === 'NotAllowedError') return m.indexOf('https') !== -1 || m.indexOf('secure') !== -1 ? 'Web NFC butuh HTTPS. Buka lewat https:// atau localhost.' : 'Izin NFC ditolak. Izinkan akses NFC lalu coba lagi.';
    if (n === 'NotReadableError') return 'NFC tidak bisa diakses. Pastikan NFC aktif di pengaturan HP.';
    if (n === 'AbortError') return 'Scan dibatalkan.';
    if (m === 'timeout') return 'Waktu scan habis (45 detik). Klik lagi lalu tempel kartu.';
    if (m === 'empty') return 'Kartu kosong — tidak ada data NDEF yang terbaca.';
    if (m === 'readingerror') return 'Kartu tidak terbaca. Coba tempel ulang.';
    return 'Gagal scan NFC: ' + (err && err.message ? err.message : err);
  }
  // resolve = isi teks pertama dari kartu; reject = Error (lihat pesanError).
  function read(timeoutMs) {
    return new Promise(function (resolve, reject) {
      if (!supported()) { reject(new Error('Perangkat/browser tidak mendukung Web NFC.')); return; }
      var reader = new NDEFReader();
      var ctl = new AbortController();
      var done = false;
      function selesai(fn, val) { if (done) return; done = true; clearTimeout(timer); try { ctl.abort(); } catch (e) {} fn(val); }
      var timer = setTimeout(function () { selesai(reject, new Error('timeout')); }, timeoutMs || 45000);
      reader.addEventListener('reading', function (e) {
        var recs = (e.message && e.message.records) || [];
        for (var i = 0; i < recs.length; i++) {
          var t = (recordText(recs[i]) || '').trim();
          if (t) { selesai(resolve, t); return; }
        }
        selesai(reject, new Error('empty'));
      });
      reader.addEventListener('readingerror', function () { selesai(reject, new Error('readingerror')); });
      try {
        var p = reader.scan({ signal: ctl.signal });
        if (p && p.catch) p.catch(function (err) { selesai(reject, err); });
      } catch (err) { selesai(reject, err); }
    });
  }
  return { supported: supported, read: read, pesanError: pesanError };
})();
</script>
