<?php
// admin/fines.php — FIXED
require_once '../config/config.php';
requireAdmin();

// ── Issue fine ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_fine'])) {
    $complaint_id = (int)($_POST['complaint_id'] ?? 0);
    $amount       = (float)($_POST['amount']      ?? 0);
    $reason       = trim($_POST['reason']         ?? '');

    if (!$complaint_id || $amount <= 0 || empty($reason)) {
        setFlash('error', 'All fields are required. Amount must be greater than 0.');
    } else {
        try {
            $pdo->prepare("
                INSERT INTO Fine (amount, reason, issued_date, paid_status, complaint_id)
                VALUES (?, ?, CURDATE(), 'unpaid', ?)
            ")->execute([$amount, $reason, $complaint_id]);
            setFlash('success', 'Fine of PKR ' . number_format($amount, 2) . ' issued successfully.');
            redirect(APP_URL . '/admin/fines.php');
        } catch (PDOException $e) {
            setFlash('error', 'Could not issue fine: ' . $e->getMessage());
        }
    }
}

// ── Mark paid ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_paid'])) {
    $fid = (int)$_POST['fine_id'];
    try {
        $pdo->prepare("UPDATE Fine SET paid_status = 'paid' WHERE fine_id = ?")->execute([$fid]);
        setFlash('success', 'Fine #' . $fid . ' marked as paid.');
    } catch (PDOException $e) {
        setFlash('error', 'Could not update: ' . $e->getMessage());
    }
    redirect(APP_URL . '/admin/fines.php');
}

// ── Fetch ────────────────────────────────────────────────────
$fines = $pdo->query("
    SELECT f.*, c.description AS complaint_desc, ci.name AS citizen_name
    FROM Fine f
    JOIN Complaint c ON f.complaint_id = c.complaint_id
    JOIN Citizen ci  ON c.citizen_id   = ci.citizen_id
    ORDER BY f.issued_date DESC, f.fine_id DESC
")->fetchAll();

$complaints = $pdo->query("
    SELECT c.complaint_id, c.description, c.status, ci.name AS citizen_name
    FROM Complaint c
    JOIN Citizen ci ON c.citizen_id = ci.citizen_id
    ORDER BY c.complaint_id DESC
")->fetchAll();

$filter      = clean($_GET['status'] ?? '');
$displayed   = $filter ? array_filter($fines, fn($f) => $f['paid_status'] === $filter) : $fines;
$totalFines  = count($fines);
$totalAmt    = array_sum(array_column($fines, 'amount'));
$paidAmt     = array_sum(array_map(fn($f) => $f['paid_status'] === 'paid' ? $f['amount'] : 0, $fines));
$unpaidCount = count(array_filter($fines, fn($f) => $f['paid_status'] === 'unpaid'));
$pct         = $totalAmt > 0 ? round($paidAmt / $totalAmt * 100) : 0;

include '../includes/header.php';
?>
<div class="container">
    <?php showFlash(); ?>

    <div class="page-header">
        <div class="page-header-left">
            <div class="breadcrumb"><i class="fas fa-home"></i><span>/</span><span>Admin</span><span>/</span><span>Fines</span></div>
            <h2><i class="fas fa-gavel"></i> Fine Management</h2>
            <p>Issue and track fines linked to civic complaints</p>
        </div>
    </div>

    <div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:32px;">
        <div class="stat-card blue">
            <div class="stat-icon-wrap"><i class="fas fa-list-alt"></i></div>
            <div class="stat-info"><h3><?= $totalFines ?></h3><p>Total Fines</p></div>
        </div>
        <div class="stat-card gold">
            <div class="stat-icon-wrap"><i class="fas fa-coins"></i></div>
            <div class="stat-info"><h3><?= $totalAmt >= 1000 ? number_format($totalAmt/1000, 0).'K' : number_format($totalAmt) ?></h3><p>Total (PKR)</p></div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon-wrap"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info"><h3><?= $paidAmt >= 1000 ? number_format($paidAmt/1000, 0).'K' : number_format($paidAmt) ?></h3><p>Collected (PKR)</p></div>
        </div>
        <div class="stat-card red">
            <div class="stat-icon-wrap"><i class="fas fa-exclamation-circle"></i></div>
            <div class="stat-info"><h3><?= $unpaidCount ?></h3><p>Unpaid</p></div>
        </div>
    </div>

    <div class="grid-2">
        <!-- Issue Fine Form -->
        <div class="card card-gold">
            <div class="card-header"><h3><i class="fas fa-plus-circle"></i> Issue New Fine</h3></div>
            <form method="POST" class="form">
                <div class="form-section">
                    <div class="form-group">
                        <label>Select Complaint *</label>
                        <select name="complaint_id" required>
                            <option value="">-- Select Complaint --</option>
                            <?php foreach ($complaints as $comp): ?>
                            <option value="<?= $comp['complaint_id'] ?>">
                                #<?= $comp['complaint_id'] ?> — <?= clean(mb_strimwidth($comp['description'], 0, 45, '…')) ?>
                                (<?= clean($comp['citizen_name']) ?>) [<?= $comp['status'] ?>]
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small>You can issue a fine for any complaint regardless of status.</small>
                    </div>
                    <div class="form-group">
                        <label>Fine Amount (PKR) *</label>
                        <input type="number" name="amount" min="100" step="100"
                               placeholder="e.g. 5000" required>
                    </div>
                    <div class="form-group">
                        <label>Reason *</label>
                        <textarea name="reason" rows="4"
                            placeholder="e.g. Contractor delayed road repair beyond agreed deadline by 14 days."
                            required></textarea>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" name="issue_fine" class="btn btn-primary">
                        <i class="fas fa-gavel"></i> Issue Fine
                    </button>
                </div>
            </form>
        </div>

        <!-- Summary -->
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-chart-pie"></i> Collection Summary</h3></div>
            <div style="padding:26px;">
                <div style="margin-bottom:22px;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                        <span style="color:var(--text-secondary);font-size:14px;">Recovery Rate</span>
                        <span style="color:var(--gold-light);font-weight:700;font-family:var(--font-mono);"><?= $pct ?>%</span>
                    </div>
                    <div class="progress-bar" style="height:10px;">
                        <div class="progress-fill" style="width:<?= $pct ?>%;"></div>
                    </div>
                </div>
                <div class="detail-list">
                    <div class="detail-row"><span>Total Issued</span><strong style="color:var(--gold-light);">PKR <?= number_format($totalAmt, 2) ?></strong></div>
                    <div class="detail-row"><span>Collected</span><strong style="color:var(--success);">PKR <?= number_format($paidAmt, 2) ?></strong></div>
                    <div class="detail-row"><span>Outstanding</span><strong style="color:var(--danger);">PKR <?= number_format($totalAmt - $paidAmt, 2) ?></strong></div>
                    <div class="detail-row"><span>Unpaid Count</span><strong><?= $unpaidCount ?> / <?= $totalFines ?></strong></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter tabs -->
    <div class="tab-bar">
        <a href="?"              class="tab <?= !$filter           ? 'active':'' ?>">All (<?= $totalFines ?>)</a>
        <a href="?status=unpaid" class="tab <?= $filter==='unpaid' ? 'active':'' ?>">⏳ Unpaid (<?= $unpaidCount ?>)</a>
        <a href="?status=paid"   class="tab <?= $filter==='paid'   ? 'active':'' ?>">✅ Paid (<?= $totalFines-$unpaidCount ?>)</a>
    </div>

    <div class="card">
        <table class="table">
            <thead>
                <tr><th>#ID</th><th>Complaint</th><th>Citizen</th><th>Amount (PKR)</th><th>Reason</th><th>Date</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php if (empty($displayed)): ?>
                <tr><td colspan="8" class="text-center" style="padding:40px;color:var(--text-muted);">No fines found.</td></tr>
                <?php else: foreach ($displayed as $f): ?>
                <tr>
                    <td style="font-family:var(--font-mono);color:var(--gold-light);">#<?= $f['fine_id'] ?></td>
                    <td>
                        <a href="<?= APP_URL ?>/complaints/view.php?id=<?= $f['complaint_id'] ?>"
                           style="color:var(--gold-light);font-weight:700;text-decoration:none;">
                            #<?= $f['complaint_id'] ?>
                        </a>
                    </td>
                    <td><?= clean($f['citizen_name']) ?></td>
                    <td style="color:var(--gold-light);font-weight:700;font-family:var(--font-mono);"><?= number_format($f['amount'], 2) ?></td>
                    <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--text-secondary);"
                        title="<?= clean($f['reason']) ?>"><?= clean($f['reason']) ?></td>
                    <td style="font-family:var(--font-mono);font-size:13px;"><?= $f['issued_date'] ?></td>
                    <td>
                        <?php if ($f['paid_status'] === 'paid'): ?>
                            <span class="badge" style="background:rgba(34,197,94,0.1);color:#4ade80;border-color:rgba(34,197,94,0.25);">✅ Paid</span>
                        <?php else: ?>
                            <span class="badge" style="background:rgba(239,68,68,0.1);color:#f87171;border-color:rgba(239,68,68,0.25);">⏳ Unpaid</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($f['paid_status'] === 'unpaid'): ?>
                        <form method="POST" data-confirm="Mark Fine #<?= $f['fine_id'] ?> as paid?">
                            <input type="hidden" name="fine_id" value="<?= $f['fine_id'] ?>">
                            <button type="submit" name="mark_paid" class="btn btn-success btn-sm">
                                <i class="fas fa-check"></i> Mark Paid
                            </button>
                        </form>
                        <?php else: ?>
                        <span style="color:var(--text-muted);font-size:13px;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include '../includes/footer.php'; ?>