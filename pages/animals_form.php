<?php
// pages/animals_form.php

require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$role = $_SESSION['role'] ?? '';
$userId = (int) ($_SESSION['user_id'] ?? 0);
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $id > 0;
$errors = [];

$speciesOptions = ['dog', 'cat', 'bird', 'rabbit', 'other'];

if ($role === 'staff') {
    set_flash('danger', 'Staff can view animal profiles but cannot add or edit them.');
    header('Location: ' . app_url('pages/animals.php'));
    exit;
}

$animal = [
    'name' => '',
    'species' => 'dog',
    'breed' => '',
    'age' => '',
    'weight' => '',
    'owner_id' => '',
];

$owners = [];
$ownerIdForUser = 0;

if ($role === 'owner') {
    $ownerStmt = $pdo->prepare('SELECT id FROM owners WHERE user_id = :user_id LIMIT 1');
    $ownerStmt->execute(['user_id' => $userId]);
    $owner = $ownerStmt->fetch();
    $ownerIdForUser = $owner ? (int) $owner['id'] : 0;
} else {
    $ownersStmt = $pdo->prepare(
        'SELECT owners.id, users.name
         FROM owners
         INNER JOIN users ON owners.user_id = users.id
         ORDER BY users.name ASC'
    );
    $ownersStmt->execute();
    $owners = $ownersStmt->fetchAll();
}

if ($isEdit) {
    if ($role === 'owner') {
        $loadStmt = $pdo->prepare('SELECT * FROM animals WHERE id = :id AND owner_id = :owner_id LIMIT 1');
        $loadStmt->execute([
            'id' => $id,
            'owner_id' => $ownerIdForUser,
        ]);
    } else {
        $loadStmt = $pdo->prepare('SELECT * FROM animals WHERE id = :id LIMIT 1');
        $loadStmt->execute(['id' => $id]);
    }

    $loadedAnimal = $loadStmt->fetch();

    if (!$loadedAnimal) {
        set_flash('danger', 'Animal record not found.');
        header('Location: ' . app_url('pages/animals.php'));
        exit;
    }

    $animal = $loadedAnimal;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $animal['name'] = trim($_POST['name'] ?? '');
    $animal['species'] = $_POST['species'] ?? '';
    $animal['breed'] = trim($_POST['breed'] ?? '');
    $animal['age'] = trim($_POST['age'] ?? '');
    $animal['weight'] = trim($_POST['weight'] ?? '');

    if ($role === 'owner') {
        $animal['owner_id'] = $ownerIdForUser;
    } else {
        $animal['owner_id'] = (int) ($_POST['owner_id'] ?? 0);
    }

    if ($animal['name'] === '') {
        $errors['name'] = 'Animal name is required.';
    }

    if (!in_array($animal['species'], $speciesOptions, true)) {
        $errors['species'] = 'Please select a valid species.';
    }

    if ($animal['breed'] === '') {
        $errors['breed'] = 'Breed is required.';
    }

    if ($animal['age'] === '' || !is_numeric($animal['age']) || (float) $animal['age'] < 0) {
        $errors['age'] = 'Please enter a valid age.';
    }

    if ($animal['weight'] === '' || !is_numeric($animal['weight']) || (float) $animal['weight'] <= 0) {
        $errors['weight'] = 'Please enter a valid weight.';
    }

    if ((int) $animal['owner_id'] <= 0) {
        $errors['owner_id'] = 'Please select a valid owner.';
    } else {
        $ownerCheckStmt = $pdo->prepare('SELECT id FROM owners WHERE id = :id LIMIT 1');
        $ownerCheckStmt->execute(['id' => (int) $animal['owner_id']]);

        if (!$ownerCheckStmt->fetch()) {
            $errors['owner_id'] = 'Selected owner does not exist.';
        }
    }

    if (empty($errors)) {
        if ($isEdit) {
            if ($role === 'owner') {
                $stmt = $pdo->prepare(
                    'UPDATE animals
                     SET name = :name, species = :species, breed = :breed, age = :age, weight = :weight, owner_id = :owner_id
                     WHERE id = :id AND owner_id = :current_owner_id'
                );
                $stmt->execute([
                    'name' => $animal['name'],
                    'species' => $animal['species'],
                    'breed' => $animal['breed'],
                    'age' => $animal['age'],
                    'weight' => $animal['weight'],
                    'owner_id' => (int) $animal['owner_id'],
                    'id' => $id,
                    'current_owner_id' => $ownerIdForUser,
                ]);
            } else {
                $stmt = $pdo->prepare(
                    'UPDATE animals
                     SET name = :name, species = :species, breed = :breed, age = :age, weight = :weight, owner_id = :owner_id
                     WHERE id = :id'
                );
                $stmt->execute([
                    'name' => $animal['name'],
                    'species' => $animal['species'],
                    'breed' => $animal['breed'],
                    'age' => $animal['age'],
                    'weight' => $animal['weight'],
                    'owner_id' => (int) $animal['owner_id'],
                    'id' => $id,
                ]);
            }

            set_flash('success', 'Animal record updated successfully.');
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO animals (owner_id, name, species, breed, age, weight)
                 VALUES (:owner_id, :name, :species, :breed, :age, :weight)'
            );
            $stmt->execute([
                'owner_id' => (int) $animal['owner_id'],
                'name' => $animal['name'],
                'species' => $animal['species'],
                'breed' => $animal['breed'],
                'age' => $animal['age'],
                'weight' => $animal['weight'],
            ]);

            set_flash('success', 'Animal record added successfully.');
        }

        header('Location: ' . app_url('pages/animals.php'));
        exit;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="entity-page">
    <section class="entity-hero">
        <p class="entity-kicker">
            <?php echo htmlspecialchars('Animal profile', ENT_QUOTES, 'UTF-8'); ?>
        </p>
        <h1 class="entity-title">
            <?php echo htmlspecialchars($isEdit ? 'Edit Animal' : 'Add New Animal', ENT_QUOTES, 'UTF-8'); ?>
        </h1>
        <p class="entity-subtitle">
            <?php echo htmlspecialchars('Keep species, breed, age, weight, and ownership details aligned with the clinic record.', ENT_QUOTES, 'UTF-8'); ?>
        </p>
    </section>

    <div class="form-shell">
        <section class="form-card card">
            <div class="card-body">
                <form method="post" action="<?php echo htmlspecialchars($isEdit ? app_url('pages/animals_form.php?id=' . $id) : app_url('pages/animals_form.php'), ENT_QUOTES, 'UTF-8'); ?>" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <?php if ($role === 'admin'): ?>
                        <div class="mb-3 form-floating-custom">
                            <select name="owner_id" id="owner_id" class="form-select <?php echo htmlspecialchars(isset($errors['owner_id']) ? 'is-invalid' : '', ENT_QUOTES, 'UTF-8'); ?>">
                                <option value="">
                                    <?php echo htmlspecialchars('Select owner', ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                                <?php foreach ($owners as $owner): ?>
                                    <option value="<?php echo htmlspecialchars((string) $owner['id'], ENT_QUOTES, 'UTF-8'); ?>"
                                        <?php echo htmlspecialchars((int) $animal['owner_id'] === (int) $owner['id'] ? 'selected' : '', ENT_QUOTES, 'UTF-8'); ?>>
                                        <?php echo htmlspecialchars($owner['name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label for="owner_id" class="form-label">
                                <?php echo htmlspecialchars('Owner', ENT_QUOTES, 'UTF-8'); ?>
                            </label>
                            <?php if (isset($errors['owner_id'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo htmlspecialchars($errors['owner_id'], ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3 form-floating-custom">
                        <input type="text" name="name" id="name"
                               class="form-control <?php echo htmlspecialchars(isset($errors['name']) ? 'is-invalid' : '', ENT_QUOTES, 'UTF-8'); ?>"
                               placeholder=" "
                               value="<?php echo htmlspecialchars($animal['name'], ENT_QUOTES, 'UTF-8'); ?>">
                        <label for="name" class="form-label">
                            <?php echo htmlspecialchars('Name', ENT_QUOTES, 'UTF-8'); ?>
                        </label>
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback">
                                <?php echo htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3 form-floating-custom">
                        <select name="species" id="species" class="form-select <?php echo htmlspecialchars(isset($errors['species']) ? 'is-invalid' : '', ENT_QUOTES, 'UTF-8'); ?>">
                            <?php foreach ($speciesOptions as $species): ?>
                                <option value="<?php echo htmlspecialchars($species, ENT_QUOTES, 'UTF-8'); ?>"
                                    <?php echo htmlspecialchars($animal['species'] === $species ? 'selected' : '', ENT_QUOTES, 'UTF-8'); ?>>
                                    <?php echo htmlspecialchars(ucfirst($species), ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label for="species" class="form-label">
                            <?php echo htmlspecialchars('Species', ENT_QUOTES, 'UTF-8'); ?>
                        </label>
                        <?php if (isset($errors['species'])): ?>
                            <div class="invalid-feedback">
                                <?php echo htmlspecialchars($errors['species'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3 form-floating-custom">
                        <input type="text" name="breed" id="breed"
                               class="form-control <?php echo htmlspecialchars(isset($errors['breed']) ? 'is-invalid' : '', ENT_QUOTES, 'UTF-8'); ?>"
                               placeholder=" "
                               value="<?php echo htmlspecialchars($animal['breed'], ENT_QUOTES, 'UTF-8'); ?>">
                        <label for="breed" class="form-label">
                            <?php echo htmlspecialchars('Breed', ENT_QUOTES, 'UTF-8'); ?>
                        </label>
                        <?php if (isset($errors['breed'])): ?>
                            <div class="invalid-feedback">
                                <?php echo htmlspecialchars($errors['breed'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3 form-floating-custom">
                                <input type="number" step="0.01" min="0" name="age" id="age"
                                       class="form-control <?php echo htmlspecialchars(isset($errors['age']) ? 'is-invalid' : '', ENT_QUOTES, 'UTF-8'); ?>"
                                       placeholder=" "
                                       value="<?php echo htmlspecialchars((string) $animal['age'], ENT_QUOTES, 'UTF-8'); ?>">
                                <label for="age" class="form-label">
                                    <?php echo htmlspecialchars('Age', ENT_QUOTES, 'UTF-8'); ?>
                                </label>
                                <?php if (isset($errors['age'])): ?>
                                    <div class="invalid-feedback">
                                        <?php echo htmlspecialchars($errors['age'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3 form-floating-custom">
                                <input type="number" step="0.01" min="0.01" name="weight" id="weight"
                                       class="form-control <?php echo htmlspecialchars(isset($errors['weight']) ? 'is-invalid' : '', ENT_QUOTES, 'UTF-8'); ?>"
                                       placeholder=" "
                                       value="<?php echo htmlspecialchars((string) $animal['weight'], ENT_QUOTES, 'UTF-8'); ?>">
                                <label for="weight" class="form-label">
                                    <?php echo htmlspecialchars('Weight', ENT_QUOTES, 'UTF-8'); ?>
                                </label>
                                <?php if (isset($errors['weight'])): ?>
                                    <div class="invalid-feedback">
                                        <?php echo htmlspecialchars($errors['weight'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?php echo htmlspecialchars($isEdit ? 'Update Animal' : 'Save Animal', ENT_QUOTES, 'UTF-8'); ?>
                        </button>
                        <a href="<?php echo htmlspecialchars(app_url('pages/animals.php'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary">
                            <?php echo htmlspecialchars('Cancel', ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
