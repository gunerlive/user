<?php if (!empty($extraScripts) && is_array($extraScripts)): ?>
  <?php foreach ($extraScripts as $src): ?>
<script src="<?= \ESign\Helpers::e($src) ?>"></script>
  <?php endforeach; ?>
<?php endif; ?>
</body>
</html>
