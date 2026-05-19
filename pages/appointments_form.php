<?php
// pages/appointments_form.php

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
$statusOptions = ['pending', 'confirmed', 'done', 'cancelled'];

if ($isEdit && !in_array($role, ['admin', 'staff'], true)) {
    set_flash('danger', 'You are not authorized to edit appointments.');
    header('Location: ' . app_url('pages/appointments.php'));
    exit;
}

$appointment = [
    'animal_id' => '',
    'staff_id' => '',
    'date' => '',
    'time' => '',
    'reason' => '',
    'status' => 'pending',
];

if ($role === 'owner') {
    $ownerStmt = $pdo->prepare('SELECT id FROM owners WHERE user_id = :user_id LIMIT 1');
    $ownerStmt->execute(['user_id' => $userId]);
    $owner = $ownerStmt->fetch();
    $ownerId = $owner ? (int) $owner['id'] : 0;

    $animalsStmt = $pdo->prepare('SELECT id, name FROM animals WHERE owner_id = :owner_id ORDER BY name ASC');
    $animalsStmt->execute(['owner_id' => $ownerId]);
    $animals = $animalsStmt->fetchAll();
} else {
    $animalsStmt = $pdo->prepare(
        'SELECT animals.id, animals.name, users.name AS owner_name
         FROM animals
         INNER JOIN owners ON animals.owner_id = owners.id
         INNER JOIN users ON owners.user_id = users.id
         ORDER BY animals.name ASC'
    );
    $animalsStmt->execute();
    $animals = $animalsStmt->fetchAll();
}

$staffStmt = $pdo->prepare(
    'SELECT id, name FROM users WHERE role = :staff_role ORDER BY name ASC'
);
$staffStmt->execute([
    'staff_role' => 'staff',
]);
$staffUsers = $staffStmt->fetchAll();

if (!$isEdit && isset($_GET['animal_id'])) {
    $appointment['animal_id'] = (int) $_GET['animal_id'];
}

if ($isEdit) {
    $loadStmt = $pdo->prepare('SELECT * FROM appointments WHERE id = :id LIMIT 1');
    $loadStmt->execute(['id' => $id]);
    $loadedAppointment = $loadStmt->fetch();

    if (!$loadedAppointment) {
        set_flash('danger', 'Appointment not found.');
        header('Location: ' . app_url('pages/appointments.php'));
        exit;
    }

    $appointment = $loadedAppointment;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $appointment['animal_id'] = (int) ($_POST['animal_id'] ?? 0);
    $appointment['date'] = trim($_POST['date'] ?? '');
    $appointment['time'] = trim($_POST['time'] ?? '');
    $appointment['reason'] = trim($_POST['reason'] ?? '');

    if (in_array($role, ['admin', 'staff'], true)) {
        $appointment['status'] = $_POST['status'] ?? 'pending';
    } else {
        $appointment['status'] = 'pending';
    }

    if ($role === 'admin') {
        $staffInput = trim($_POST['staff_id'] ?? '');
        $appointment['staff_id'] = $staffInput === '' ? null : (int) $staffInput;
    } elseif ($role === 'staff' && !$isEdit) {
        $appointment['staff_id'] = $userId;
    } elseif ($role === 'owner') {
        $appointment['staff_id'] = null;
    } else {
        $appointment['staff_id'] = empty($appointment['staff_id']) ? null : (int) $appointment['staff_id'];
    }

    if ((int) $appointment['animal_id'] <= 0) {
        $errors['animal_id'] = 'Please select an animal.';
    } else {
        if ($role === 'owner') {
            $animalCheckStmt = $pdo->prepare('SELECT id FROM animals WHERE id = :id AND owner_id = :owner_id LIMIT 1');
            $animalCheckStmt->execute([
                'id' => (int) $appointment['animal_id'],
                'owner_id' => $ownerId,
            ]);
        } else {
            $animalCheckStmt = $pdo->prepare('SELECT id FROM animals WHERE id = :id LIMIT 1');
            $animalCheckStmt->execute(['id' => (int) $appointment['animal_id']]);
        }

        if (!$animalCheckStmt->fetch()) {
            $errors['animal_id'] = 'Selected animal does not exist.';
        }
    }

    if ($appointment['date'] === '') {
        $errors['date'] = 'Date is required.';
    }

    if ($appointment['time'] === '') {
        $errors['time'] = 'Time is required.';
    }

    if ($appointment['reason'] === '') {
        $errors['reason'] = 'Reason is required.';
    }

    if (!in_array($appointment['status'], $statusOptions, true)) {
        $errors['status'] = 'Please select a valid status.';
    }

    if ($appointment['staff_id'] !== null) {
        $staffCheckStmt = $pdo->prepare('SELECT id FROM users WHERE id = :id AND role = :staff_role LIMIT 1');
        $staffCheckStmt->execute([
            'id' => (int) $appointment['staff_id'],
            'staff_role' => 'staff',
        ]);

        if (!$staffCheckStmt->fetch()) {
            $errors['staff_id'] = 'Please select a valid staff member.';
        }
    }

    if (empty($errors)) {
        if ($isEdit) {
            $stmt = $pdo->prepare(
                'UPDATE appointments
                 SET animal_id = :animal_id, staff_id = :staff_id, date = :date, time = :time, reason = :reason, status = :status
                 WHERE id = :id'
            );
            $stmt->execute([
                'animal_id' => (int) $appointment['animal_id'],
                'staff_id' => $appointment['staff_id'],
                'date' => $appointment['date'],
                'time' => $appointment['time'],
                'reason' => $appointment['reason'],
                'status' => $appointment['status'],
                'id' => $id,
            ]);

            set_flash('success', 'Appointment updated successfully.');
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO appointments (animal_id, staff_id, date, time, reason, status)
                 VALUES (:animal_id, :staff_id, :date, :time, :reason, :status)'
            );
            $stmt->execute([
                'animal_id' => (int) $appointment['animal_id'],
                'staff_id' => $appointment['staff_id'],
                'date' => $appointment['date'],
                'time' => $appointment['time'],
                'reason' => $appointment['reason'],
                'status' => $appointment['status'],
            ]);

            set_flash('success', 'Appointment booked successfully.');
        }

        header('Location: ' . app_url('pages/appointments.php'));
        exit;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="entity-page">
    <section class="entity-hero">
        <p class="entity-kicker">
            <?php echo htmlspecialchars('Appointments', ENT_QUOTES, 'UTF-8'); ?>
        </p>
        <h1 class="entity-title">
            <?php echo htmlspecialchars($isEdit ? 'Edit Appointment' : 'Book New Appointment', ENT_QUOTES, 'UTF-8'); ?>
        </h1>
        <p class="entity-subtitle">
            <?php echo htmlspecialchars('Schedule visits with the right animal, care team member, time, and appointment status.', ENT_QUOTES, 'UTF-8'); ?>
        </p>
    </section>

    <div class="form-shell">
        <section class="form-card card">
            <div class="card-body">
                <form method="post" action="<?php echo htmlspecialchars($isEdit ? app_url('pages/appointments_form.php?id=' . $id) : app_url('pages/appointments_form.php'), ENT_QUOTES, 'UTF-8'); ?>" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="mb-3 form-floating-custom">
                        <select name="animal_id" id="animal_id" class="form-select <?php echo htmlspecialchars(isset($errors['animal_id']) ? 'is-invalid' : '', ENT_QUOTES, 'UTF-8'); ?>">
                            <option value="">
                                <?php echo htmlspecialchars('Select animal', ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                            <?php foreach ($animals as $animal): ?>
                                <?php
                                $label = $role === 'owner'
                                    ? $animal['name']
                                    : $animal['name'] . ' - ' . $animal['owner_name'];
                                ?>
                                <option value="<?php echo htmlspecialchars((string) $animal['id'], ENT_QUOTES, 'UTF-8'); ?>"
                                    <?php echo htmlspecialchars((int) $appointment['animal_id'] === (int) $animal['id'] ? 'selected' : '', ENT_QUOTES, 'UTF-8'); ?>>
                                    <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label for="animal_id" class="form-label">
                            <?php echo htmlspecialchars('Animal', ENT_QUOTES, 'UTF-8'); ?>
                        </label>
                        <?php if (isset($errors['animal_id'])): ?>
                            <div class="invalid-feedback">
                                <?php echo htmlspecialchars($errors['animal_id'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3 form-floating-custom">
                                <input type="date" name="date" id="date"
                                       class="form-control <?php echo htmlspecialchars(isset($errors['date']) ? 'is-invalid' : '', ENT_QUOTES, 'UTF-8'); ?>"
                                       placeholder=" "
                                       value="<?php echo htmlspecialchars($appointment['date'], ENT_QUOTES, 'UTF-8'); ?>">
                                <label for="date" class="form-label">
                                    <?php echo htmlspecialchars('Date', ENT_QUOTES, 'UTF-8'); ?>
                                </label>
                                <?php if (isset($errors['date'])): ?>
                                    <div class="invalid-feedback">
                                        <?php echo htmlspecialchars($errors['date'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3 form-floating-custom">
                                <input type="time" name="time" id="time"
                                       class="form-control <?php echo htmlspecialchars(isset($errors['time']) ? 'is-invalid' : '', ENT_QUOTES, 'UTF-8'); ?>"
                                       placeholder=" "
                                       value="<?php echo htmlspecialchars($appointment['time'], ENT_QUOTES, 'UTF-8'); ?>">
                                <label for="time" class="form-label">
                                    <?php echo htmlspecialchars('Time', ENT_QUOTES, 'UTF-8'); ?>
                                </label>
                                <?php if (isset($errors['time'])): ?>
                                    <div class="invalid-feedback">
                                        <?php echo htmlspecialchars($errors['time'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 form-floating-custom">
                        <textarea name="reason" id="reason" rows="4"
                                  placeholder=" "
                                  class="form-control <?php echo htmlspecialchars(isset($errors['reason']) ? 'is-invalid' : '', ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($appointment['reason'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                        <label for="reason" class="form-label">
                            <?php echo htmlspecialchars('Reason', ENT_QUOTES, 'UTF-8'); ?>
                        </label>
                        <?php if (isset($errors['reason'])): ?>
                            <div class="invalid-feedback">
                                <?php echo htmlspecialchars($errors['reason'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($role === 'admin'): ?>
                        <div class="mb-3 form-floating-custom">
                            <select name="staff_id" id="staff_id" class="form-select <?php echo htmlspecialchars(isset($errors['staff_id']) ? 'is-invalid' : '', ENT_QUOTES, 'UTF-8'); ?>">
                                <option value="">
                                    <?php echo htmlspecialchars('Unassigned', ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                                <?php foreach ($staffUsers as $staff): ?>
                                    <option value="<?php echo htmlspecialchars((string) $staff['id'], ENT_QUOTES, 'UTF-8'); ?>"
                                        <?php echo htmlspecialchars((int) ($appointment['staff_id'] ?? 0) === (int) $staff['id'] ? 'selected' : '', ENT_QUOTES, 'UTF-8'); ?>>
                                        <?php echo htmlspecialchars($staff['name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label for="staff_id" class="form-label">
                                <?php echo htmlspecialchars('Assign Staff', ENT_QUOTES, 'UTF-8'); ?>
                            </label>
                            <?php if (isset($errors['staff_id'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo htmlspecialchars($errors['staff_id'], ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (in_array($role, ['admin', 'staff'], true)): ?>
                        <div class="mb-3 form-floating-custom">
                            <select name="status" id="status" class="form-select <?php echo htmlspecialchars(isset($errors['status']) ? 'is-invalid' : '', ENT_QUOTES, 'UTF-8'); ?>">
                                <?php foreach ($statusOptions as $statusOption): ?>
                                    <option value="<?php echo htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8'); ?>"
                                        <?php echo htmlspecialchars($appointment['status'] === $statusOption ? 'selected' : '', ENT_QUOTES, 'UTF-8'); ?>>
                                        <?php echo htmlspecialchars(ucfirst($statusOption), ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label for="status" class="form-label">
                                <?php echo htmlspecialchars('Status', ENT_QUOTES, 'UTF-8'); ?>
                            </label>
                            <?php if (isset($errors['status'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo htmlspecialchars($errors['status'], ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?php echo htmlspecialchars($isEdit ? 'Update Appointment' : 'Book Appointment', ENT_QUOTES, 'UTF-8'); ?>
                        </button>
                        <a href="<?php echo htmlspecialchars(app_url('pages/appointments.php'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary">
                            <?php echo htmlspecialchars('Cancel', ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
