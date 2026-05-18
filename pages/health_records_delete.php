<?php
// pages/health_records_delete.php

require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash('warning', 'Use the delete button to remove a health record.');
    header('Location: ' . app_url('pages/health_records.php'));
    exit;
}

verify_csrf_token();

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if ($id <= 0) {
    set_flash('danger', 'Invalid health record.');
    header('Location: ' . app_url('pages/health_records.php'));
    exit;
}

$stmt = $pdo->prepare('DELETE FROM health_records WHERE id = :id');
$stmt->execute(['id' => $id]);

if ($stmt->rowCount() > 0) {
    set_flash('success', 'Health record deleted successfully.');
} else {
    set_flash('warning', 'Health record was not found.');
}

header('Location: ' . app_url('pages/health_records.php'));
exit;
