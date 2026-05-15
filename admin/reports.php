<?php
require_once '../config/config.php';
requireAdmin();

$deptStats    = $pdo->query("SELECT * FROM DepartmentDashboard")->fetchAll();
$highWards    = $pdo->query("SELECT * FROM HighIssueWards LIMIT 5")->fetchAll();
$officerLoad  = $pdo->query("SELECT * FROM OfficerWorkload ORDER BY assigned_complaints DESC")->fetchAll();
$categoryStats= $pdo->query("SELECT cat.type, COUNT(*) AS total FROM Complaint c JOIN Category cat ON c.category_id = cat.category_id GROUP BY cat.type ORDER BY total DESC")->fetchAll();
$avgRating    = $pdo->query("SELECT d.name, ROUND(AVG(f.citizen_rating),1) AS avg_rating, COUNT(f.feedback_id) AS total_reviews FROM Feedback f JOIN Complaint c ON f.complaint_id = c.complaint_id LEFT JOIN Department d ON c.assigned_dept_id = d.department_id GROUP BY d.name")->fetchAll();

include '../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h2><i class="fas fa-chart-bar"></i> Analytics & Reports</h2>
        <p>System-wide performance overview</p>
    </div>

    <div class="grid-2">
        <!-- Department Stats -->
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-building"></i> Department Performance</h3></div>
            <table class="table">
                <thead><tr><th>Department</th><th>Total</th><th>Resolved</th><th>Pending</th><th>Escalated</th></tr></thead>
                <tbody>
                    <?php foreach ($deptStats as $d): ?>
                    <tr>
                        <td><?= clean($d['department']) ?></td>
                        <td><strong><?= $d['total_complaints'] ?></strong></td>
                        <td style="color:#2ecc71"><?= $d['resolved'] ?></td>
                        <td style="color:#f39c12"><?= $d['pending'] ?></td>
                        <td style="color:#e74c3c"><?= $d['escalated'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Category Breakdown -->
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-tags"></i> Complaints by Category</h3></div>
            <table class="table">
                <thead><tr><th>Category</th><th>Total</th><th>Share</th></tr></thead>
                <?php
                    $grandTotal = array_sum(array_column($categoryStats, 'total'));
                ?>
                <tbody>
                    <?php foreach ($categoryStats as $cat): ?>
                    <tr>
                        <td><?= clean($cat['type']) ?></td>
                        <td><?= $cat['total'] ?></td>
                        <td>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width:<?= $grandTotal > 0 ? round($cat['total']/$grandTotal*100) : 0 ?>%"></div>
                                <span><?= $grandTotal > 0 ? round($cat['total']/$grandTotal*100) : 0 ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Officer Workload -->
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-user-tie"></i> Officer Workload</h3></div>
            <table class="table">
                <thead><tr><th>Officer</th><th>Department</th><th>Assigned</th><th>Resolved</th></tr></thead>
                <tbody>
                    <?php foreach ($officerLoad as $o): ?>
                    <tr>
                        <td><?= clean($o['officer_name']) ?></td>
                        <td><?= clean($o['department']) ?></td>
                        <td><?= $o['assigned_complaints'] ?></td>
                        <td style="color:#2ecc71"><?= $o['resolved_count'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Citizen Satisfaction -->
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-star"></i> Citizen Satisfaction</h3></div>
            <table class="table">
                <thead><tr><th>Department</th><th>Avg Rating</th><th>Reviews</th></tr></thead>
                <tbody>
                    <?php foreach ($avgRating as $r): ?>
                    <tr>
                        <td><?= clean($r['name'] ?? 'Unassigned') ?></td>
                        <td>
                            <?php for ($i=1;$i<=5;$i++): ?>
                                <i class="fas fa-star" style="color:<?= $i <= $r['avg_rating'] ? '#f1c40f' : '#ddd' ?>; font-size:12px;"></i>
                            <?php endfor; ?>
                            <?= $r['avg_rating'] ?>/5
                        </td>
                        <td><?= $r['total_reviews'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- High Issue Wards -->
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-map-marker-alt"></i> High Issue Areas (Top 5 Wards)</h3></div>
        <table class="table">
            <thead><tr><th>Ward</th><th>Municipality</th><th>Total Complaints</th><th>Heat Level</th></tr></thead>
            <tbody>
                <?php foreach ($highWards as $w): ?>
                <tr>
                    <td><?= clean($w['area_name']) ?></td>
                    <td><?= clean($w['municipality']) ?></td>
                    <td><?= $w['total_complaints'] ?></td>
                    <td>
                        <div class="progress-bar">
                            <div class="progress-fill red" style="width:<?= min(100, $w['total_complaints'] * 10) ?>%"></div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>