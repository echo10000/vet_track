<?php
// pages/owners_form.php

require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
require_role('admin');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$errors = [];

if ($id <= 0) {
    set_flash('danger', 'Invalid owner.');
    header('Location: ' . app_url('pages/owners.php'));
    exit;
}

$stmt = $pdo->prepare(
    'SELECT owners.id, owners.phone, owners.address, users.name
     FROM owners
     INNER JOIN users ON owners.user_id = users.id
     WHERE owners.id = :id
     LIMIT 1'
);
$stmt->execute(['id' => $id]);
$owner = $stmt->fetch();

if (!$owner) {
    set_flash('danger', 'Owner not found.');
    header('Location: ' . app_url('pages/owners.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $owner['phone'] = trim($_POST['phone'] ?? '');
    $owner['address'] = trim($_POST['address'] ?? '');

    if ($owner['phone'] === '') {
        $errors['phone'] = 'Phone is required.';
    }

    if ($owner['address'] === '') {
        $errors['address'] = 'Address is required.';
    }

    if (empty($errors)) {
        $updateStmt = $pdo->prepare(
            'UPDATE owners SET phone = :phone, address = :address WHERE id = :id'
        );
        $updateStmt->execute([
            'phone' => $owner['phone'],
            'address' => $owner['address'],
            'id' => $id,
        ]);

        set_flash('success', 'Owner contact information updated successfully.');
        header('Location: ' . app_url('pages/owners.php'));
        exit;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="entity-page">
    <section class="entity-hero">
        <p class="entity-kicker">
            <?php echo htmlspecialchars('Owner contact', ENT_QUOTES, 'UTF-8'); ?>
        </p>
        <h1 class="entity-title">
            <?php echo htmlspecialchars('Edit Owner Contact Info', ENT_QUOTES, 'UTF-8'); ?>
        </h1>
        <p class="entity-subtitle">
            <?php echo htmlspecialchars('Update phone and address details for ' . $owner['name'] . '.', ENT_QUOTES, 'UTF-8'); ?>
        </p>
    </section>

    <div class="form-shell">
        <section class="form-card card">
            <div class="card-body">
                <form method="post" action="<?php echo htmlspecialchars(app_url('pages/owners_form.php?id=' . $id), ENT_QUOTES, 'UTF-8'); ?>" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="mb-3 form-floating-custom">
                        <input type="text" name="phone" id="phone"
                               class="form-control <?php echo htmlspecialchars(isset($errors['phone']) ? 'is-invalid' : '', ENT_QUOTES, 'UTF-8'); ?>"
                               placeholder=" "
                               value="<?php echo htmlspecialchars($owner['phone'], ENT_QUOTES, 'UTF-8'); ?>">
                        <label for="phone" class="form-label">
                            <?php echo htmlspecialchars('Phone', ENT_QUOTES, 'UTF-8'); ?>
                        </label>
                        <?php if (isset($errors['phone'])): ?>
                            <div class="invalid-feedback">
                                <?php echo htmlspecialchars($errors['phone'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3 form-floating-custom">
                        <textarea name="address" id="address" rows="4"
                                  placeholder=" "
                                  class="form-control <?php echo htmlspecialchars(isset($errors['address']) ? 'is-invalid' : '', ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($owner['address'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                        <label for="address" class="form-label">
                            <?php echo htmlspecialchars('Address', ENT_QUOTES, 'UTF-8'); ?>
                        </label>
                        <?php if (isset($errors['address'])): ?>
                            <div class="invalid-feedback">
                                <?php echo htmlspecialchars($errors['address'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?php echo htmlspecialchars('Update Contact Info', ENT_QUOTES, 'UTF-8'); ?>
                        </button>
                        <a href="<?php echo htmlspecialchars(app_url('pages/owners.php'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary">
                            <?php echo htmlspecialchars('Cancel', ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
