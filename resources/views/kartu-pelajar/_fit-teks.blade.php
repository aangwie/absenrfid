<script>
  (function() {
    // Nilai kolom biodata selalu 1 baris; font mengecil otomatis sampai muat.
    function ciutkan(rowsEl) {
      var tabel = rowsEl.querySelector('.biodata');
      if (!tabel) return;
      var selV = tabel.querySelectorAll('.v');
      if (!selV.length) return;
      for (var i = 0; i < selV.length; i++) {
        selV[i].style.whiteSpace = 'nowrap';
      }
      var v = tabel.querySelector('tr:first-child .v') || selV[0];
      var kotak = rowsEl.getBoundingClientRect();
      var batas = kotak.right - (parseFloat(getComputedStyle(rowsEl).paddingRight) || 0);
      var ukuran = parseFloat(getComputedStyle(tabel).fontSize) || 8.8;
      var minimum = 6.2; // ~4.65pt: batas terkecil agar tetap terbaca
      var langkah = 0;
      while (v.getBoundingClientRect().right > batas + 0.5 && ukuran > minimum && langkah < 40) {
        ukuran -= 0.12;
        tabel.style.fontSize = ukuran + 'px';
        langkah++;
      }
    }

    function jalanSemua() {
      var daftar = document.querySelectorAll('.rows');
      for (var i = 0; i < daftar.length; i++) ciutkan(daftar[i]);
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', jalanSemua);
    } else {
      jalanSemua();
    }
    if (document.fonts && document.fonts.ready) {
      document.fonts.ready.then(jalanSemua);
    }
  })();
</script>
