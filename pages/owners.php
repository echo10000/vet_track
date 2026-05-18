<?php
// pages/owners.php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
require_role('admin');

$stmt = $pdo->prepare(
    'SELECT owners.id, owners.phone, owners.address, users.name, COUNT(animals.id) AS animal_count
     FROM owners
     INNER JOIN users ON owners.user_id = users.id
     LEFT JOIN animals ON animals.owner_id = owners.id
     GROUP BY owners.id, owners.phone, owners.address, users.name
     ORDER BY users.name ASC'
);
$stmt->execute();
$owners = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="entity-page">
    <section class="entity-hero">
        <div>
            <div class="entity-kicker">
                <?php echo htmlspecialchars('Client Directory', ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <h1 class="entity-title">
                <?php echo htmlspecialchars('Owners', ENT_QUOTES, 'UTF-8'); ?>
            </h1>
            <p class="entity-subtitle">
                <?php echo htmlspecialchars('Review owner contact details and open linked animal profiles.', ENT_QUOTES, 'UTF-8'); ?>
            </p>
        </div>
    </section>

<div class="entity-table-card card shadow-sm">
    <div class="card-body">
        <?php if (empty($owners)): ?>
            <p class="empty-state text-muted mb-0">
                <?php echo htmlspecialchars('No owners found.', ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                    <tr>
                        <th><?php echo htmlspecialchars('Name', ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars('Phone', ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars('Address', ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars('Animal Count', ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars('Actions', ENT_QUOTES, 'UTF-8'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($owners as $owner): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($owner['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($owner['phone'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($owner['address'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $owner['animal_count'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <div class="table-actions">
                                    <a href="<?php echo htmlspecialchars(app_url('pages/owners_form.php?id=' . $owner['id']), ENT_QUOTES, 'UTF-8'); ?>"
                                       class="btn-icon"
                                       title="<?php echo htmlspecialchars('Edit contact info', ENT_QUOTES, 'UTF-8'); ?>"
                                       aria-label="<?php echo htmlspecialchars('Edit contact info', ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo vet_icon('edit'); ?>
                                    </a>
                                    <a href="<?php echo htmlspecialchars(app_url('pages/animals.php?owner_id=' . $owner['id']), ENT_QUOTES, 'UTF-8'); ?>"
                                       class="btn-icon"
                                       title="<?php echo htmlspecialchars('View animals', ENT_QUOTES, 'UTF-8'); ?>"
                                       aria-label="<?php echo htmlspecialchars('View animals', ENT_QUOTES, 'UTF-8'); ?>">
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
