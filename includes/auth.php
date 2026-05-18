<?php
// includes/auth.php

require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login()
{
    if (!isset($_SESSION['user_id'])) {
        set_flash('warning', 'Please log in first.');
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit;
    }
}

function require_role($roles)
{
    if (is_string($roles)) {
        $roles = [$roles];
    }

    $userRole = $_SESSION['role'] ?? '';

    if (!in_array($userRole, $roles, true)) {
        set_flash('danger', 'You are not authorized to access that page.');
        header('Location: ' . BASE_URL . 'pages/dashboard.php');
        exit;
    }
}
