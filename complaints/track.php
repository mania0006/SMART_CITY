<?php
require_once '../config/config.php';
requireCitizen();

$id = getUserId();
$complaints = $pdo->prepare("
    SELECT c.*, cat.type AS category, cat.priority_level,
           l.address AS location_address, w.area_name AS ward,
           d.name AS department
    FROM Complaint c
    JOIN Category cat ON c.category_id = cat.category_id
    JOIN Location l   ON c.location_id = l.location_id
    JOIN Ward w       ON l.ward_id = w.ward_id
    LEFT JOIN Department d ON c.assigned_dept_id = d.department_id
    WHERE c.citizen_id = ?
    ORDER BY c.submitted_date DESC
");
$complaints->execute([$id]);
$list = $complaints->fetchAll();

include '../includes/header.php';
?>

<div class="container">
    <?php showFlash(); ?>

    <div class="page-header">
        <h2><i class="fas fa-search"></i> Track My Complaints</h2>
        <p>Monitor the status of all your submitted complaints</p>
        <a href="<?= APP_URL ?>/complaints/submit.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Complaint
        </a>
    </div>

    <?php if (empty($list)): ?>
        <div class="empty-state">
            <i class="fas fa-clipboard-list empty-icon"></i>
            <h3>No complaints submitted yet</h3>
            <p>Submit your first civic complaint and track its progress here.</p>
            <a href="<?= APP_URL ?>/complaints/submit.php" class="btn btn-primary">Submit Now</a>
        </div>
    <?php else: ?>
        <div class="complaints-list">
            <?php foreach ($list as $c): ?>
            <div class="complaint-card">
                <div class="complaint-card-header">
                    <div>
                        <span class="complaint-id">#<?= $c['complaint_id'] ?></span>
                        <span class="complaint-category"><?= clean($c['category']) ?></span>
                        <span class="priority-badge priority-<?= strtolower($c['priority_level']) ?>"><?= clean($c['priority_level']) ?></span>
                    </div>
                    <?= statusBadge($c['status']) ?>
                </div>
                <div class="complaint-card-body">
                    <p class="complaint-desc"><?= clean($c['description']) ?></p>
                    <div class="complaint-meta">
                        <span><i class="fas fa-map-marker-alt"></i> <?= clean($c['location_address']) ?>, <?= clean($c['ward']) ?></span>
                        <span><i class="fas fa-building"></i> <?= clean($c['department'] ?? 'Unassigned') ?></span>
                        <span><i class="fas fa-calendar"></i> <?= $c['submitted_date'] ?></span>
                    </div>
                </div>
                <div class="complaint-card-footer">
                    <a href="<?= APP_URL ?>/complaints/view.php?id=<?= $c['complaint_id'] ?>" class="btn btn-sm">
                        <i class="fas fa-eye"></i> View Details
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>