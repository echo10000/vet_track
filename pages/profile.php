<?php
// pages/profile.php

require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$userId = (int) ($_SESSION['user_id'] ?? 0);
$profileErrors = [];
$passwordErrors = [];

$stmt = $pdo->prepare('SELECT id, name, email, password, role FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    echo htmlspecialchars('User not found.', ENT_QUOTES, 'UTF-8');
    die();
}

$name = $user['name'];
$email = $user['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    $formType = $_POST['form_type'] ?? '';

    if ($formType === 'profile') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($name === '') {
            $profileErrors['name'] = 'Name is required.';
        }

        if ($email === '') {
            $profileErrors['email'] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $profileErrors['email'] = 'Please enter a valid email address.';
        } else {
            $checkStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1');
            $checkStmt->execute([
                'email' => $email,
                'id' => $userId,
            ]);

            if ($checkStmt->fetch()) {
                $profileErrors['email'] = 'Email is already taken by another user.';
            }
        }

        if (empty($profileErrors)) {
            $updateStmt = $pdo->prepare('UPDATE users SET name = :name, email = :email WHERE id = :id');
            $updateStmt->execute([
                'name' => $name,
                'email' => $email,
                'id' => $userId,
            ]);

            $_SESSION['name'] = $name;

            set_flash('success', 'Profile updated successfully.');
            header('Location: ' . BASE_URL . 'pages/profile.php');
            exit;
        }
    }

    if ($formType === 'password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmNewPassword = $_POST['confirm_new_password'] ?? '';

        if ($currentPassword === '') {
            $passwordErrors['current_password'] = 'Current password is required.';
        } elseif (!password_verify($currentPassword, $user['password'])) {
            $passwordErrors['current_password'] = 'Current password is incorrect.';
        }

        if ($newPassword === '') {
            $passwordErrors['new_password'] = 'New password is required.';
        } elseif (strlen($newPassword) < 8) {
            $passwordErrors['new_password'] = 'New password must be at least 8 characters.';
        }

        if ($confirmNewPassword === '') {
            $passwordErrors['confirm_new_password'] = 'Please confirm your new password.';
        } elseif ($newPassword !== $confirmNewPassword) {
            $passwordErrors['confirm_new_password'] = 'New passwords do not match.';
        }

        if (empty($passwordErrors)) {
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

            $updatePasswordStmt = $pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
            $updatePasswordStmt->execute([
                'password' => $hashedPassword,
                'id' => $userId,
            ]);

            set_flash('success', 'Password changed successfully.');
            header('Location: ' . app_url('pages/profile.php'));
            exit;
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="entity-page">
    <section class="entity-hero">
        <p class="entity-kicker">
            <?php echo htmlspecialchars('Account settings', ENT_QUOTES, 'UTF-8'); ?>
        </p>
        <h1 class="entity-title">
            <?php echo htmlspecialchars('My Profile', ENT_QUOTES, 'UTF-8'); ?>
        </h1>
        <p class="entity-subtitle">
            <?php echo htmlspecialchars('Manage your identity, sign-in email, and password for secure VetTrack access.', ENT_QUOTES, 'UTF-8'); ?>
        </p>
    </section>

    <div class="profile-grid">
        <section class="profile-card card">
            <div class="card-body">
                <div class="auth-card-header">
                    <p class="entity-kicker">
                        <?php echo htmlspecialchars('Profile details', ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                    <h2>
                        <?php echo htmlspecialchars('Update Name and Email', ENT_QUOTES, 'UTF-8'); ?>
                    </h2>
                </div>

                <form method="post" action="<?php echo htmlspecialchars(app_url('pages/profile.php'), ENT_QUOTES, 'UTF-8'); ?>" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="form_type" value="profile">

                    <div class="mb-3 form-floating-custom">
                        <input type="text" name="name" id="name"
                               class="form-control <?php echo htmlspecialchars(isset($profileErrors['name']) ? 'is-invalid' : '', ENT_QUOTES, 'UTF-8'); ?>"
                               placeholder=" "
                               value="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>">
                        <label for="name" class="form-label">
                            <?php echo htmlspecialchars('Name', ENT_QUOTES, 'UTF-8'); ?>
                        </label>
                        <?php if (isset($profileErrors['name'])): ?>
                            <div class="invalid-feedback">
                                <?php echo htmlspecialchars($profileErrors['name'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3 form-floating-custom">
                        <input type="email" name="email" id="email"
                               class="form-control <?php echo htmlspecialchars(isset($profileErrors['email']) ? 'is-invalid' : '', ENT_QUOTES, 'UTF-8'); ?>"
                               placeholder=" "
                               value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>">
                        <label for="email" class="form-label">
                            <?php echo htmlspecialchars('Email', ENT_QUOTES, 'UTF-8'); ?>
                        </label>
                        <?php if (isset($profileErrors['email'])): ?>
                            <div class="invalid-feedback">
                                <?php echo htmlspecialchars($profileErrors['email'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?php echo htmlspecialchars('Save Profile', ENT_QUOTES, 'UTF-8'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <section class="profile-card card">
            <div class="card-body">
                <div class="auth-card-header">
                    <p class="entity-kicker">
                        <?php echo htmlspecialchars('Security', ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                    <h2>
                        <?php echo htmlspecialchars('Change Password', ENT_QUOTES, 'UTF-8'); ?>
                    </h2>
                </div>

                <form method="post" action="<?php echo htmlspecialchars(app_url('pages/profile.php'), ENT_QUOTES, 'UTF-8'); ?>" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="form_type" value="password">

                    <div class="mb-3 form-floating-custom">
                        <input type="password" name="current_password" id="current_password"
                               class="form-control <?php echo htmlspecialchars(isset($passwordErrors['current_password']) ? 'is-invalid' : '', ENT_QUOTES, 'UTF-8'); ?>"
                               placeholder=" ">
                        <label for="current_password" class="form-label">
                            <?php echo htmlspecialchars('Current Password', ENT_QUOTES, 'UTF-8'); ?>
                        </label>
                        <?php if (isset($passwordErrors['current_password'])): ?>
                            <div class="invalid-feedback">
                                <?php echo htmlspecialchars($passwordErrors['current_password'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3 form-floating-custom">
                        <input type="password" name="new_password" id="new_password"
                               class="form-control <?php echo htmlspecialchars(isset($passwordErrors['new_password']) ? 'is-invalid' : '', ENT_QUOTES, 'UTF-8'); ?>"
                               placeholder=" ">
                        <label for="new_password" class="form-label">
                            <?php echo htmlspecialchars('New Password', ENT_QUOTES, 'UTF-8'); ?>
                        </label>
                        <?php if (isset($passwordErrors['new_password'])): ?>
                            <div class="invalid-feedback">
                                <?php echo htmlspecialchars($passwordErrors['new_password'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3 form-floating-custom">
                        <input type="password" name="confirm_new_password" id="confirm_new_password"
                               class="form-control <?php echo htmlspecialchars(isset($passwordErrors['confirm_new_password']) ? 'is-invalid' : '', ENT_QUOTES, 'UTF-8'); ?>"
                               placeholder=" ">
                        <label for="confirm_new_password" class="form-label">
                            <?php echo htmlspecialchars('Confirm New Password', ENT_QUOTES, 'UTF-8'); ?>
                        </label>
                        <?php if (isset($passwordErrors['confirm_new_password'])): ?>
                            <div class="invalid-feedback">
                                <?php echo htmlspecialchars($passwordErrors['confirm_new_password'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?php echo htmlspecialchars('Change Password', ENT_QUOTES, 'UTF-8'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
