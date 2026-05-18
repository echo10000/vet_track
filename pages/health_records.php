<?php
// pages/health_records.php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$role = $_SESSION['role'] ?? '';
$userId = (int) ($_SESSION['user_id'] ?? 0);
$animalId = isset($_GET['animal_id']) ? (int) $_GET['animal_id'] : 0;
$params = [];

if ($role === 'owner') {
    $ownerStmt = $pdo->prepare('SELECT id FROM owners WHERE user_id = :user_id LIMIT 1');
    $ownerStmt->execute(['user_id' => $userId]);
    $owner = $ownerStmt->fetch();
    $ownerId = $owner ? (int) $owner['id'] : 0;

    $sql = 'SELECT health_records.*, animals.name AS animal_name, recorded_user.name AS recorded_by_name
            FROM health_records
            INNER JOIN animals ON health_records.animal_id = animals.id
            INNER JOIN users AS recorded_user ON health_records.recorded_by = recorded_user.id
            WHERE animals.owner_id = :owner_id';
    $params['owner_id'] = $ownerId;
} else {
    $sql = 'SELECT health_records.*, animals.name AS animal_name, recorded_user.name AS recorded_by_name
            FROM health_records
            INNER JOIN animals ON health_records.animal_id = animals.id
            INNER JOIN users AS recorded_user ON health_records.recorded_by = recorded_user.id
            WHERE 1 = 1';
}

if ($animalId > 0) {
    $sql .= ' AND health_records.animal_id = :animal_id';
    $params['animal_id'] = $animalId;
}

$sql .= ' ORDER BY health_records.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="entity-page">
    <section class="entity-hero">
        <div>
            <div class="entity-kicker">
                <?php echo htmlspecialchars('Medical History', ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <h1 class="entity-title">
                <?php echo htmlspecialchars($role === 'owner' ? 'My Health Records' : 'Health Records', ENT_QUOTES, 'UTF-8'); ?>
            </h1>
            <p class="entity-subtitle">
                <?php echo htmlspecialchars('Browse diagnoses, treatments, clinical notes, and record authorship.', ENT_QUOTES, 'UTF-8'); ?>
            </p>
        </div>

        <?php if (in_array($role, ['admin', 'staff'], true)): ?>
            <div class="entity-actions">
                <a href="<?php echo htmlspecialchars(app_url('pages/health_records_form.php' . ($animalId > 0 ? '?animal_id=' . $animalId : '')), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary">
                    <?php echo vet_icon('clipboard'); ?>
                    <?php echo htmlspecialchars('Add Record', ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </div>
        <?php endif; ?>
    </section>

<div class="entity-table-card card shadow-sm">
    <div class="card-body">
        <?php if (empty($records)): ?>
            <p class="empty-state text-muted mb-0">
                <?php echo htmlspecialchars('No health records found.', ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                    <tr>
                        <th><?php echo htmlspecialchars('Animal', ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars('Diagnosis', ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars('Treatment', ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars('Notes', ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars('Recorded By', ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars('Date Recorded', ENT_QUOTES, 'UTF-8'); ?></th>
                        <?php if (in_array($role, ['admin', 'staff'], true)): ?>
                            <th><?php echo htmlspecialchars('Actions', ENT_QUOTES, 'UTF-8'); ?></th>
                        <?php endif; ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($records as $record): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($record['animal_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($record['diagnosis'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($record['treatment'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($record['notes'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($record['recorded_by_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($record['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <?php if (in_array($role, ['admin', 'staff'], true)): ?>
                                <td>
                                    <div class="table-actions">
                                        <a href="<?php echo htmlspecialchars(app_url('pages/health_records_form.php?id=' . $record['id']), ENT_QUOTES, 'UTF-8'); ?>"
                                           class="btn-icon"
                                           title="<?php echo htmlspecialchars('Edit health record', ENT_QUOTES, 'UTF-8'); ?>"
                                           aria-label="<?php echo htmlspecialchars('Edit health record', ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo vet_icon('edit'); ?>
                                        </a>
                                        <?php if ($role === 'admin'): ?>
                                            <form method="post" action="<?php echo htmlspecialchars(app_url('pages/health_records_delete.php'), ENT_QUOTES, 'UTF-8'); ?>" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) $record['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <button type="submit"
                                                        class="btn-icon btn-icon-danger"
                                                        title="<?php echo htmlspecialchars('Delete health record', ENT_QUOTES, 'UTF-8'); ?>"
                                                        aria-label="<?php echo htmlspecialchars('Delete health record', ENT_QUOTES, 'UTF-8'); ?>"
                                                        onclick="return confirm('<?php echo htmlspecialchars('Delete this health record?', ENT_QUOTES, 'UTF-8'); ?>');">
                                                    <?php echo vet_icon('trash'); ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            <?php endif; ?>
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
