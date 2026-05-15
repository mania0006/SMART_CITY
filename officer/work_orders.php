<?php
// ═══════════════════════════════════════════════════════════
//  WORK ORDERS — Officer creates & manages work orders
//  Path: officer/work_orders.php
// ═══════════════════════════════════════════════════════════
require_once '../config/config.php';
requireOfficer();

$officer_id = getUserId();

// ── Create work order ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_work_order'])) {
    $complaint_id  = (int)($_POST['complaint_id']  ?? 0);
    $contractor_id = (int)($_POST['contractor_id'] ?? 0);
    $description   = clean($_POST['description']   ?? '');

    if (!$complaint_id || !$contractor_id || empty($description)) {
        setFlash('error', 'Please fill all required fields.');
    } else {
        // Check no duplicate
        $dup = $pdo->prepare("SELECT work_order_id FROM Work_Order WHERE complaint_id=? AND officer_id=?");
        $dup->execute([$complaint_id, $officer_id]);
        if ($dup->fetch()) {
            setFlash('warning', 'A work order already exists for this complaint from you.');
        } else {
            $pdo->prepare("INSERT INTO Work_Order (description,issued_date,status,complaint_id,contractor_id,officer_id) VALUES (?,CURDATE(),'pending',?,?,?)")
                ->execute([$description, $complaint_id, $contractor_id, $officer_id]);
            setFlash('success', 'Work order created and assigned to contractor.');
            redirect(APP_URL . '/officer/work_orders.php');
        }
    }
}

// ── Update status ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_wo_status'])) {
    $woid      = (int)$_POST['work_order_id'];
    $newStatus = in_array($_POST['wo_status'], ['pending','in_progress','completed','cancelled']) ? $_POST['wo_status'] : null;
    if ($woid && $newStatus) {
        $pdo->prepare("UPDATE Work_Order SET status=? WHERE work_order_id=? AND officer_id=?")->execute([$newStatus, $woid, $officer_id]);
        setFlash('success', 'Work order status updated.');
        redirect(APP_URL . '/officer/work_orders.php');
    }
}

// ── Fetch assigned complaints (only officer's assignments) ───
$myComplaints = $pdo->prepare("
    SELECT c.complaint_id, c.description, c.status, cat.type AS category
    FROM Complaint c
    JOIN Officer_Assignment oa ON c.complaint_id = oa.complaint_id
    JOIN Category cat ON c.category_id = cat.category_id
    WHERE oa.officer_id = ? AND c.status != 'resolved'
    ORDER BY c.complaint_id DESC
");
$myComplaints->execute([$officer_id]);
$myComplaints = $myComplaints->fetchAll();

$contractors = $pdo->query("SELECT * FROM Contractor ORDER BY rating DESC")->fetchAll();

$workOrders = $pdo->prepare("
    SELECT wo.*, c.description AS complaint_desc, cat.type AS category,
           con.name AS contractor_name, con.specialization, con.rating AS contractor_rating
    FROM Work_Order wo
    JOIN Complaint c    ON wo.complaint_id  = c.complaint_id
    JOIN Category cat   ON c.category_id    = cat.category_id
    JOIN Contractor con ON wo.contractor_id = con.contractor_id
    WHERE wo.officer_id = ?
    ORDER BY wo.issued_date DESC
");
$workOrders->execute([$officer_id]);
$workOrders = $workOrders->fetchAll();

$totalWO     = count($workOrders);
$pendingWO   = count(array_filter($workOrders, fn($w) => $w['status']==='pending'));
$activeWO    = count(array_filter($workOrders, fn($w) => $w['status']==='in_progress'));
$completedWO = count(array_filter($workOrders, fn($w) => $w['status']==='completed'));

include '../includes/header.php';
?>
<div class="container">
    <?php showFlash(); ?>

    <div class="page-header">
        <div class="page-header-left">
            <div class="breadcrumb"><i class="fas fa-home"></i><span>/</span><span>Officer</span><span>/</span><span>Work Orders</span></div>
            <h2><i class="fas fa-clipboard-list"></i> Work Orders</h2>
            <p>Create and manage contractor work orders for assigned complaints</p>
        </div>
    </div>

    <div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:32px;">
        <div class="stat-card blue">
            <div class="stat-icon-wrap"><i class="fas fa-file-alt"></i></div>
            <div class="stat-info"><h3><?= $totalWO ?></h3><p>Total Orders</p></div>
        </div>
        <div class="stat-card orange">
            <div class="stat-icon-wrap"><i class="fas fa-hourglass-start"></i></div>
            <div class="stat-info"><h3><?= $pendingWO ?></h3><p>Pending</p></div>
        </div>
        <div class="stat-card gold">
            <div class="stat-icon-wrap"><i class="fas fa-hard-hat"></i></div>
            <div class="stat-info"><h3><?= $activeWO ?></h3><p>In Progress</p></div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon-wrap"><i class="fas fa-check-double"></i></div>
            <div class="stat-info"><h3><?= $completedWO ?></h3><p>Completed</p></div>
        </div>
    </div>

    <div class="grid-2">
        <!-- Create Work Order -->
        <div class="card card-gold">
            <div class="card-header"><h3><i class="fas fa-plus-circle"></i> Create Work Order</h3></div>
            <form method="POST" class="form">
                <div class="form-section">
                    <div class="form-group">
                        <label>Complaint *</label>
                        <?php if (empty($myComplaints)): ?>
                        <div style="padding:12px;background:var(--bg-elevated);border:1px solid var(--border);border-radius:var(--r-sm);color:var(--text-muted);font-size:14px;">
                            <i class="fas fa-info-circle" style="color:var(--gold);margin-right:6px;"></i>
                            No active complaints assigned to you.
                        </div>
                        <?php else: ?>
                        <select name="complaint_id" required>
                            <option value="">-- Select Complaint --</option>
                            <?php foreach($myComplaints as $c): ?>
                            <option value="<?= $c['complaint_id'] ?>">
                                #<?= $c['complaint_id'] ?> — <?= clean(mb_strimwidth($c['description'],0,45,'…')) ?> [<?= clean($c['category']) ?>]
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>Assign Contractor *</label>
                        <?php if (empty($contractors)): ?>
                        <div style="padding:12px;background:var(--bg-elevated);border:1px solid var(--border);border-radius:var(--r-sm);color:var(--text-muted);font-size:14px;">
                            No contractors registered. Contact admin.
                        </div>
                        <?php else: ?>
                        <select name="contractor_id" required>
                            <option value="">-- Select Contractor --</option>
                            <?php foreach($contractors as $con): ?>
                            <option value="<?= $con['contractor_id'] ?>">
                                <?= clean($con['name']) ?> — <?= clean($con['specialization']) ?> (⭐ <?= $con['rating'] ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>Work Description *</label>
                        <textarea name="description" rows="5" placeholder="Describe the work to be carried out by the contractor in detail..." required></textarea>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" name="create_work_order" class="btn btn-primary" <?= (empty($myComplaints) || empty($contractors)) ? 'disabled' : '' ?>>
                        <i class="fas fa-paper-plane"></i> Issue Work Order
                    </button>
                </div>
            </form>
        </div>

        <!-- Contractor Directory -->
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-hard-hat"></i> Contractor Directory</h3></div>
            <?php if (empty($contractors)): ?>
            <div style="padding:30px;text-align:center;color:var(--text-muted);">No contractors registered yet.</div>
            <?php else: ?>
            <div style="padding:8px 0;">
                <?php foreach($contractors as $con): ?>
                <div style="padding:14px 22px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:14px;">
                    <div style="width:42px;height:42px;background:var(--gold-trace);border:1px solid var(--border-accent);border-radius:var(--r-sm);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-hard-hat" style="color:var(--gold);font-size:18px;"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <p style="font-weight:700;color:var(--text-primary);font-size:15px;margin-bottom:2px;"><?= clean($con['name']) ?></p>
                        <p style="font-size:13px;color:var(--text-muted);"><?= clean($con['specialization']) ?> &nbsp;·&nbsp; <?= clean($con['contact']) ?></p>
                    </div>
                    <div style="text-align:right;flex-shrink:0;">
                        <?php for($i=1;$i<=5;$i++): ?>
                            <i class="fas fa-star" style="font-size:11px;color:<?= $i<=$con['rating'] ? 'var(--gold)' : 'var(--bg-elevated)' ?>;"></i>
                        <?php endfor; ?>
                        <p style="font-size:12px;color:var(--text-muted);margin-top:2px;"><?= $con['rating'] ?>/5</p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Work Orders Table -->
    <?php if (!empty($workOrders)): ?>
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-table"></i> My Work Orders</h3></div>
        <table class="table">
            <thead>
                <tr><th>#WO</th><th>Complaint</th><th>Contractor</th><th>Description</th><th>Date</th><th>Status</th><th>Update</th></tr>
            </thead>
            <tbody>
                <?php foreach($workOrders as $wo):
                    $statusMap = [
                        'pending'     => ['rgba(245,158,11,0.1)', '#fbbf24', '⏳ Pending'],
                        'in_progress' => ['rgba(99,102,241,0.1)', '#818cf8', '🔧 In Progress'],
                        'completed'   => ['rgba(16,185,129,0.1)', '#34d399', '✅ Completed'],
                        'cancelled'   => ['rgba(239,68,68,0.1)',  '#f87171', '❌ Cancelled'],
                    ];
                    [$sbg,$scol,$slbl] = $statusMap[$wo['status']] ?? $statusMap['pending'];
                ?>
                <tr>
                    <td style="font-family:var(--font-mono);color:var(--gold-light);font-weight:700;">#<?= $wo['work_order_id'] ?></td>
                    <td>
                        <a href="<?= APP_URL ?>/complaints/view.php?id=<?= $wo['complaint_id'] ?>" style="color:var(--gold-light);font-weight:600;">#<?= $wo['complaint_id'] ?></a>
                        <br><small style="color:var(--text-muted);"><?= clean($wo['category']) ?></small>
                    </td>
                    <td>
                        <strong><?= clean($wo['contractor_name']) ?></strong>
                        <br><small style="color:var(--text-muted);"><?= clean($wo['specialization']) ?></small>
                    </td>
                    <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--text-secondary);" title="<?= clean($wo['description']) ?>">
                        <?= clean($wo['description']) ?>
                    </td>
                    <td style="font-family:var(--font-mono);font-size:12px;"><?= $wo['issued_date'] ?></td>
                    <td>
                        <span class="badge" style="background:<?= $sbg ?>;color:<?= $scol ?>;border-color:<?= $sbg ?>;"><?= $slbl ?></span>
                    </td>
                    <td>
                        <?php if($wo['status'] !== 'completed' && $wo['status'] !== 'cancelled'): ?>
                        <form method="POST" style="display:flex;gap:5px;align-items:center;">
                            <input type="hidden" name="work_order_id" value="<?= $wo['work_order_id'] ?>">
                            <select name="wo_status" style="font-size:12px;padding:4px 8px;background:var(--bg-input);border:1px solid var(--border-hover);border-radius:6px;color:var(--text-primary);">
                                <option value="pending"     <?= $wo['status']==='pending'     ? 'selected':'' ?>>Pending</option>
                                <option value="in_progress" <?= $wo['status']==='in_progress' ? 'selected':'' ?>>In Progress</option>
                                <option value="completed"   <?= $wo['status']==='completed'   ? 'selected':'' ?>>Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <button type="submit" name="update_wo_status" class="btn btn-sm btn-secondary" style="padding:5px 10px;">
                                <i class="fas fa-save"></i>
                            </button>
                        </form>
                        <?php else: ?>
                        <span style="color:var(--text-muted);font-size:13px;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-clipboard empty-icon"></i>
        <h3>No work orders yet</h3>
        <p>Create your first work order using the form above.</p>
    </div>
    <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>