<?php
// auth/register.php

require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$errors = [];
$name = '';
$email = '';
$role = 'owner';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role = 'owner';

    if ($name === '') {
        $errors['name'] = 'Full name is required.';
    }

    if ($email === '') {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    if ($password === '') {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    }

    if ($confirmPassword === '') {
        $errors['confirm_password'] = 'Please confirm your password.';
    } elseif ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $checkStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $checkStmt->execute(['email' => $email]);

        if ($checkStmt->fetch()) {
            $errors['email'] = 'Email is already registered.';
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            $userStmt = $pdo->prepare(
                'INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)'
            );
            $userStmt->execute([
                'name' => $name,
                'email' => $email,
                'password' => $hashedPassword,
                'role' => $role,
            ]);

            $userId = (int) $pdo->lastInsertId();

            if ($role === 'owner') {
                $ownerStmt = $pdo->prepare(
                    'INSERT INTO owners (user_id, phone, address) VALUES (:user_id, :phone, :address)'
                );
                $ownerStmt->execute([
                    'user_id' => $userId,
                    'phone' => '',
                    'address' => '',
                ]);
            }

            $pdo->commit();

            set_flash('success', 'Registration successful. You can now log in.');
            header('Location: ' . BASE_URL . 'auth/login.php');
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors['general'] = 'Registration failed. Please try again.';
        }
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
            <p class="auth-kicker"><?php echo htmlspecialchars('Owner access', ENT_QUOTES, 'UTF-8'); ?></p>
            <h1><?php echo htmlspecialchars('Create a simple portal for your pet care.', ENT_QUOTES, 'UTF-8'); ?></h1>
            <p><?php echo htmlspecialchars('Register as an owner to manage your animals, request appointments, and view health records shared by the clinic.', ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <div class="auth-panel-list">
            <span><?php echo vet_icon('animal'); ?> <?php echo htmlspecialchars('Animal profiles', ENT_QUOTES, 'UTF-8'); ?></span>
            <span><?php echo vet_icon('calendar'); ?> <?php echo htmlspecialchars('Appointment requests', ENT_QUOTES, 'UTF-8'); ?></span>
            <span><?php echo vet_icon('clipboard'); ?> <?php echo htmlspecialchars('Health record access', ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
    </section>

    <section class="auth-card card">
        <div class="card-body">
            <div class="auth-card-header">
                <p class="auth-kicker"><?php echo htmlspecialchars('Owner registration', ENT_QUOTES, 'UTF-8'); ?></p>
                <h2><?php echo htmlspecialchars('Create your account', ENT_QUOTES, 'UTF-8'); ?></h2>
            </div>

            <?php if (isset($errors['general'])): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($errors['general'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?php echo htmlspecialchars(app_url('auth/register.php'), ENT_QUOTES, 'UTF-8'); ?>" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="mb-3 form-floating-custom">
                    <input type="text" name="name" id="name"
                           class="form-control <?php echo htmlspecialchars(isset($errors['name']) ? 'is-invalid' : '', ENT_QUOTES, 'UTF-8'); ?>"
                           placeholder=" "
                           value="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>">
                    <label for="name" class="form-label">
                        <?php echo htmlspecialchars('Full name', ENT_QUOTES, 'UTF-8'); ?>
                    </label>
                    <?php if (isset($errors['name'])): ?>
                        <div class="invalid-feedback">
                            <?php echo htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-3 form-floating-custom">
                    <input type="email" name="email" id="email"
                           class="form-control <?php echo htmlspecialchars(isset($errors['email']) ? 'is-invalid' : '', ENT_QUOTES, 'UTF-8'); ?>"
                           placeholder=" "
                           value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>">
                    <label for="email" class="form-label">
                        <?php echo htmlspecialchars('Email address', ENT_QUOTES, 'UTF-8'); ?>
                    </label>
                    <?php if (isset($errors['email'])): ?>
                        <div class="invalid-feedback">
                            <?php echo htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-3 form-floating-custom">
                    <input type="password" name="password" id="password"
                           class="form-control <?php echo htmlspecialchars(isset($errors['password']) ? 'is-invalid' : '', ENT_QUOTES, 'UTF-8'); ?>"
                           placeholder=" ">
                    <label for="password" class="form-label">
                        <?php echo htmlspecialchars('Password', ENT_QUOTES, 'UTF-8'); ?>
                    </label>
                    <?php if (isset($errors['password'])): ?>
                        <div class="invalid-feedback">
                            <?php echo htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-3 form-floating-custom">
                    <input type="password" name="confirm_password" id="confirm_password"
                           class="form-control <?php echo htmlspecialchars(isset($errors['confirm_password']) ? 'is-invalid' : '', ENT_QUOTES, 'UTF-8'); ?>"
                           placeholder=" ">
                    <label for="confirm_password" class="form-label">
                        <?php echo htmlspecialchars('Confirm password', ENT_QUOTES, 'UTF-8'); ?>
                    </label>
                    <?php if (isset($errors['confirm_password'])): ?>
                        <div class="invalid-feedback">
                            <?php echo htmlspecialchars($errors['confirm_password'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <?php echo htmlspecialchars('Create Account', ENT_QUOTES, 'UTF-8'); ?>
                </button>
            </form>

            <p class="auth-switch">
                <?php echo htmlspecialchars('Already registered?', ENT_QUOTES, 'UTF-8'); ?>
                <a href="<?php echo htmlspecialchars(app_url('auth/login.php'), ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars('Sign in', ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </p>
            </div>
    </section>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
