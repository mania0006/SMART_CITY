<?php
require_once '../config/config.php';
requireOfficer();

$complaint_id = (int)($_GET['id'] ?? 0);
$officer_id   = getUserId();

$stmt = $pdo->prepare("SELECT c.*, cat.type AS category FROM Complaint c JOIN Category cat ON c.category_id = cat.category_id WHERE c.complaint_id = ?");
$stmt->execute([$complaint_id]);
$c = $stmt->fetch();

if (!$c) { setFlash('error', 'Complaint not found.'); redirect(APP_URL . '/officer/assigned_complaints.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action      = clean($_POST['action'] ?? '');
    $new_status  = clean($_POST['new_status'] ?? '');
    $report      = clean($_POST['report'] ?? '');
    $result      = clean($_POST['result'] ?? '');
    $action_taken= clean($_POST['action_taken'] ?? '');
    $quality     = clean($_POST['quality_check'] ?? '');

    if ($action === 'inspection' && !empty($report)) {
        $ins = $pdo->prepare("INSERT INTO Inspection (report, inspection_date, result, complaint_id, officer_id) VALUES (?, CURDATE(), ?, ?, ?)");
        $ins->execute([$report, $result, $complaint_id, $officer_id]);
        // Log in audit
        $audit = $pdo->prepare("INSERT INTO Audit_Log (action, table_affected, officer_id) VALUES (?, 'Inspection', ?)");
        $audit->execute(["Inspection added for complaint #$complaint_id", $officer_id]);
        setFlash('success', 'Inspection report submitted.');
    }

    if ($action === 'resolve' && !empty($action_taken)) {
        $res = $pdo->prepare("INSERT INTO Resolution (action_taken, resolved_date, quality_check, complaint_id, officer_id) VALUES (?, CURDATE(), ?, ?, ?)");
        $res->execute([$action_taken, $quality, $complaint_id, $officer_id]);
        $upd = $pdo->prepare("UPDATE Complaint SET status = 'resolved' WHERE complaint_id = ?");
        $upd->execute([$complaint_id]);
        setFlash('success', 'Complaint marked as resolved!');
    }

    if ($action === 'status' && !empty($new_status)) {
        $upd = $pdo->prepare("UPDATE Complaint SET status = ? WHERE complaint_id = ?");
        $upd->execute([$new_status, $complaint_id]);
        $log = $pdo->prepare("INSERT INTO Status_Log (old_status, new_status, complaint_id, officer_id) VALUES (?, ?, ?, ?)");
        $log->execute([$c['status'], $new_status, $complaint_id, $officer_id]);
        setFlash('success', 'Status updated to: ' . $new_status);
    }

    redirect(APP_URL . '/officer/update_status.php?id=' . $complaint_id);
}

include '../includes/header.php';
?>

<div class="container">
    <?php showFlash(); ?>
    <div class="page-header">
        <h2><i class="fas fa-edit"></i> Update Complaint #<?= $complaint_id ?></h2>
        <p><?= clean($c['category']) ?> — <?= statusBadge($c['status']) ?></p>
    </div>

    <div class="card">
        <p><strong>Description:</strong> <?= clean($c['description']) ?></p>
    </div>

    <div class="grid-2">
        <!-- Update Status -->
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-toggle-on"></i> Change Status</h3></div>
            <form method="POST">
                <input type="hidden" name="action" value="status">
                <div class="form-group">
                    <label>New Status</label>
                    <select name="new_status" required>
                        <option value="submitted">📋 Submitted</option>
                        <option value="in_progress">🔧 In Progress</option>
                        <option value="escalated">🚨 Escalated</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-full">Update Status</button>
            </form>
        </div>

        <!-- Add Inspection -->
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-search-location"></i> Add Inspection</h3></div>
            <form method="POST">
                <input type="hidden" name="action" value="inspection">
                <div class="form-group">
                    <label>Inspection Report *</label>
                    <textarea name="report" rows="3" placeholder="Describe your field inspection findings..." required></textarea>
                </div>
                <div class="form-group">
                    <label>Result</label>
                    <select name="result">
                        <option value="action_required">Action Required</option>
                        <option value="in_progress">In Progress</option>
                        <option value="resolved">Resolved</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-full">Submit Inspection</button>
            </form>
        </div>
    </div>

    <!-- Mark as Resolved -->
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-check-circle"></i> Mark as Resolved</h3></div>
        <form method="POST">
            <input type="hidden" name="action" value="resolve">
            <div class="form-group">
                <label>Action Taken *</label>
                <textarea name="action_taken" rows="3" placeholder="Describe what was done to resolve this complaint..." required></textarea>
            </div>
            <div class="form-group">
                <label>Quality Check</label>
                <select name="quality_check">
                    <option value="passed">Passed</option>
                    <option value="pending_review">Pending Review</option>
                    <option value="failed">Failed</option>
                </select>
            </div>
            <button type="submit" class="btn btn-success btn-full">
                <i class="fas fa-check"></i> Mark as Resolved
            </button>
        </form>
    </div>

    <a href="<?= APP_URL ?>/officer/assigned_complaints.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back
    </a>
</div>

<?php include '../includes/footer.php'; ?>