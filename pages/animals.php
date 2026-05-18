<?php
// pages/animals.php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$role = $_SESSION['role'] ?? '';
$userId = (int) ($_SESSION['user_id'] ?? 0);
$search = trim($_GET['search'] ?? '');
$ownerIdFilter = isset($_GET['owner_id']) ? (int) $_GET['owner_id'] : 0;
$params = [];

if ($role === 'owner') {
    $ownerStmt = $pdo->prepare('SELECT id FROM owners WHERE user_id = :user_id LIMIT 1');
    $ownerStmt->execute(['user_id' => $userId]);
    $owner = $ownerStmt->fetch();
    $ownerId = $owner ? (int) $owner['id'] : 0;

    $sql = 'SELECT animals.*, users.name AS owner_name
            FROM animals
            INNER JOIN owners ON animals.owner_id = owners.id
            INNER JOIN users ON owners.user_id = users.id
            WHERE animals.owner_id = :owner_id';
    $params['owner_id'] = $ownerId;
} else {
    $sql = 'SELECT animals.*, users.name AS owner_name
            FROM animals
            INNER JOIN owners ON animals.owner_id = owners.id
            INNER JOIN users ON owners.user_id = users.id
            WHERE 1 = 1';
}

if ($search !== '') {
    $sql .= ' AND (animals.name LIKE :search_name OR animals.species LIKE :search_species)';
    $params['search_name'] = '%' . $search . '%';
    $params['search_species'] = '%' . $search . '%';
}

if ($role !== 'owner' && $ownerIdFilter > 0) {
    $sql .= ' AND animals.owner_id = :owner_id_filter';
    $params['owner_id_filter'] = $ownerIdFilter;
}

$sql .= ' ORDER BY animals.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$animals = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="entity-page">
    <section class="entity-hero">
        <div>
            <div class="entity-kicker">
                <?php echo htmlspecialchars('Patient Directory', ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <h1 class="entity-title">
                <?php echo htmlspecialchars($role === 'owner' ? 'My Animals' : 'Animals', ENT_QUOTES, 'UTF-8'); ?>
            </h1>
            <p class="entity-subtitle">
                <?php echo htmlspecialchars('Review animal profiles, ownership details, and linked care records.', ENT_QUOTES, 'UTF-8'); ?>
            </p>
        </div>
        <?php if ($role !== 'staff'): ?>
            <div class="entity-actions">
                <a href="<?php echo htmlspecialchars(app_url('pages/animals_form.php'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary">
                    <?php echo vet_icon('animal'); ?>
                    <?php echo htmlspecialchars('Add New Animal', ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </div>
        <?php endif; ?>
    </section>

<div class="filter-card card shadow-sm mb-4">
    <div class="card-body">
        <form method="get" action="<?php echo htmlspecialchars(app_url('pages/animals.php'), ENT_QUOTES, 'UTF-8'); ?>" class="row g-2">
            <?php if ($role !== 'owner' && $ownerIdFilter > 0): ?>
                <input type="hidden" name="owner_id" value="<?php echo htmlspecialchars((string) $ownerIdFilter, ENT_QUOTES, 'UTF-8'); ?>">
            <?php endif; ?>
            <div class="col-md-10">
                <input type="text" name="search" class="form-control"
                       placeholder="<?php echo htmlspecialchars('Search by name or species', ENT_QUOTES, 'UTF-8'); ?>"
                       value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-outline-primary">
                    <?php echo htmlspecialchars('Search', ENT_QUOTES, 'UTF-8'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<div class="entity-table-card card shadow-sm">
    <div class="card-body">
        <?php if (empty($animals)): ?>
            <p class="empty-state text-muted mb-0">
                <?php echo htmlspecialchars('No animal records found.', ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                    <tr>
                        <th><?php echo htmlspecialchars('Name', ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars('Species', ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars('Breed', ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars('Age', ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars('Weight', ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars('Owner', ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars('Actions', ENT_QUOTES, 'UTF-8'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($animals as $animal): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($animal['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($animal['species']), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($animal['breed'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($animal['age'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($animal['weight'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($animal['owner_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <div class="table-actions">
                                    <?php if ($role !== 'staff'): ?>
                                        <a href="<?php echo htmlspecialchars(app_url('pages/animals_form.php?id=' . $animal['id']), ENT_QUOTES, 'UTF-8'); ?>"
                                           class="btn-icon"
                                           title="<?php echo htmlspecialchars('Edit animal', ENT_QUOTES, 'UTF-8'); ?>"
                                           aria-label="<?php echo htmlspecialchars('Edit animal', ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo vet_icon('edit'); ?>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($role === 'admin'): ?>
                                        <form method="post" action="<?php echo htmlspecialchars(app_url('pages/animals_delete.php'), ENT_QUOTES, 'UTF-8'); ?>" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) $animal['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <button type="submit"
                                                    class="btn-icon btn-icon-danger"
                                                    title="<?php echo htmlspecialchars('Delete animal', ENT_QUOTES, 'UTF-8'); ?>"
                                                    aria-label="<?php echo htmlspecialchars('Delete animal', ENT_QUOTES, 'UTF-8'); ?>"
                                                    onclick="return confirm('<?php echo htmlspecialchars('Delete this animal?', ENT_QUOTES, 'UTF-8'); ?>');">
                                                <?php echo vet_icon('trash'); ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <a href="<?php echo htmlspecialchars(app_url('pages/health_records.php?animal_id=' . $animal['id']), ENT_QUOTES, 'UTF-8'); ?>"
                                       class="btn-icon"
                                       title="<?php echo htmlspecialchars('View health records', ENT_QUOTES, 'UTF-8'); ?>"
                                       aria-label="<?php echo htmlspecialchars('View health records', ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo vet_icon('eye'); ?>
                                    </a>
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
