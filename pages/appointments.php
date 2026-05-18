<?php
// pages/appointments.php

require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$role = $_SESSION['role'] ?? '';
$userId = (int) ($_SESSION['user_id'] ?? 0);
$status = $_GET['status'] ?? '';
$date = $_GET['date'] ?? '';
$params = [];

$statusOptions = ['pending', 'confirmed', 'done', 'cancelled'];
$statusBadgeClasses = [
    'pending' => 'status-pending',
    'confirmed' => 'status-confirmed',
    'done' => 'status-done',
    'cancelled' => 'status-cancelled',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel' && $role === 'owner') {
    verify_csrf_token();
    $appointmentId = (int) ($_POST['appointment_id'] ?? 0);

    $ownerStmt = $pdo->prepare('SELECT id FROM owners WHERE user_id = :user_id LIMIT 1');
    $ownerStmt->execute(['user_id' => $userId]);
    $owner = $ownerStmt->fetch();
    $ownerId = $owner ? (int) $owner['id'] : 0;

    $cancelStmt = $pdo->prepare(
        'UPDATE appointments
         INNER JOIN animals ON appointments.animal_id = animals.id
         SET appointments.status = :cancelled
         WHERE appointments.id = :appointment_id
           AND animals.owner_id = :owner_id
           AND appointments.status = :pending'
    );
    $cancelStmt->execute([
        'cancelled' => 'cancelled',
        'appointment_id' => $appointmentId,
        'owner_id' => $ownerId,
        'pending' => 'pending',
    ]);

    if ($cancelStmt->rowCount() > 0) {
        set_flash('success', 'Appointment cancelled successfully.');
    } else {
        set_flash('warning', 'Appointment could not be cancelled.');
    }

    header('Location: ' . app_url('pages/appointments.php'));
    exit;
}

if ($role === 'owner') {
    $ownerStmt = $pdo->prepare('SELECT id FROM owners WHERE user_id = :user_id LIMIT 1');
    $ownerStmt->execute(['user_id' => $userId]);
    $owner = $ownerStmt->fetch();
    $ownerId = $owner ? (int) $owner['id'] : 0;

    $sql = 'SELECT appointments.*, animals.name AS animal_name, users.name AS owner_name, staff.name AS staff_name
            FROM appointments
            INNER JOIN animals ON appointments.animal_id = animals.id
            INNER JOIN owners ON animals.owner_id = owners.id
            INNER JOIN users ON owners.user_id = users.id
            LEFT JOIN users AS staff ON appointments.staff_id = staff.id
            WHERE animals.owner_id = :owner_id';
    $params['owner_id'] = $ownerId;
} else {
    $sql = 'SELECT appointments.*, animals.name AS animal_name, users.name AS owner_name, staff.name AS staff_name
            FROM appointments
            INNER JOIN animals ON appointments.animal_id = animals.id
            INNER JOIN owners ON animals.owner_id = owners.id
            INNER JOIN users ON owners.user_id = users.id
            LEFT JOIN users AS staff ON appointments.staff_id = staff.id
            WHERE 1 = 1';
}

if ($status !== '' && in_array($status, $statusOptions, true)) {
    $sql .= ' AND appointments.status = :status';
    $params['status'] = $status;
}

if ($date !== '') {
    $sql .= ' AND appointments.date = :date';
    $params['date'] = $date;
}

$sql .= ' ORDER BY appointments.date DESC, appointments.time DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="entity-page">
    <section class="entity-hero">
        <div>
            <div class="entity-kicker">
                <?php echo htmlspecialchars('Schedule', ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <h1 class="entity-title">
                <?php echo htmlspecialchars($role === 'owner' ? 'My Appointments' : 'Appointments', ENT_QUOTES, 'UTF-8'); ?>
            </h1>
            <p class="entity-subtitle">
                <?php echo htmlspecialchars('Track visits by date, care team assignment, and appointment status.', ENT_QUOTES, 'UTF-8'); ?>
            </p>
        </div>
        <div class="entity-actions">
            <a href="<?php echo htmlspecialchars(app_url('pages/appointments_form.php'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary">
                <?php echo vet_icon('calendar'); ?>
                <?php echo htmlspecialchars('Book New Appointment', ENT_QUOTES, 'UTF-8'); ?>
            </a>
        </div>
    </section>

<div class="filter-card card shadow-sm mb-4">
    <div class="card-body">
        <form method="get" action="<?php echo htmlspecialchars(app_url('pages/appointments.php'), ENT_QUOTES, 'UTF-8'); ?>" class="row g-2">
            <div class="col-md-5">
                <select name="status" class="form-select">
                    <option value="">
                        <?php echo htmlspecialchars('All statuses', ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                    <?php foreach ($statusOptions as $statusOption): ?>
                        <option value="<?php echo htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8'); ?>"
                            <?php echo htmlspecialchars($status === $statusOption ? 'selected' : '', ENT_QUOTES, 'UTF-8'); ?>>
                            <?php echo htmlspecialchars(ucfirst($statusOption), ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <input type="date" name="date" class="form-control"
                       value="<?php echo htmlspecialchars($date, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-outline-primary">
                    <?php echo htmlspecialchars('Filter', ENT_QUOTES, 'UTF-8'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<div class="entity-table-card card shadow-sm">
    <div class="card-body">
        <?php if (empty($appointments)): ?>
            <p class="empty-state text-muted mb-0">
                <?php echo htmlspecialchars('No appointments found.', ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                    <tr>
                        <th><?php echo htmlspecialchars('Animal', ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars('Owner', ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars('Date', ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars('Time', ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars('Reason', ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars('Staff', ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars('Status', ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars('Actions', ENT_QUOTES, 'UTF-8'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($appointments as $appointment): ?>
                        <?php $badgeClass = $statusBadgeClasses[$appointment['status']] ?? 'secondary'; ?>
                        <tr>
                            <td><?php echo htmlspecialchars($appointment['animal_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($appointment['owner_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($appointment['date'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($appointment['time'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($appointment['reason'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($appointment['staff_name'] ?? 'Unassigned', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <span class="status-badge <?php echo htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars(ucfirst($appointment['status']), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <?php if (in_array($role, ['admin', 'staff'], true)): ?>
                                        <a href="<?php echo htmlspecialchars(app_url('pages/appointments_form.php?id=' . $appointment['id']), ENT_QUOTES, 'UTF-8'); ?>"
                                           class="btn-icon"
                                           title="<?php echo htmlspecialchars('Edit appointment', ENT_QUOTES, 'UTF-8'); ?>"
                                           aria-label="<?php echo htmlspecialchars('Edit appointment', ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo vet_icon('edit'); ?>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($role === 'admin'): ?>
                                        <form method="post"
                                              action="<?php echo htmlspecialchars(app_url('pages/appointments_delete.php'), ENT_QUOTES, 'UTF-8'); ?>"
                                              class="d-inline js-confirm-form"
                                              data-confirm-title="<?php echo htmlspecialchars('Delete appointment?', ENT_QUOTES, 'UTF-8'); ?>"
                                              data-confirm-message="<?php echo htmlspecialchars('This appointment will be permanently removed from the schedule.', ENT_QUOTES, 'UTF-8'); ?>"
                                              data-confirm-button="<?php echo htmlspecialchars('Delete Appointment', ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) $appointment['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <button type="submit"
                                                    class="btn-icon btn-icon-danger"
                                                    title="<?php echo htmlspecialchars('Delete appointment', ENT_QUOTES, 'UTF-8'); ?>"
                                                    aria-label="<?php echo htmlspecialchars('Delete appointment', ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo vet_icon('trash'); ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($role === 'owner' && $appointment['status'] === 'pending'): ?>
                                        <form method="post"
                                              action="<?php echo htmlspecialchars(app_url('pages/appointments.php'), ENT_QUOTES, 'UTF-8'); ?>"
                                              class="d-inline js-confirm-form"
                                              data-confirm-title="<?php echo htmlspecialchars('Cancel appointment?', ENT_QUOTES, 'UTF-8'); ?>"
                                              data-confirm-message="<?php echo htmlspecialchars('This will mark the pending appointment as cancelled.', ENT_QUOTES, 'UTF-8'); ?>"
                                              data-confirm-button="<?php echo htmlspecialchars('Cancel Appointment', ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="action" value="cancel">
                                            <input type="hidden" name="appointment_id"
                                                   value="<?php echo htmlspecialchars((string) $appointment['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <button type="submit"
                                                    class="btn-icon btn-icon-danger"
                                                    title="<?php echo htmlspecialchars('Cancel appointment', ENT_QUOTES, 'UTF-8'); ?>"
                                                    aria-label="<?php echo htmlspecialchars('Cancel appointment', ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo vet_icon('trash'); ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
