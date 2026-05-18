<?php
// includes/header.php

require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPage = $_SERVER['PHP_SELF'] ?? '';
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['name'] ?? '';
$userRole = $_SESSION['role'] ?? '';

function is_active_link($path, $currentPage)
{
    $page = basename((string) $currentPage);
    $section = str_ends_with($path, '.php') ? substr($path, 0, -4) : $path;

    return ($page === $path || str_starts_with($page, $section . '_')) ? 'active' : '';
}

function get_initials($name)
{
    $name = trim((string) $name);

    if ($name === '') {
        return 'U';
    }

    $parts = preg_split('/\s+/', $name);
    $initials = '';

    foreach ($parts as $part) {
        if ($part !== '') {
            $initials .= strtoupper(substr($part, 0, 1));
        }

        if (strlen($initials) >= 2) {
            break;
        }
    }

    return $initials !== '' ? $initials : 'U';
}

$adminLinks = [
    ['label' => 'Dashboard', 'url' => BASE_URL . 'pages/dashboard.php', 'match' => 'dashboard.php'],
    ['label' => 'Animals', 'url' => BASE_URL . 'pages/animals.php', 'match' => 'animals.php'],
    ['label' => 'Appointments', 'url' => BASE_URL . 'pages/appointments.php', 'match' => 'appointments.php'],
    ['label' => 'Health Records', 'url' => BASE_URL . 'pages/health_records.php', 'match' => 'health_records.php'],
    ['label' => 'Owners', 'url' => BASE_URL . 'pages/owners.php', 'match' => 'owners.php'],
];

$staffLinks = [
    ['label' => 'Dashboard', 'url' => BASE_URL . 'pages/dashboard.php', 'match' => 'dashboard.php'],
    ['label' => 'Animals', 'url' => BASE_URL . 'pages/animals.php', 'match' => 'animals.php'],
    ['label' => 'Appointments', 'url' => BASE_URL . 'pages/appointments.php', 'match' => 'appointments.php'],
    ['label' => 'Health Records', 'url' => BASE_URL . 'pages/health_records.php', 'match' => 'health_records.php'],
];

$ownerLinks = [
    ['label' => 'Dashboard', 'url' => BASE_URL . 'pages/dashboard.php', 'match' => 'dashboard.php'],
    ['label' => 'My Animals', 'url' => BASE_URL . 'pages/animals.php', 'match' => 'animals.php'],
    ['label' => 'My Appointments', 'url' => BASE_URL . 'pages/appointments.php', 'match' => 'appointments.php'],
    ['label' => 'My Records', 'url' => BASE_URL . 'pages/health_records.php', 'match' => 'health_records.php'],
];

$navLinks = $ownerLinks;

if ($userRole === 'admin') {
    $navLinks = $adminLinks;
} elseif ($userRole === 'staff') {
    $navLinks = $staffLinks;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars('VetTrack', ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo htmlspecialchars(BASE_URL . 'assets/css/style.css', ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
</head>
<body>
<div class="page-loader" id="pageLoader" aria-hidden="true"></div>
<?php if ($isLoggedIn): ?>
    <button class="btn btn-primary d-md-none m-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar">
        <?php echo htmlspecialchars('Menu', ENT_QUOTES, 'UTF-8'); ?>
    </button>

    <aside class="sidebar d-none d-md-block position-fixed top-0 start-0 vh-100 p-3">
        <div class="sidebar-brand">
            <?php echo vet_icon('paw'); ?>
            <span><?php echo htmlspecialchars('VetTrack', ENT_QUOTES, 'UTF-8'); ?></span>
        </div>

        <div class="d-flex align-items-center mb-4">
            <div class="avatar-circle d-flex align-items-center justify-content-center me-2">
                <?php echo htmlspecialchars(get_initials($userName), ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <div>
                <div class="fw-bold">
                    <?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <small class="text-white-50">
                    <?php echo htmlspecialchars(ucfirst($userRole), ENT_QUOTES, 'UTF-8'); ?>
                </small>
            </div>
        </div>

        <nav class="nav flex-column">
            <?php foreach ($navLinks as $link): ?>
                <a class="nav-link <?php echo htmlspecialchars(is_active_link($link['match'], $currentPage), ENT_QUOTES, 'UTF-8'); ?>"
                   href="<?php echo htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php endforeach; ?>

            <hr class="border-light">

            <a class="nav-link <?php echo htmlspecialchars(is_active_link('profile.php', $currentPage), ENT_QUOTES, 'UTF-8'); ?>" href="<?php echo htmlspecialchars(BASE_URL . 'pages/profile.php', ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars('Profile', ENT_QUOTES, 'UTF-8'); ?>
            </a>
            <a class="nav-link" href="<?php echo htmlspecialchars(BASE_URL . 'auth/logout.php', ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars('Logout', ENT_QUOTES, 'UTF-8'); ?>
            </a>
        </nav>
    </aside>

    <div class="offcanvas offcanvas-start sidebar-mobile" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="mobileSidebarLabel">
                <span class="sidebar-brand mb-0">
                    <?php echo vet_icon('paw'); ?>
                    <span><?php echo htmlspecialchars('VetTrack', ENT_QUOTES, 'UTF-8'); ?></span>
                </span>
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="<?php echo htmlspecialchars('Close', ENT_QUOTES, 'UTF-8'); ?>"></button>
        </div>
        <div class="offcanvas-body">
            <div class="d-flex align-items-center mb-4">
                <div class="avatar-circle d-flex align-items-center justify-content-center me-2">
                    <?php echo htmlspecialchars(get_initials($userName), ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <div>
                    <div class="fw-bold">
                        <?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <small class="text-white-50">
                        <?php echo htmlspecialchars(ucfirst($userRole), ENT_QUOTES, 'UTF-8'); ?>
                    </small>
                </div>
            </div>

            <nav class="nav flex-column">
                <?php foreach ($navLinks as $link): ?>
                    <a class="nav-link <?php echo htmlspecialchars(is_active_link($link['match'], $currentPage), ENT_QUOTES, 'UTF-8'); ?>"
                       href="<?php echo htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php endforeach; ?>

                <hr class="border-light">

                <a class="nav-link <?php echo htmlspecialchars(is_active_link('profile.php', $currentPage), ENT_QUOTES, 'UTF-8'); ?>" href="<?php echo htmlspecialchars(BASE_URL . 'pages/profile.php', ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars('Profile', ENT_QUOTES, 'UTF-8'); ?>
                </a>
                <a class="nav-link" href="<?php echo htmlspecialchars(BASE_URL . 'auth/logout.php', ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars('Logout', ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </nav>
        </div>
    </div>

    <main class="main-content">
        <div class="container-fluid py-4">
            <?php include __DIR__ . '/flash.php'; ?>
<?php else: ?>
    <main>
        <div class="container py-4">
            <?php include __DIR__ . '/flash.php'; ?>
<?php endif; ?>
