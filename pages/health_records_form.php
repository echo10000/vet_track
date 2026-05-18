<?php
// pages/health_records_form.php

require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
require_role(['admin', 'staff']);

$userId = (int) ($_SESSION['user_id'] ?? 0);
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $id > 0;
$errors = [];

$record = [
    'animal_id' => '',
    'appointment_id' => '',
    'diagnosis' => '',
    'treatment' => '',
    'notes' => '',
];

$animalsStmt = $pdo->prepare(
    'SELECT animals.id, animals.name, users.name AS owner_name
     FROM animals
     INNER JOIN owners ON animals.owner_id = owners.id
     INNER JOIN users ON owners.user_id = users.id
     ORDER BY animals.name ASC'
);
$animalsStmt->execute();
$animals = $animalsStmt->fetchAll();

if ($isEdit) {
    $loadStmt = $pdo->prepare('SELECT * FROM health_records WHERE id = :id LIMIT 1');
    $loadStmt->execute(['id' => $id]);
    $loadedRecord = $loadStmt->fetch();

    if (!$loadedRecord) {
        set_flash('danger', 'Health record not found.');
        header('Location: ' . app_url('pages/health_records.php'));
        exit;
    }

    $record = $loadedRecord;
}

if (!$isEdit && isset($_GET['animal_id'])) {
    $record['animal_id'] = (int) $_GET['animal_id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $record['animal_id'] = (int) ($_POST['animal_id'] ?? 0);
    $appointmentInput = trim($_POST['appointment_id'] ?? '');
    $record['appointment_id'] = $appointmentInput === '' ? null : (int) $appointmentInput;
    $record['diagnosis'] = trim($_POST['diagnosis'] ?? '');
    $record['treatment'] = trim($_POST['treatment'] ?? '');
    $record['notes'] = trim($_POST['notes'] ?? '');

    if ((int) $record['animal_id'] <= 0) {
        $errors['animal_id'] = 'Please select an animal.';
    } else {
        $animalCheckStmt = $pdo->prepare('SELECT id FROM animals WHERE id = :id LIMIT 1');
        $animalCheckStmt->execute(['id' => (int) $record['animal_id']]);

        if (!$animalCheckStmt->fetch()) {
            $errors['animal_id'] = 'Selected animal does not exist.';
        }
    }

    if ($record['appointment_id'] !== null) {
        $appointmentCheckStmt = $pdo->prepare(
            'SELECT id FROM appointments WHERE id = :appointment_id AND animal_id = :animal_id LIMIT 1'
        );
        $appointmentCheckStmt->execute([
            'appointment_id' => (int) $record['appointment_id'],
            'animal_id' => (int) $record['animal_id'],
        ]);

        if (!$appointmentCheckStmt->fetch()) {
            $errors['appointment_id'] = 'Selected appointment is not linked to the selected animal.';
        }
    }

    if ($record['diagnosis'] === '') {
        $errors['diagnosis'] = 'Diagnosis is required.';
    }

    if ($record['treatment'] === '') {
        $errors['treatment'] = 'Treatment is required.';
    }

    if (empty($errors)) {
        if ($isEdit) {
            $stmt = $pdo->prepare(
                'UPDATE health_records
                 SET animal_id = :animal_id, appointment_id = :appointment_id, diagnosis = :diagnosis,
                     treatment = :treatment, notes = :notes, recorded_by = :recorded_by
                 WHERE id = :id'
            );
            $stmt->execute([
                'animal_id' => (int) $record['animal_id'],
                'appointment_id' => $record['appointment_id'],
                'diagnosis' => $record['diagnosis'],
                'treatment' => $record['treatment'],
                'notes' => $record['notes'],
                'recorded_by' => $userId,
                'id' => $id,
            ]);

            set_flash('success', 'Health record updated successfully.');
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO health_records (animal_id, appointment_id, diagnosis, treatment, notes, recorded_by)
                 VALUES (:animal_id, :appointment_id, :diagnosis, :treatment, :notes, :recorded_by)'
            );
            $stmt->execute([
                'animal_id' => (int) $record['animal_id'],
                'appointment_id' => $record['appointment_id'],
                'diagnosis' => $record['diagnosis'],
                'treatment' => $record['treatment'],
                'notes' => $record['notes'],
                'recorded_by' => $userId,
            ]);

            set_flash('success', 'Health record added successfully.');
        }

        header('Location: ' . app_url('pages/health_records.php'));
        exit;
    }
}

$selectedAnimalId = (int) ($record['animal_id'] ?? 0);
if ($selectedAnimalId > 0) {
    $appointmentsStmt = $pdo->prepare(
        'SELECT id, date, time, reason, status
         FROM appointments
         WHERE animal_id = :animal_id
         ORDER BY date DESC, time DESC'
    );
    $appointmentsStmt->execute(['animal_id' => $selectedAnimalId]);
    $appointments = $appointmentsStmt->fetchAll();
} else {
    $appointmentsStmt = $pdo->prepare(
        'SELECT appointments.id, appointments.date, appointments.time, appointments.reason, appointments.status, animals.name AS animal_name
         FROM appointments
         INNER JOIN animals ON appointments.animal_id = animals.id
         ORDER BY appointments.date DESC, appointments.time DESC'
    );
    $appointmentsStmt->execute();
    $appointments = $appointmentsStmt->fetchAll();
}

include __DIR__ . '/../includes/header.php';
?>

<div class="entity-page">
    <section class="entity-hero">
        <p class="entity-kicker">
            <?php echo htmlspecialchars('Clinical records', ENT_QUOTES, 'UTF-8'); ?>
        </p>
        <h1 class="entity-title">
            <?php echo htmlspecialchars($isEdit ? 'Edit Health Record' : 'Add Health Record', ENT_QUOTES, 'UTF-8'); ?>
        </h1>
        <p class="entity-subtitle">
            <?php echo htmlspecialchars('Capture diagnoses, treatments, notes, and optional appointment context in one clean record.', ENT_QUOTES, 'UTF-8'); ?>
        </p>
    </section>

    <div class="form-shell">
        <section class="form-card card">
            <div class="card-body">
                <form method="post" action="<?php echo htmlspecialchars($isEdit ? app_url('pages/health_records_form.php?id=' . $id) : app_url('pages/health_records_form.php'), ENT_QUOTES, 'UTF-8'); ?>" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="mb-3 form-floating-custom">
                        <select name="animal_id" id="animal_id" class="form-select <?php echo htmlspecialchars(isset($errors['animal_id']) ? 'is-invalid' : '', ENT_QUOTES, 'UTF-8'); ?>">
                            <option value="">
                                <?php echo htmlspecialchars('Select animal', ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                            <?php foreach ($animals as $animal): ?>
                                <option value="<?php echo htmlspecialchars((string) $animal['id'], ENT_QUOTES, 'UTF-8'); ?>"
                                    <?php echo htmlspecialchars((int) $record['animal_id'] === (int) $animal['id'] ? 'selected' : '', ENT_QUOTES, 'UTF-8'); ?>>
                                    <?php echo htmlspecialchars($animal['name'] . ' - ' . $animal['owner_name'], ENT_QUOTES, 'UTF-8'); ?>
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

                    <div class="mb-3 form-floating-custom">
                        <select name="appointment_id" id="appointment_id" class="form-select <?php echo htmlspecialchars(isset($errors['appointment_id']) ? 'is-invalid' : '', ENT_QUOTES, 'UTF-8'); ?>">
                            <option value="">
                                <?php echo htmlspecialchars('No linked appointment', ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                            <?php foreach ($appointments as $appointment): ?>
                                <?php
                                $labelParts = [];
                                if (isset($appointment['animal_name'])) {
                                    $labelParts[] = $appointment['animal_name'];
                                }
                                $labelParts[] = $appointment['date'];
                                $labelParts[] = $appointment['time'];
                                $labelParts[] = $appointment['status'];
                                $appointmentLabel = implode(' - ', $labelParts);
                                ?>
                                <option value="<?php echo htmlspecialchars((string) $appointment['id'], ENT_QUOTES, 'UTF-8'); ?>"
                                    <?php echo htmlspecialchars((int) ($record['appointment_id'] ?? 0) === (int) $appointment['id'] ? 'selected' : '', ENT_QUOTES, 'UTF-8'); ?>>
                                    <?php echo htmlspecialchars($appointmentLabel, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label for="appointment_id" class="form-label">
                            <?php echo htmlspecialchars('Linked Appointment', ENT_QUOTES, 'UTF-8'); ?>
                        </label>
                        <?php if (isset($errors['appointment_id'])): ?>
                            <div class="invalid-feedback">
                                <?php echo htmlspecialchars($errors['appointment_id'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3 form-floating-custom">
                        <textarea name="diagnosis" id="diagnosis" rows="3"
                                  placeholder=" "
                                  class="form-control <?php echo htmlspecialchars(isset($errors['diagnosis']) ? 'is-invalid' : '', ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($record['diagnosis'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                        <label for="diagnosis" class="form-label">
                            <?php echo htmlspecialchars('Diagnosis', ENT_QUOTES, 'UTF-8'); ?>
                        </label>
                        <?php if (isset($errors['diagnosis'])): ?>
                            <div class="invalid-feedback">
                                <?php echo htmlspecialchars($errors['diagnosis'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3 form-floating-custom">
                        <textarea name="treatment" id="treatment" rows="3"
                                  placeholder=" "
                                  class="form-control <?php echo htmlspecialchars(isset($errors['treatment']) ? 'is-invalid' : '', ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($record['treatment'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                        <label for="treatment" class="form-label">
                            <?php echo htmlspecialchars('Treatment', ENT_QUOTES, 'UTF-8'); ?>
                        </label>
                        <?php if (isset($errors['treatment'])): ?>
                            <div class="invalid-feedback">
                                <?php echo htmlspecialchars($errors['treatment'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3 form-floating-custom">
                        <textarea name="notes" id="notes" rows="3" class="form-control" placeholder=" "><?php echo htmlspecialchars($record['notes'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        <label for="notes" class="form-label">
                            <?php echo htmlspecialchars('Notes', ENT_QUOTES, 'UTF-8'); ?>
                        </label>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?php echo htmlspecialchars($isEdit ? 'Update Record' : 'Save Record', ENT_QUOTES, 'UTF-8'); ?>
                        </button>
                        <a href="<?php echo htmlspecialchars(app_url('pages/health_records.php'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary">
                            <?php echo htmlspecialchars('Cancel', ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
