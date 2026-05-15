<?php
// ═══════════════════════════════════════════════════════════
//  CONTRACTORS — Admin manages contractor directory
//  Path: admin/contractors.php
// ═══════════════════════════════════════════════════════════
require_once '../config/config.php';
requireAdmin();

// ── Add contractor ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_contractor'])) {
    $name     = clean($_POST['name']           ?? '');
    $contact  = clean($_POST['contact']        ?? '');
    $spec     = clean($_POST['specialization'] ?? '');
    $rating   = (float)($_POST['rating']       ?? 0);

    if (empty($name) || empty($spec)) {
        setFlash('error', 'Name and specialization are required.');
    } else {
        $pdo->prepare("INSERT INTO Contractor (name,contact,specialization,rating) VALUES (?,?,?,?)")
            ->execute([$name, $contact, $spec, min(5, max(0, $rating))]);
        setFlash('success', 'Contractor added successfully.');
        redirect(APP_URL . '/admin/contractors.php');
    }
}

// ── Delete contractor ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_contractor'])) {
    $cid = (int)$_POST['contractor_id'];
    // Check if in use
    $inUse = $pdo->prepare("SELECT COUNT(*) FROM Work_Order WHERE contractor_id=?");
    $inUse->execute([$cid]);
    if ($inUse->fetchColumn() > 0) {
        setFlash('error', 'Cannot delete — this contractor has existing work orders.');
    } else {
        $pdo->prepare("DELETE FROM Contractor WHERE contractor_id=?")->execute([$cid]);
        setFlash('success', 'Contractor removed.');
    }
    redirect(APP_URL . '/admin/contractors.php');
}

// ── Update rating ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_rating'])) {
    $cid    = (int)$_POST['contractor_id'];
    $rating = (float)$_POST['rating'];
    $pdo->prepare("UPDATE Contractor SET rating=? WHERE contractor_id=?")->execute([min(5,max(0,$rating)), $cid]);
    setFlash('success', 'Rating updated.');
    redirect(APP_URL . '/admin/contractors.php');
}

// ── Fetch ────────────────────────────────────────────────────
$contractors = $pdo->query("
    SELECT con.*,
           COUNT(wo.work_order_id)                                                             AS total_orders,
           SUM(CASE WHEN wo.status='completed' THEN 1 ELSE 0 END)                             AS completed_orders,
           SUM(CASE WHEN wo.status IN('pending','in_progress') THEN 1 ELSE 0 END)             AS active_orders
    FROM Contractor con
    LEFT JOIN Work_Order wo ON con.contractor_id = wo.contractor_id
    GROUP BY con.contractor_id
    ORDER BY con.rating DESC
")->fetchAll();

$specializations = ['Road Construction','Plumbing & Water','Electrical Works','Sanitation & Waste','Street Lighting','General Civil Works','Demolition','Landscaping'];

include '../includes/header.php';
?>
<div class="container">
    <?php showFlash(); ?>

    <div class="page-header">
        <div class="page-header-left">
            <div class="breadcrumb"><i class="fas fa-home"></i><span>/</span><span>Admin</span><span>/</span><span>Contractors</span></div>
            <h2><i class="fas fa-hard-hat"></i> Contractor Directory</h2>
            <p>Manage the list of contractors available for civic work orders</p>
        </div>
    </div>

    <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);max-width:620px;margin-bottom:32px;">
        <div class="stat-card blue">
            <div class="stat-icon-wrap"><i class="fas fa-hard-hat"></i></div>
            <div class="stat-info"><h3><?= count($contractors) ?></h3><p>Total Contractors</p></div>
        </div>
        <div class="stat-card gold">
            <div class="stat-icon-wrap"><i class="fas fa-star"></i></div>
            <div class="stat-info"><h3><?= count($contractors) > 0 ? round(array_sum(array_column($contractors,'rating'))/count($contractors),1) : 0 ?></h3><p>Avg Rating</p></div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon-wrap"><i class="fas fa-clipboard-check"></i></div>
            <div class="stat-info"><h3><?= array_sum(array_column($contractors,'completed_orders')) ?></h3><p>Completed Orders</p></div>
        </div>
    </div>

    <div class="grid-2">
        <!-- Add Contractor Form -->
        <div class="card card-gold">
            <div class="card-header"><h3><i class="fas fa-plus-circle"></i> Register New Contractor</h3></div>
            <form method="POST" class="form">
                <div class="form-section">
                    <div class="form-group">
                        <label>Company / Contractor Name *</label>
                        <input type="text" name="name" placeholder="e.g. BuildRight Pvt Ltd" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Contact Number</label>
                            <input type="text" name="contact" placeholder="e.g. 021-35001234">
                        </div>
                        <div class="form-group">
                            <label>Initial Rating (0–5)</label>
                            <input type="number" name="rating" min="0" max="5" step="0.1" value="0" placeholder="e.g. 4.2">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Specialization *</label>
                        <select name="specialization" required>
                            <option value="">-- Select --</option>
                            <?php foreach($specializations as $s): ?>
                            <option value="<?= $s ?>"><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" name="add_contractor" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Contractor
                    </button>
                </div>
            </form>
        </div>

        <!-- Quick Stats Panel -->
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-chart-bar"></i> Performance Overview</h3></div>
            <div style="padding:20px 0;">
                <?php foreach($contractors as $con): ?>
                <div style="padding:12px 22px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:14px;">
                    <div style="width:36px;height:36px;background:var(--gold-trace);border:1px solid var(--border-accent);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-hard-hat" style="color:var(--gold);font-size:15px;"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <p style="font-weight:600;color:var(--text-primary);font-size:14px;margin-bottom:3px;"><?= clean($con['name']) ?></p>
                        <div style="display:flex;gap:12px;font-size:12px;color:var(--text-muted);">
                            <span style="color:var(--success);">✅ <?= $con['completed_orders'] ?> done</span>
                            <span style="color:var(--warning);">🔧 <?= $con['active_orders'] ?> active</span>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <div style="display:flex;gap:2px;justify-content:flex-end;margin-bottom:2px;">
                            <?php for($i=1;$i<=5;$i++): ?>
                            <i class="fas fa-star" style="font-size:10px;color:<?= $i<=$con['rating'] ? 'var(--gold)' : 'var(--bg-elevated)' ?>;"></i>
                            <?php endfor; ?>
                        </div>
                        <p style="font-size:11px;color:var(--text-muted);"><?= $con['rating'] ?>/5</p>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if(empty($contractors)): ?>
                <div style="padding:30px;text-align:center;color:var(--text-muted);">No contractors yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Full Table -->
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-table"></i> All Contractors</h3></div>
        <table class="table">
            <thead>
                <tr><th>#</th><th>Name</th><th>Specialization</th><th>Contact</th><th>Rating</th><th>Orders</th><th>Update Rating</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php if(empty($contractors)): ?>
                <tr><td colspan="8" class="text-center" style="padding:40px;color:var(--text-muted);">No contractors registered.</td></tr>
                <?php else: foreach($contractors as $con): ?>
                <tr>
                    <td style="font-family:var(--font-mono);color:var(--gold-light);">#<?= $con['contractor_id'] ?></td>
                    <td><strong style="color:var(--text-primary);"><?= clean($con['name']) ?></strong></td>
                    <td style="color:var(--text-secondary);"><?= clean($con['specialization']) ?></td>
                    <td style="font-family:var(--font-mono);font-size:13px;"><?= clean($con['contact']) ?></td>
                    <td>
                        <div style="display:flex;gap:2px;align-items:center;">
                            <?php for($i=1;$i<=5;$i++): ?>
                            <i class="fas fa-star" style="font-size:13px;color:<?= $i<=$con['rating'] ? 'var(--gold)' : 'var(--bg-elevated)' ?>;"></i>
                            <?php endfor; ?>
                            <span style="margin-left:6px;font-size:13px;color:var(--text-muted);"><?= $con['rating'] ?></span>
                        </div>
                    </td>
                    <td>
                        <span style="color:var(--success);font-weight:600;"><?= $con['completed_orders'] ?></span>
                        <span style="color:var(--text-muted);font-size:12px;"> / <?= $con['total_orders'] ?></span>
                    </td>
                    <td>
                        <form method="POST" style="display:flex;gap:5px;align-items:center;">
                            <input type="hidden" name="contractor_id" value="<?= $con['contractor_id'] ?>">
                            <input type="number" name="rating" min="0" max="5" step="0.1" value="<?= $con['rating'] ?>"
                                style="width:65px;padding:5px 8px;background:var(--bg-input);border:1px solid var(--border-hover);border-radius:6px;color:var(--text-primary);font-size:13px;">
                            <button type="submit" name="update_rating" class="btn btn-sm btn-secondary" style="padding:5px 10px;">
                                <i class="fas fa-save"></i>
                            </button>
                        </form>
                    </td>
                    <td>
                        <form method="POST" data-confirm="Remove contractor '<?= clean($con['name']) ?>'?">
                            <input type="hidden" name="contractor_id" value="<?= $con['contractor_id'] ?>">
                            <button type="submit" name="delete_contractor" class="btn btn-danger btn-sm">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include '../includes/footer.php'; ?>