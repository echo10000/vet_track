<?php
// includes/functions.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function set_flash($type, $message)
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function get_flash()
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function app_url($path = '')
{
    require_once __DIR__ . '/../config/paths.php';

    return BASE_URL . ltrim((string) $path, '/');
}

function vet_icon($name)
{
    $icons = [
        'paw' => '<path d="M11.1 7.2c1.05-.12 1.75-1.22 1.57-2.45s-1.17-2.13-2.22-2.01-1.75 1.22-1.57 2.45 1.17 2.13 2.22 2.01Z"/><path d="M6.62 8.2c.96-.42 1.28-1.68.72-2.81S5.56 3.66 4.6 4.08 3.32 5.76 3.88 6.89s1.78 1.73 2.74 1.31Z"/><path d="M17.38 8.2c.96.42 2.18-.18 2.74-1.31s.24-2.39-.72-2.81-2.18.18-2.74 1.31-.24 2.39.72 2.81Z"/><path d="M12 10.2c-2.85 0-5.7 2.64-5.7 5.28 0 1.61 1.12 2.37 2.58 2.37 1.06 0 1.78-.44 3.12-.44s2.06.44 3.12.44c1.46 0 2.58-.76 2.58-2.37 0-2.64-2.85-5.28-5.7-5.28Z"/>',
        'edit' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
        'trash' => '<path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v5"/><path d="M14 11v5"/>',
        'eye' => '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        'calendar' => '<path d="M7 2v4"/><path d="M17 2v4"/><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18"/>',
        'clipboard' => '<path d="M9 3h6l1 2h3v17H5V5h3Z"/><path d="M9 9h6"/><path d="M9 13h6"/><path d="M9 17h4"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'animal' => '<path d="M12 10.2c-2.85 0-5.7 2.64-5.7 5.28 0 1.61 1.12 2.37 2.58 2.37 1.06 0 1.78-.44 3.12-.44s2.06.44 3.12.44c1.46 0 2.58-.76 2.58-2.37 0-2.64-2.85-5.28-5.7-5.28Z"/><circle cx="5.5" cy="7" r="2"/><circle cx="10" cy="5" r="2"/><circle cx="14" cy="5" r="2"/><circle cx="18.5" cy="7" r="2"/>',
    ];

    if (!isset($icons[$name])) {
        return '';
    }

    return '<svg class="icon icon-' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . $icons[$name] . '</svg>';
}

function generate_csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf_token()
{
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    $postedToken = $_POST['csrf_token'] ?? '';

    if ($sessionToken === '' || $postedToken === '' || !hash_equals($sessionToken, $postedToken)) {
        http_response_code(403);
        echo htmlspecialchars('403 Forbidden', ENT_QUOTES, 'UTF-8');
        die();
    }
}
