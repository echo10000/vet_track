<?php
// auth/login.php

require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors['general'] = 'Invalid email or password.';
    } else {
        $stmt = $pdo->prepare('SELECT id, name, email, password, role FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];

            set_flash('success', 'Logged in successfully.');

            if (in_array($user['role'], ['admin', 'staff'], true)) {
                header('Location: ' . BASE_URL . 'pages/dashboard.php');
                exit;
            }

            header('Location: ' . BASE_URL . 'pages/animals.php');
            exit;
        }

        $errors['general'] = 'Invalid email or password.';
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="auth-shell">
    <section class="auth-panel">
        <a class="auth-brand" href="<?php echo htmlspecialchars(app_url(), ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo vet_icon('paw'); ?>
            <span><?php echo htmlspecialchars('VetTrack', ENT_QUOTES, 'UTF-8'); ?></span>
        </a>
        <div>
            <p class="auth-kicker"><?php echo htmlspecialchars('Clinic workspace', ENT_QUOTES, 'UTF-8'); ?></p>
            <h1><?php echo htmlspecialchars('Welcome back to organized veterinary care.', ENT_QUOTES, 'UTF-8'); ?></h1>
            <p><?php echo htmlspecialchars('Sign in to manage animal profiles, appointments, owners, and health records from one focused dashboard.', ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <div class="auth-panel-list">
            <span><?php echo vet_icon('calendar'); ?> <?php echo htmlspecialchars('Appointment tracking', ENT_QUOTES, 'UTF-8'); ?></span>
            <span><?php echo vet_icon('clipboard'); ?> <?php echo htmlspecialchars('Clinical history', ENT_QUOTES, 'UTF-8'); ?></span>
            <span><?php echo vet_icon('users'); ?> <?php echo htmlspecialchars('Role-based access', ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
    </section>

    <section class="auth-card card">
        <div class="card-body">
            <div class="auth-card-header">
                <p class="auth-kicker"><?php echo htmlspecialchars('Secure sign in', ENT_QUOTES, 'UTF-8'); ?></p>
                <h2><?php echo htmlspecialchars('Log in to VetTrack', ENT_QUOTES, 'UTF-8'); ?></h2>
            </div>

            <?php if (isset($errors['general'])): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($errors['general'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?php echo htmlspecialchars(app_url('auth/login.php'), ENT_QUOTES, 'UTF-8'); ?>" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="mb-3 form-floating-custom">
                    <input type="email" name="email" id="email" class="form-control"
                           placeholder=" "
                           value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>">
                    <label for="email" class="form-label">
                        <?php echo htmlspecialchars('Email address', ENT_QUOTES, 'UTF-8'); ?>
                    </label>
                </div>

                <div class="mb-3 form-floating-custom">
                    <input type="password" name="password" id="password" class="form-control" placeholder=" ">
                    <label for="password" class="form-label">
                        <?php echo htmlspecialchars('Password', ENT_QUOTES, 'UTF-8'); ?>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <?php echo htmlspecialchars('Sign In', ENT_QUOTES, 'UTF-8'); ?>
                </button>
            </form>

            <p class="auth-switch">
                <?php echo htmlspecialchars('New owner?', ENT_QUOTES, 'UTF-8'); ?>
                <a href="<?php echo htmlspecialchars(app_url('auth/register.php'), ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars('Create an account', ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </p>
            </div>
    </section>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
