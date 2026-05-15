<?php
require_once '../config/config.php';
requireOfficer();

$officer_id = getUserId();
$complaints = $pdo->prepare("
    SELECT c.*, cat.type AS category, cat.priority_level,
           l.address AS location_address, w.area_name AS ward,
           d.name AS department, ci.name AS citizen_name,
           oa.assigned_date
    FROM Complaint c
    JOIN Officer_Assignment oa ON c.complaint_id = oa.complaint_id
    JOIN Category cat  ON c.category_id = cat.category_id
    JOIN Location l    ON c.location_id = l.location_id
    JOIN Ward w        ON l.ward_id = w.ward_id
    LEFT JOIN Department d ON c.assigned_dept_id = d.department_id
    JOIN Citizen ci    ON c.citizen_id = ci.citizen_id
    WHERE oa.officer_id = ?
    ORDER BY oa.assigned_date DESC
");
$complaints->execute([$officer_id]);
$list = $complaints->fetchAll();

include '../includes/header.php';
?>

<div class="container">
    <?php showFlash(); ?>
    <div class="page-header">
        <h2><i class="fas fa-tasks"></i> Assigned Complaints</h2>
        <p>Complaints assigned to you for inspection and resolution</p>
    </div>

    <?php if (empty($list)): ?>
        <div class="empty-state">
            <i class="fas fa-clipboard empty-icon"></i>
            <h3>No complaints assigned yet</h3>
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
                        <span><i class="fas fa-user"></i> <?= clean($c['citizen_name']) ?></span>
                        <span><i class="fas fa-map-marker-alt"></i> <?= clean($c['location_address']) ?>, <?= clean($c['ward']) ?></span>
                        <span><i class="fas fa-calendar"></i> Assigned: <?= $c['assigned_date'] ?></span>
                    </div>
                </div>
                <div class="complaint-card-footer">
                    <a href="<?= APP_URL ?>/officer/update_status.php?id=<?= $c['complaint_id'] ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-edit"></i> Update Status
                    </a>
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