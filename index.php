<?php
// index.php

require_once __DIR__ . '/config/paths.php';
require_once __DIR__ . '/includes/functions.php';

$isLoggedIn = isset($_SESSION['user_id']);
$dashboardUrl = app_url('pages/dashboard.php');
$loginUrl = app_url('auth/login.php');
$registerUrl = app_url('auth/register.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars('VetTrack | Veterinary Management System', ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo htmlspecialchars(app_url('assets/css/style.css'), ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
</head>
<body class="landing-page">
<div class="page-loader" id="pageLoader" aria-hidden="true"></div>

<header class="landing-nav">
    <a class="landing-brand" href="<?php echo htmlspecialchars(app_url(), ENT_QUOTES, 'UTF-8'); ?>">
        <?php echo vet_icon('paw'); ?>
        <span><?php echo htmlspecialchars('VetTrack', ENT_QUOTES, 'UTF-8'); ?></span>
    </a>

    <nav class="landing-nav-actions" aria-label="<?php echo htmlspecialchars('Landing navigation', ENT_QUOTES, 'UTF-8'); ?>">
        <?php if ($isLoggedIn): ?>
            <a href="<?php echo htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary">
                <?php echo htmlspecialchars('Open Dashboard', ENT_QUOTES, 'UTF-8'); ?>
            </a>
        <?php else: ?>
            <a href="<?php echo htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-primary">
                <?php echo htmlspecialchars('Sign In', ENT_QUOTES, 'UTF-8'); ?>
            </a>
            <a href="<?php echo htmlspecialchars($registerUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary">
                <?php echo htmlspecialchars('Get Started', ENT_QUOTES, 'UTF-8'); ?>
            </a>
        <?php endif; ?>
    </nav>
</header>

<main>
    <section class="landing-hero">
        <div class="landing-hero-media" aria-hidden="true">
            <div class="clinic-photo-panel">
                <div class="clinic-photo-toolbar">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <div class="clinic-photo-scene">
                    <div class="clinic-portrait clinic-portrait-large"></div>
                    <div class="clinic-portrait clinic-portrait-small"></div>
                    <div class="clinic-pet-card">
                        <?php echo vet_icon('paw'); ?>
                        <div>
                            <strong><?php echo htmlspecialchars('Milo', ENT_QUOTES, 'UTF-8'); ?></strong>
                            <span><?php echo htmlspecialchars('Annual wellness check', ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="landing-hero-content">
            <p class="landing-kicker"><?php echo htmlspecialchars('Animal care records, appointments, and owner access in one place', ENT_QUOTES, 'UTF-8'); ?></p>
            <h1><?php echo htmlspecialchars('VetTrack', ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="landing-lead">
                <?php echo htmlspecialchars('A focused veterinary management system for clinics that need organized patient profiles, clean appointment handling, and role-based access for staff, admins, and pet owners.', ENT_QUOTES, 'UTF-8'); ?>
            </p>
            <div class="landing-hero-actions">
                <?php if ($isLoggedIn): ?>
                    <a href="<?php echo htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary btn-lg">
                        <?php echo htmlspecialchars('Go to Dashboard', ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php else: ?>
                    <a href="<?php echo htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary btn-lg">
                        <?php echo htmlspecialchars('Sign In', ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                    <a href="<?php echo htmlspecialchars($registerUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary btn-lg">
                        <?php echo htmlspecialchars('Create Owner Account', ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="landing-section">
        <div class="landing-section-header">
            <h2><?php echo htmlspecialchars('Built Around Clinic Workflows', ENT_QUOTES, 'UTF-8'); ?></h2>
            <p><?php echo htmlspecialchars('Fast enough for the front desk, structured enough for health records, and simple enough for owners to use.', ENT_QUOTES, 'UTF-8'); ?></p>
        </div>

        <div class="landing-feature-grid">
            <article class="landing-feature-card">
                <div class="landing-feature-icon"><?php echo vet_icon('animal'); ?></div>
                <h3><?php echo htmlspecialchars('Animal Profiles', ENT_QUOTES, 'UTF-8'); ?></h3>
                <p><?php echo htmlspecialchars('Keep species, breed, age, weight, owner details, and related records easy to scan.', ENT_QUOTES, 'UTF-8'); ?></p>
            </article>

            <article class="landing-feature-card">
                <div class="landing-feature-icon"><?php echo vet_icon('calendar'); ?></div>
                <h3><?php echo htmlspecialchars('Appointments', ENT_QUOTES, 'UTF-8'); ?></h3>
                <p><?php echo htmlspecialchars('Book visits, track status, assign staff, and keep upcoming clinic work visible.', ENT_QUOTES, 'UTF-8'); ?></p>
            </article>

            <article class="landing-feature-card">
                <div class="landing-feature-icon"><?php echo vet_icon('clipboard'); ?></div>
                <h3><?php echo htmlspecialchars('Health Records', ENT_QUOTES, 'UTF-8'); ?></h3>
                <p><?php echo htmlspecialchars('Record diagnoses, treatments, notes, and appointment-linked clinical history.', ENT_QUOTES, 'UTF-8'); ?></p>
            </article>
        </div>
    </section>

    <section class="landing-workflow">
        <div>
            <p class="landing-kicker"><?php echo htmlspecialchars('Role-based access', ENT_QUOTES, 'UTF-8'); ?></p>
            <h2><?php echo htmlspecialchars('Clear views for every user', ENT_QUOTES, 'UTF-8'); ?></h2>
        </div>

        <div class="landing-role-list">
            <div>
                <strong><?php echo htmlspecialchars('Owners', ENT_QUOTES, 'UTF-8'); ?></strong>
                <span><?php echo htmlspecialchars('Manage their animals, book appointments, and view health records.', ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div>
                <strong><?php echo htmlspecialchars('Staff', ENT_QUOTES, 'UTF-8'); ?></strong>
                <span><?php echo htmlspecialchars('Handle clinic records, appointments, and owner contact information.', ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div>
                <strong><?php echo htmlspecialchars('Admins', ENT_QUOTES, 'UTF-8'); ?></strong>
                <span><?php echo htmlspecialchars('Oversee the full system with dashboard summaries and operational visibility.', ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        </div>
    </section>
</main>

<footer class="landing-footer">
    <span><?php echo htmlspecialchars('VetTrack', ENT_QUOTES, 'UTF-8'); ?></span>
    <span><?php echo htmlspecialchars('Veterinary management made tidy.', ENT_QUOTES, 'UTF-8'); ?></span>
</footer>

<script src="<?php echo htmlspecialchars(app_url('assets/js/main.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
</body>
</html>
