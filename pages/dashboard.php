<?php
// pages/dashboard.php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$role = $_SESSION['role'] ?? '';
$userId = (int) ($_SESSION['user_id'] ?? 0);
$statusBadgeClasses = [
    'pending' => 'status-pending',
    'confirmed' => 'status-confirmed',
    'done' => 'status-done',
    'cancelled' => 'status-cancelled',
];

if (in_array($role, ['admin', 'staff'], true)) {
    $totalAnimalsStmt = $pdo->prepare('SELECT COUNT(*) AS total FROM animals');
    $totalAnimalsStmt->execute();
    $totalAnimals = (int) ($totalAnimalsStmt->fetch()['total'] ?? 0);

    $todayAppointmentsStmt = $pdo->prepare('SELECT COUNT(*) AS total FROM appointments WHERE date = CURDATE()');
    $todayAppointmentsStmt->execute();
    $todayAppointments = (int) ($todayAppointmentsStmt->fetch()['total'] ?? 0);

    $pendingAppointmentsStmt = $pdo->prepare('SELECT COUNT(*) AS total FROM appointments WHERE status = :status');
    $pendingAppointmentsStmt->execute(['status' => 'pending']);
    $pendingAppointments = (int) ($pendingAppointmentsStmt->fetch()['total'] ?? 0);

    $totalOwnersStmt = $pdo->prepare('SELECT COUNT(*) AS total FROM owners');
    $totalOwnersStmt->execute();
    $totalOwners = (int) ($totalOwnersStmt->fetch()['total'] ?? 0);

    $recentStmt = $pdo->prepare(
        'SELECT appointments.*, animals.name AS animal_name, users.name AS owner_name
         FROM appointments
         INNER JOIN animals ON appointments.animal_id = animals.id
         INNER JOIN owners ON animals.owner_id = owners.id
         INNER JOIN users ON owners.user_id = users.id
         ORDER BY appointments.created_at DESC
         LIMIT 10'
    );
    $recentStmt->execute();
    $recentAppointments = $recentStmt->fetchAll();

    $weekStmt = $pdo->prepare(
        'SELECT appointments.*, animals.name AS animal_name, users.name AS owner_name
         FROM appointments
         INNER JOIN animals ON appointments.animal_id = animals.id
         INNER JOIN owners ON animals.owner_id = owners.id
         INNER JOIN users ON owners.user_id = users.id
         WHERE appointments.date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
         ORDER BY appointments.date ASC, appointments.time ASC'
    );
    $weekStmt->execute();
    $upcomingWeek = $weekStmt->fetchAll();
} else {
    $ownerStmt = $pdo->prepare('SELECT id FROM owners WHERE user_id = :user_id LIMIT 1');
    $ownerStmt->execute(['user_id' => $userId]);
    $owner = $ownerStmt->fetch();
    $ownerId = $owner ? (int) $owner['id'] : 0;

    $animalsStmt = $pdo->prepare('SELECT * FROM animals WHERE owner_id = :owner_id ORDER BY name ASC');
    $animalsStmt->execute(['owner_id' => $ownerId]);
    $ownerAnimals = $animalsStmt->fetchAll();

    $upcomingStmt = $pdo->prepare(
        'SELECT appointments.*, animals.name AS animal_name
         FROM appointments
         INNER JOIN animals ON appointments.animal_id = animals.id
         WHERE animals.owner_id = :owner_id
           AND appointments.date >= CURDATE()
           AND appointments.status IN (:pending_status, :confirmed_status)
         ORDER BY appointments.date ASC, appointments.time ASC'
    );
    $upcomingStmt->execute([
        'owner_id' => $ownerId,
        'pending_status' => 'pending',
        'confirmed_status' => 'confirmed',
    ]);
    $ownerUpcomingAppointments = $upcomingStmt->fetchAll();

    $latestStmt = $pdo->prepare(
        'SELECT animals.id AS animal_id, animals.name AS animal_name,
                health_records.diagnosis, health_records.treatment, health_records.created_at
         FROM animals
         LEFT JOIN health_records ON health_records.id = (
             SELECT hr2.id
             FROM health_records AS hr2
             WHERE hr2.animal_id = animals.id
             ORDER BY hr2.created_at DESC
             LIMIT 1
         )
         WHERE animals.owner_id = :owner_id
         ORDER BY animals.name ASC'
    );
    $latestStmt->execute(['owner_id' => $ownerId]);
    $latestRecords = $latestStmt->fetchAll();
}

include __DIR__ . '/../includes/header.php';
?>

<div class="entity-page dashboard-section">
    <section class="entity-hero">
        <div>
            <div class="entity-kicker">
                <?php echo htmlspecialchars(in_array($role, ['admin', 'staff'], true) ? 'Clinic Overview' : 'Owner Portal', ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <h1 class="entity-title">
                <?php echo htmlspecialchars('Dashboard', ENT_QUOTES, 'UTF-8'); ?>
            </h1>
            <p class="entity-subtitle">
                <?php echo htmlspecialchars(in_array($role, ['admin', 'staff'], true) ? 'Monitor patients, owners, appointments, and the week ahead from one operational view.' : 'Keep your animals, appointments, and latest health updates easy to review.', ENT_QUOTES, 'UTF-8'); ?>
            </p>
        </div>
        <div class="entity-actions">
            <?php if (in_array($role, ['admin', 'staff'], true)): ?>
                <a href="<?php echo htmlspecialchars(app_url('pages/appointments.php'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary">
                    <?php echo vet_icon('calendar'); ?>
                    <?php echo htmlspecialchars('Manage Appointments', ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php else: ?>
                <a href="<?php echo htmlspecialchars(app_url('pages/appointments_form.php'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary">
                    <?php echo vet_icon('calendar'); ?>
                    <?php echo htmlspecialchars('Book Appointment', ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php endif; ?>
        </div>
    </section>

    <?php if (in_array($role, ['admin', 'staff'], true)): ?>
        <div class="row g-3">
            <div class="col-md-3">
                <div class="card border-0 stat-card stat-card-blue">
                    <div class="stat-icon"><?php echo vet_icon('animal'); ?></div>
                    <div class="card-body">
                        <p class="stat-label"><?php echo htmlspecialchars('Total Animals', ENT_QUOTES, 'UTF-8'); ?></p>
                        <h2 class="stat-value"><?php echo htmlspecialchars((string) $totalAnimals, ENT_QUOTES, 'UTF-8'); ?></h2>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 stat-card stat-card-green">
                    <div class="stat-icon"><?php echo vet_icon('calendar'); ?></div>
                    <div class="card-body">
                        <p class="stat-label"><?php echo htmlspecialchars("Today's Appointments", ENT_QUOTES, 'UTF-8'); ?></p>
                        <h2 class="stat-value"><?php echo htmlspecialchars((string) $todayAppointments, ENT_QUOTES, 'UTF-8'); ?></h2>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 stat-card stat-card-amber">
                    <div class="stat-icon"><?php echo vet_icon('clipboard'); ?></div>
                    <div class="card-body">
                        <p class="stat-label"><?php echo htmlspecialchars('Pending Appointments', ENT_QUOTES, 'UTF-8'); ?></p>
                        <h2 class="stat-value"><?php echo htmlspecialchars((string) $pendingAppointments, ENT_QUOTES, 'UTF-8'); ?></h2>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 stat-card stat-card-purple">
                    <div class="stat-icon"><?php echo vet_icon('users'); ?></div>
                    <div class="card-body">
                        <p class="stat-label"><?php echo htmlspecialchars('Total Owners', ENT_QUOTES, 'UTF-8'); ?></p>
                        <h2 class="stat-value"><?php echo htmlspecialchars((string) $totalOwners, ENT_QUOTES, 'UTF-8'); ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <section class="entity-table-card card">
            <div class="card-body">
                <h2 class="section-card-title">
                    <?php echo vet_icon('calendar'); ?>
                    <?php echo htmlspecialchars('Recent Appointments', ENT_QUOTES, 'UTF-8'); ?>
                </h2>

                <?php if (empty($recentAppointments)): ?>
                    <p class="empty-state text-muted mb-0"><?php echo htmlspecialchars('No recent appointments.', ENT_QUOTES, 'UTF-8'); ?></p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                            <tr>
                                <th><?php echo htmlspecialchars('Animal', ENT_QUOTES, 'UTF-8'); ?></th>
                                <th><?php echo htmlspecialchars('Owner', ENT_QUOTES, 'UTF-8'); ?></th>
                                <th><?php echo htmlspecialchars('Date', ENT_QUOTES, 'UTF-8'); ?></th>
                                <th><?php echo htmlspecialchars('Status', ENT_QUOTES, 'UTF-8'); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($recentAppointments as $appointment): ?>
                                <?php $badgeClass = $statusBadgeClasses[$appointment['status']] ?? 'secondary'; ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($appointment['animal_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['owner_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars(ucfirst($appointment['status']), ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="entity-table-card card">
            <div class="card-body">
                <h2 class="section-card-title">
                    <?php echo vet_icon('clipboard'); ?>
                    <?php echo htmlspecialchars('Upcoming This Week', ENT_QUOTES, 'UTF-8'); ?>
                </h2>

                <?php if (empty($upcomingWeek)): ?>
                    <p class="empty-state text-muted mb-0"><?php echo htmlspecialchars('No upcoming appointments this week.', ENT_QUOTES, 'UTF-8'); ?></p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                            <tr>
                                <th><?php echo htmlspecialchars('Animal', ENT_QUOTES, 'UTF-8'); ?></th>
                                <th><?php echo htmlspecialchars('Owner', ENT_QUOTES, 'UTF-8'); ?></th>
                                <th><?php echo htmlspecialchars('Date', ENT_QUOTES, 'UTF-8'); ?></th>
                                <th><?php echo htmlspecialchars('Time', ENT_QUOTES, 'UTF-8'); ?></th>
                                <th><?php echo htmlspecialchars('Status', ENT_QUOTES, 'UTF-8'); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($upcomingWeek as $appointment): ?>
                                <?php $badgeClass = $statusBadgeClasses[$appointment['status']] ?? 'secondary'; ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($appointment['animal_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['owner_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['time'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars(ucfirst($appointment['status']), ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php else: ?>
        <section class="entity-table-card card">
            <div class="card-body">
                <h2 class="section-card-title">
                    <?php echo vet_icon('animal'); ?>
                    <?php echo htmlspecialchars('My Animals', ENT_QUOTES, 'UTF-8'); ?>
                </h2>

                <?php if (empty($ownerAnimals)): ?>
                    <p class="empty-state text-muted mb-0"><?php echo htmlspecialchars('No animals yet.', ENT_QUOTES, 'UTF-8'); ?></p>
                <?php else: ?>
                    <div class="owner-card-grid">
                        <?php foreach ($ownerAnimals as $animal): ?>
                            <article class="owner-mini-card">
                                <h3><?php echo htmlspecialchars($animal['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p><?php echo htmlspecialchars(ucfirst($animal['species']) . ' - ' . $animal['breed'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <a href="<?php echo htmlspecialchars(app_url('pages/appointments_form.php?animal_id=' . $animal['id']), ENT_QUOTES, 'UTF-8'); ?>"
                                   class="btn btn-sm btn-primary">
                                    <?php echo vet_icon('calendar'); ?>
                                    <?php echo htmlspecialchars('Book Appointment', ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="entity-table-card card">
            <div class="card-body">
                <h2 class="section-card-title">
                    <?php echo vet_icon('calendar'); ?>
                    <?php echo htmlspecialchars('My Upcoming Appointments', ENT_QUOTES, 'UTF-8'); ?>
                </h2>

                <?php if (empty($ownerUpcomingAppointments)): ?>
                    <p class="empty-state text-muted mb-0"><?php echo htmlspecialchars('No upcoming appointments.', ENT_QUOTES, 'UTF-8'); ?></p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                            <tr>
                                <th><?php echo htmlspecialchars('Animal', ENT_QUOTES, 'UTF-8'); ?></th>
                                <th><?php echo htmlspecialchars('Date', ENT_QUOTES, 'UTF-8'); ?></th>
                                <th><?php echo htmlspecialchars('Time', ENT_QUOTES, 'UTF-8'); ?></th>
                                <th><?php echo htmlspecialchars('Reason', ENT_QUOTES, 'UTF-8'); ?></th>
                                <th><?php echo htmlspecialchars('Status', ENT_QUOTES, 'UTF-8'); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($ownerUpcomingAppointments as $appointment): ?>
                                <?php $badgeClass = $statusBadgeClasses[$appointment['status']] ?? 'secondary'; ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($appointment['animal_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['time'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['reason'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars(ucfirst($appointment['status']), ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="entity-table-card card">
            <div class="card-body">
                <h2 class="section-card-title">
                    <?php echo vet_icon('clipboard'); ?>
                    <?php echo htmlspecialchars('Latest Health Record Per Animal', ENT_QUOTES, 'UTF-8'); ?>
                </h2>

                <?php if (empty($latestRecords)): ?>
                    <p class="empty-state text-muted mb-0"><?php echo htmlspecialchars('No animals available.', ENT_QUOTES, 'UTF-8'); ?></p>
                <?php else: ?>
                    <div class="owner-card-grid">
                        <?php foreach ($latestRecords as $record): ?>
                            <article class="owner-mini-card">
                                <h3><?php echo htmlspecialchars($record['animal_name'], ENT_QUOTES, 'UTF-8'); ?></h3>

                                <?php if ($record['diagnosis']): ?>
                                    <div class="record-body">
                                        <p>
                                            <strong><?php echo htmlspecialchars('Diagnosis:', ENT_QUOTES, 'UTF-8'); ?></strong>
                                            <?php echo htmlspecialchars($record['diagnosis'], ENT_QUOTES, 'UTF-8'); ?>
                                        </p>
                                        <p>
                                            <strong><?php echo htmlspecialchars('Treatment:', ENT_QUOTES, 'UTF-8'); ?></strong>
                                            <?php echo htmlspecialchars($record['treatment'], ENT_QUOTES, 'UTF-8'); ?>
                                        </p>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($record['created_at'], ENT_QUOTES, 'UTF-8'); ?>
                                        </small>
                                    </div>
                                <?php else: ?>
                                    <p><?php echo htmlspecialchars('No records yet', ENT_QUOTES, 'UTF-8'); ?></p>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
