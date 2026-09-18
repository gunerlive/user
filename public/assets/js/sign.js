(function () {
  'use strict';

  var cfg = window.SIGN_PAGE_CONFIG;

  async function renderPdfWithHighlight() {
    if (!cfg || typeof pdfjsLib === 'undefined') return;
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

    var shell = document.getElementById('pdfShell');
    if (!shell) return;

    var pdf = await pdfjsLib.getDocument(cfg.pdfUrl).promise;

    for (var pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
      /* eslint-disable no-await-in-loop */
      var page = await pdf.getPage(pageNum);
      var viewport = page.getViewport({ scale: 1.1 });

      var wrap = document.createElement('div');
      wrap.className = 'pdf-page-wrap';
      wrap.style.width = viewport.width + 'px';
      wrap.style.height = viewport.height + 'px';
      wrap.style.margin = '0 auto 12px';

      var canvas = document.createElement('canvas');
      canvas.width = viewport.width;
      canvas.height = viewport.height;
      wrap.appendChild(canvas);
      shell.appendChild(wrap);

      var ctx = canvas.getContext('2d');
      await page.render({ canvasContext: ctx, viewport: viewport }).promise;

      if (pageNum === cfg.highlight.page) {
        var marker = document.createElement('div');
        marker.className = 'sig-marker';
        marker.style.left = cfg.highlight.x + '%';
        marker.style.top = cfg.highlight.y + '%';
        marker.style.width = cfg.highlight.w + '%';
        marker.style.borderColor = '#f59e0b';
        marker.style.color = '#b45309';
        marker.textContent = cfg.isTurn ? 'จุดเซ็นของคุณ' : 'จุดเซ็นของคุณ (รอคิว)';
        wrap.appendChild(marker);
        wrap.scrollIntoView({ block: 'center' });
      }
    }
  }

  function setupSignaturePad() {
    var canvasEl = document.getElementById('signaturePad');
    if (!canvasEl || typeof SignaturePad === 'undefined') return;

    function resizeCanvas() {
      var ratio = Math.max(window.devicePixelRatio || 1, 1);
      var rect = canvasEl.getBoundingClientRect();
      canvasEl.width = rect.width * ratio;
      canvasEl.height = rect.height * ratio;
      canvasEl.getContext('2d').scale(ratio, ratio);
      pad.clear();
    }

    var pad = new SignaturePad(canvasEl, { backgroundColor: 'rgba(0,0,0,0)', penColor: '#1e3a8a' });
    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);

    document.getElementById('clearPadBtn').addEventListener('click', function () {
      pad.clear();
    });

    var form = document.getElementById('signForm');
    var errorBox = document.getElementById('signError');
    form.addEventListener('submit', function (e) {
      if (pad.isEmpty()) {
        e.preventDefault();
        errorBox.style.display = 'block';
        return;
      }
      errorBox.style.display = 'none';
      document.getElementById('signatureData').value = pad.toDataURL('image/png');
    });
  }

  renderPdfWithHighlight();
  setupSignaturePad();
})();
