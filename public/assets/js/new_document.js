(function () {
  'use strict';

  var dropzone = document.getElementById('dropzone');
  var dropzoneText = document.getElementById('dropzoneText');
  var fileInput = document.getElementById('pdfFile');
  var signerList = document.getElementById('signerList');
  var template = document.getElementById('signerRowTemplate');
  var addSignerBtn = document.getElementById('addSignerBtn');

  function updateDropzoneLabel() {
    if (fileInput.files && fileInput.files.length > 0) {
      dropzoneText.textContent = 'เลือกไฟล์แล้ว: ' + fileInput.files[0].name + ' (คลิกเพื่อเลือกไฟล์อื่น)';
    }
  }

  dropzone.addEventListener('click', function () {
    fileInput.click();
  });

  fileInput.addEventListener('change', updateDropzoneLabel);

  ['dragenter', 'dragover'].forEach(function (evt) {
    dropzone.addEventListener(evt, function (e) {
      e.preventDefault();
      e.stopPropagation();
      dropzone.classList.add('dragover');
    });
  });

  ['dragleave', 'drop'].forEach(function (evt) {
    dropzone.addEventListener(evt, function (e) {
      e.preventDefault();
      e.stopPropagation();
      dropzone.classList.remove('dragover');
    });
  });

  dropzone.addEventListener('drop', function (e) {
    var files = e.dataTransfer.files;
    if (files && files.length > 0) {
      fileInput.files = files;
      updateDropzoneLabel();
    }
  });

  function renumberSigners() {
    var rows = signerList.querySelectorAll('.signer-row');
    rows.forEach(function (row, idx) {
      var dot = row.querySelector('.signer-order');
      if (dot) {
        dot.textContent = String(idx + 1);
      }
    });
    var removeButtons = signerList.querySelectorAll('.remove-signer-btn');
    removeButtons.forEach(function (btn) {
      btn.disabled = rows.length <= 1;
    });
  }

  function addSignerRow() {
    var fragment = template.content.cloneNode(true);
    var removeBtn = fragment.querySelector('.remove-signer-btn');
    removeBtn.addEventListener('click', function (e) {
      var row = e.target.closest('.signer-row');
      if (signerList.querySelectorAll('.signer-row').length > 1) {
        row.remove();
        renumberSigners();
      }
    });
    signerList.appendChild(fragment);
    renumberSigners();
  }

  addSignerBtn.addEventListener('click', addSignerRow);

  // เริ่มต้นด้วยผู้ลงนาม 2 คนเป็นตัวอย่าง
  addSignerRow();
  addSignerRow();
})();
