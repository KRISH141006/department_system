</main>
<footer style="text-align:center; padding: 2rem; color: #666; font-size: 0.9rem;">
  &copy; <?= date('Y') ?> Department System. All rights reserved.
</footer>
<?php
$calendar_picker_path = __DIR__ . '/../../assets/js/calendar-picker.js';
if (isset($base_path) && file_exists($calendar_picker_path)):
?>
<script src="<?= $base_path ?>/assets/js/calendar-picker.js?v=<?= filemtime($calendar_picker_path) ?>"></script>
<?php endif; ?>
<?php
$app_ux_path = __DIR__ . '/../../assets/js/app-ux.js';
if (isset($base_path) && file_exists($app_ux_path)):
?>
<script src="<?= $base_path ?>/assets/js/app-ux.js?v=<?= filemtime($app_ux_path) ?>"></script>
<?php endif; ?>
</body>
</html>
