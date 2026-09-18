(function () {
  'use strict';

  var cfg = window.PLACE_SIGNATURES_CONFIG;
  var signers = cfg.signers;
  var selectedId = signers.length > 0 ? signers[0].id : null;
  var pageWraps = {}; // pageNumber -> DOM element

  pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

  var shell = document.getElementById('pdfShell');
  var chipsContainer = document.getElementById('signerChips');
  var finalizeBtn = document.getElementById('finalizeBtn');
  var finalizeResult = document.getElementById('finalizeResult');

  function findSigner(id) {
    for (var i = 0; i < signers.length; i++) {
      if (signers[i].id === id) return signers[i];
    }
    return null;
  }

  function renderChips() {
    chipsContainer.innerHTML = '';
    signers.forEach(function (s) {
      var chip = document.createElement('div');
      chip.className = 'signer-row';
      chip.style.cursor = 'pointer';
      chip.style.border = '2px solid ' + (s.id === selectedId ? s.color : 'transparent');
      chip.innerHTML =
        '<div class="signer-dot" style="background:' + s.color + ';">' + s.order_no + '</div>' +
        '<div style="flex-grow:1; min-width:0;">' +
        '<div style="font-size:13px; font-weight:700; color:#0f172a; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">' + escapeHtml(s.full_name) + '</div>' +
        '<div style="font-size:11px; color:#64748b;">หน้า ' + s.sig_page + '</div>' +
        '</div>';
      chip.addEventListener('click', function () {
        selectedId = s.id;
        renderChips();
        renderMarkers();
      });
      chipsContainer.appendChild(chip);
    });
  }

  function escapeHtml(str) {
    var div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  function clearMarkers() {
    document.querySelectorAll('.sig-marker').forEach(function (el) { el.remove(); });
  }

  function renderMarkers() {
    clearMarkers();
    signers.forEach(function (s) {
      var wrap = pageWraps[s.sig_page];
      if (!wrap) return;
      var marker = document.createElement('div');
      marker.className = 'sig-marker';
      marker.style.left = s.sig_x_pct + '%';
      marker.style.top = s.sig_y_pct + '%';
      marker.style.width = s.sig_w_pct + '%';
      marker.style.borderColor = s.color;
      marker.style.color = s.color;
      marker.style.opacity = s.id === selectedId ? '1' : '0.75';
      marker.textContent = 'จุดเซ็น #' + s.order_no + ' ' + s.full_name;
      wrap.appendChild(marker);
    });
  }

  function onPageClick(pageNumber, wrapEl, evt) {
    if (selectedId === null) return;
    var signer = findSigner(selectedId);
    if (!signer) return;
    var rect = wrapEl.getBoundingClientRect();
    var xPct = ((evt.clientX - rect.left) / rect.width) * 100;
    var yPct = ((evt.clientY - rect.top) / rect.height) * 100;
    signer.sig_page = pageNumber;
    signer.sig_x_pct = Math.max(0, Math.min(95, Math.round(xPct * 10) / 10));
    signer.sig_y_pct = Math.max(0, Math.min(95, Math.round(yPct * 10) / 10));
    renderChips();
    renderMarkers();
  }

  async function renderPdf() {
    var loadingTask = pdfjsLib.getDocument(cfg.pdfUrl);
    var pdf = await loadingTask.promise;
    shell.innerHTML = '';
    shell.style.display = 'block';

    for (var pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
      /* eslint-disable no-await-in-loop */
      var page = await pdf.getPage(pageNum);
      var viewport = page.getViewport({ scale: 1.3 });

      var wrap = document.createElement('div');
      wrap.className = 'pdf-page-wrap';
      wrap.style.width = viewport.width + 'px';
      wrap.style.height = viewport.height + 'px';
      wrap.style.margin = '0 auto 16px';

      var canvas = document.createElement('canvas');
      canvas.width = viewport.width;
      canvas.height = viewport.height;
      wrap.appendChild(canvas);
      shell.appendChild(wrap);

      var pageNumCopy = pageNum;
      wrap.addEventListener('click', function (evt) {
        if (evt.target.classList.contains('sig-marker')) return;
        onPageClick(pageNumCopy, this, evt);
      });

      pageWraps[pageNum] = wrap;

      var ctx = canvas.getContext('2d');
      await page.render({ canvasContext: ctx, viewport: viewport }).promise;
    }

    renderChips();
    renderMarkers();
  }

  finalizeBtn.addEventListener('click', async function () {
    finalizeBtn.disabled = true;
    finalizeBtn.textContent = 'กำลังบันทึก...';
    try {
      var res = await fetch('actions/finalize_document.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          csrf_token: cfg.csrfToken,
          document_id: cfg.documentId,
          signers: signers.map(function (s) {
            return {
              id: s.id,
              sig_page: s.sig_page,
              sig_x_pct: s.sig_x_pct,
              sig_y_pct: s.sig_y_pct,
              sig_w_pct: s.sig_w_pct,
            };
          }),
        }),
      });
      var data = await res.json();
      if (!res.ok || !data.success) {
        throw new Error(data.message || 'บันทึกไม่สำเร็จ');
      }
      finalizeResult.innerHTML =
        '<div class="alert alert-success" style="margin-bottom: 10px;">สร้างลิงก์เรียบร้อย! ส่งลิงก์นี้ให้ผู้ลงนามคนแรก:</div>' +
        '<div class="card" style="padding:12px; word-break:break-all; font-size:13px; margin-bottom:10px;">' + escapeHtml(data.first_signer_link) + '</div>' +
        '<a href="document.php?id=' + cfg.documentId + '" class="btn btn-primary btn-block">ไปที่หน้ารายละเอียดเอกสาร</a>';
      finalizeBtn.style.display = 'none';
    } catch (err) {
      finalizeResult.innerHTML = '<div class="alert alert-danger">' + escapeHtml(err.message) + '</div>';
      finalizeBtn.disabled = false;
      finalizeBtn.textContent = 'เสร็จสิ้น สร้างลิงก์ให้ผู้ลงนาม';
    }
  });

  renderPdf().catch(function (err) {
    shell.innerHTML = '<div class="alert alert-danger">โหลดเอกสารไม่สำเร็จ: ' + escapeHtml(err.message) + '</div>';
  });
})();
