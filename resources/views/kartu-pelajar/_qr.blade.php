<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
  function renderQrKartu() {
    document.querySelectorAll('.qr[data-qr]').forEach(function (el) {
      if (el.dataset.qrDone) return;
      el.dataset.qrDone = '1';
      var text = el.getAttribute('data-qr') || '-';
      try {
        if (typeof QRCode !== 'undefined') {
          new QRCode(el, { text: text, width: 96, height: 96, correctLevel: QRCode.CorrectLevel.M });
        } else {
          fallbackQr(el, text);
        }
      } catch (e) {
        el.innerHTML = '';
        el.removeAttribute('data-qr-done');
        fallbackQr(el, text);
      }
    });
  }
  function fallbackQr(el, text) {
    var img = document.createElement('img');
    img.alt = 'QR ' + text;
    img.src = 'https://api.qrserver.com/v1/create-qr-code/?size=96x96&data=' + encodeURIComponent(text);
    el.appendChild(img);
  }
  renderQrKartu();
</script>
