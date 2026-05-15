<?php
require_once '../config/config.php';
requireAdmin();

// Assign officer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign'])) {
    $cid = (int)$_POST['complaint_id'];
    $oid = (int)$_POST['officer_id'];
    $check = $pdo->prepare("SELECT * FROM Officer_Assignment WHERE complaint_id = ? AND officer_id = ?");
    $check->execute([$cid, $oid]);
    if (!$check->fetch()) {
        $ins = $pdo->prepare("INSERT INTO Officer_Assignment (assigned_date, status, officer_id, complaint_id) VALUES (CURDATE(), 'active', ?, ?)");
        $ins->execute([$oid, $cid]);
        $upd = $pdo->prepare("UPDATE Complaint SET status = 'in_progress' WHERE complaint_id = ?");
        $upd->execute([$cid]);
        setFlash('success', 'Officer assigned successfully!');
    } else {
        setFlash('warning', 'Officer already assigned to this complaint.');
    }
    redirect(APP_URL . '/admin/manage_complaint.php');
}

$filter = clean($_GET['status'] ?? '');
$sql = "SELECT c.*, cat.type AS category, ci.name AS citizen_name, d.name AS department
        FROM Complaint c
        JOIN Category cat ON c.category_id = cat.category_id
        JOIN Citizen ci   ON c.citizen_id  = ci.citizen_id
        LEFT JOIN Department d ON c.assigned_dept_id = d.department_id";
if ($filter) $sql .= " WHERE c.status = " . $pdo->quote($filter);
$sql .= " ORDER BY c.submitted_date DESC";
$complaints = $pdo->query($sql)->fetchAll();
$officers   = $pdo->query("SELECT o.*, d.name AS dept FROM Officer o JOIN Department d ON o.department_id = d.department_id")->fetchAll();

include '../includes/header.php';
?>

<div class="container">
    <?php showFlash(); ?>
    <div class="page-header">
        <h2><i class="fas fa-cogs"></i> Manage Complaints</h2>
    </div>

    <!-- Filter Tabs -->
    <div class="tab-bar">
        <a href="?" class="tab <?= !$filter ? 'active' : '' ?>">All</a>
        <a href="?status=submitted" class="tab <?= $filter==='submitted' ? 'active' : '' ?>">Submitted</a>
        <a href="?status=in_progress" class="tab <?= $filter==='in_progress' ? 'active' : '' ?>">In Progress</a>
        <a href="?status=resolved" class="tab <?= $filter==='resolved' ? 'active' : '' ?>">Resolved</a>
        <a href="?status=escalated" class="tab <?= $filter==='escalated' ? 'active' : '' ?>">Escalated</a>
    </div>

    <div class="card">
        <table class="table">
            <thead>
                <tr><th>#ID</th><th>Citizen</th><th>Category</th><th>Department</th><th>Date</th><th>Status</th><th>Assign Officer</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php foreach ($complaints as $c): ?>
                <tr>
                    <td>#<?= $c['complaint_id'] ?></td>
                    <td><?= clean($c['citizen_name']) ?></td>
                    <td><?= clean($c['category']) ?></td>
                    <td><?= clean($c['department'] ?? 'Unassigned') ?></td>
                    <td><?= $c['submitted_date'] ?></td>
                    <td><?= statusBadge($c['status']) ?></td>
                    <td>
                        <form method="POST" style="display:flex;gap:6px;">
                            <input type="hidden" name="complaint_id" value="<?= $c['complaint_id'] ?>">
                            <input type="hidden" name="assign" value="1">
                            <select name="officer_id" style="font-size:12px;padding:4px;">
                                <option value="">-- Officer --</option>
                                <?php foreach ($officers as $o): ?>
                                    <option value="<?= $o['officer_id'] ?>"><?= clean($o['name']) ?> (<?= clean($o['dept']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-sm">Assign</button>
                        </form>
                    </td>
                    <td>
                        <a href="<?= APP_URL ?>/complaints/view.php?id=<?= $c['complaint_id'] ?>" class="btn btn-sm">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>