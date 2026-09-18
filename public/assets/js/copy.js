document.addEventListener('click', function (e) {
  var btn = e.target.closest('[data-copy]');
  if (!btn) return;
  var text = btn.getAttribute('data-copy');
  var done = function () {
    var original = btn.textContent;
    btn.textContent = 'คัดลอกแล้ว!';
    setTimeout(function () { btn.textContent = original; }, 1500);
  };
  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(text).then(done).catch(function () {
      window.prompt('คัดลอกลิงก์นี้ด้วยตนเอง:', text);
    });
  } else {
    window.prompt('คัดลอกลิงก์นี้ด้วยตนเอง:', text);
  }
});
