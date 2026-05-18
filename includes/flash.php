<?php
// includes/flash.php

$flash = get_flash();

if (!$flash) {
    return;
}

$type = $flash['type'] ?? 'info';
$message = $flash['message'] ?? '';
$allowedTypes = ['success', 'danger', 'warning', 'info'];

if (!in_array($type, $allowedTypes, true)) {
    $type = 'info';
}

if ($message === '') {
    return;
}
?>

<div class="alert alert-<?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?> alert-dismissible fade show global-feedback" role="alert">
    <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?php echo htmlspecialchars('Close', ENT_QUOTES, 'UTF-8'); ?>"></button>
</div>
