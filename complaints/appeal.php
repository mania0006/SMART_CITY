<?php
// complaints/appeal.php — FIXED
require_once '../config/config.php';
requireCitizen();

$complaint_id = (int)($_GET['id'] ?? 0);
$citizen_id   = getUserId();

if (!$complaint_id) {
    setFlash('error', 'Invalid complaint.');
    redirect(APP_URL . '/complaints/track.php');
}

$stmt = $pdo->prepare("
    SELECT c.complaint_id, c.status, c.description,
           cat.type AS category, d.name AS department
    FROM Complaint c
    JOIN Category cat ON c.category_id = cat.category_id
    LEFT JOIN Department d ON c.assigned_dept_id = d.department_id
    WHERE c.complaint_id = ? AND c.citizen_id = ?
");
$stmt->execute([$complaint_id, $citizen_id]);
$complaint = $stmt->fetch();

if (!$complaint) {
    setFlash('error', 'Complaint not found.');
    redirect(APP_URL . '/complaints/track.php');
}

if ($complaint['status'] !== 'resolved') {
    setFlash('warning', 'You can only appeal a resolved complaint. Status is currently: ' . $complaint['status']);
    redirect(APP_URL . '/complaints/view.php?id=' . $complaint_id);
}

$fetchAppeal = function() use ($pdo, $complaint_id) {
    $s = $pdo->prepare("SELECT * FROM Appeal WHERE complaint_id = ?");
    $s->execute([$complaint_id]);
    return $s->fetch();
};
$existingAppeal = $fetchAppeal();

// ── Submit ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['file_appeal']) && !$existingAppeal) {
    $reason = trim($_POST['reason'] ?? '');
    if (strlen($reason) < 10) {
        setFlash('error', 'Please provide a reason (minimum 10 characters).');
    } else {
        try {
            $pdo->prepare("
                INSERT INTO Appeal (reason, status, filed_date, complaint_id)
                VALUES (?, 'pending', CURDATE(), ?)
            ")->execute([$reason, $complaint_id]);
            setFlash('success', 'Appeal filed successfully! Admin will review within 3–5 business days.');
            redirect(APP_URL . '/complaints/view.php?id=' . $complaint_id);
        } catch (PDOException $e) {
            setFlash('error', 'Could not save appeal: ' . $e->getMessage());
        }
    }
    $existingAppeal = $fetchAppeal();
}

include '../includes/header.php';
?>
<div class="container">
    <?php showFlash(); ?>

    <div class="page-header">
        <div class="page-header-left">
            <div class="breadcrumb">
                <i class="fas fa-home"></i><span>/</span>
                <a href="<?= APP_URL ?>/complaints/track.php" style="color:var(--text-muted);text-decoration:none;">My Complaints</a>
                <span>/</span>
                <a href="<?= APP_URL ?>/complaints/view.php?id=<?= $complaint_id ?>" style="color:var(--text-muted);text-decoration:none;">#<?= $complaint_id ?></a>
                <span>/</span><span>Appeal</span>
            </div>
            <h2><i class="fas fa-balance-scale"></i> File an Appeal</h2>
            <p>Complaint #<?= $complaint_id ?> &nbsp;&middot;&nbsp; <?= clean($complaint['category']) ?></p>
        </div>
        <?= statusBadge($complaint['status']) ?>
    </div>

    <!-- Complaint summary card -->
    <div class="card" style="border-left:3px solid var(--gold);margin-bottom:26px;">
        <div style="padding:20px 26px;">
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:10px;">
                <span style="font-family:var(--font-mono);color:var(--gold-light);font-weight:700;">#<?= $complaint_id ?></span>
                <span style="background:var(--gold-dim);color:var(--gold-light);padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;border:1px solid var(--border-accent);"><?= clean($complaint['category']) ?></span>
                <span style="color:var(--text-muted);font-size:13px;"><?= clean($complaint['department'] ?? 'Unassigned') ?></span>
            </div>
            <p style="color:var(--text-secondary);font-size:15px;line-height:1.7;"><?= clean($complaint['description']) ?></p>
        </div>
    </div>

    <?php if ($existingAppeal):
        $apMap = [
            'pending'  => ['rgba(245,158,11,0.1)','#fbbf24','rgba(245,158,11,0.25)','⏳ Under Review'],
            'accepted' => ['rgba(16,185,129,0.1)','#34d399','rgba(16,185,129,0.25)','✅ Accepted'],
            'rejected' => ['rgba(239,68,68,0.1)', '#f87171','rgba(239,68,68,0.25)', '❌ Rejected'],
        ];
        [$apBg,$apCol,$apBd,$apLbl] = $apMap[$existingAppeal['status']] ?? $apMap['pending'];
    ?>
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-file-alt"></i> Your Appeal Status</h3></div>
        <div style="padding:26px;">
            <span class="badge" style="background:<?= $apBg ?>;color:<?= $apCol ?>;border-color:<?= $apBd ?>;font-size:15px;padding:9px 20px;margin-bottom:20px;display:inline-flex;"><?= $apLbl ?></span>
            <div class="detail-list">
                <div class="detail-row"><span>Filed On</span><strong><?= $existingAppeal['filed_date'] ?></strong></div>
                <?php if ($existingAppeal['decision_date']): ?>
                <div class="detail-row"><span>Decision Date</span><strong><?= $existingAppeal['decision_date'] ?></strong></div>
                <?php endif; ?>
                <div class="detail-row"><span>Your Reason</span><p style="color:var(--text-secondary);line-height:1.65;"><?= clean($existingAppeal['reason']) ?></p></div>
            </div>
            <?php if ($existingAppeal['status'] === 'pending'): ?>
            <div style="margin-top:16px;padding:14px 18px;background:var(--gold-trace);border:1px solid var(--border);border-radius:var(--r-sm);">
                <p style="color:var(--gold-light);font-size:14px;"><i class="fas fa-clock"></i> Awaiting admin review.</p>
            </div>
            <?php elseif ($existingAppeal['status'] === 'accepted'): ?>
            <div style="margin-top:16px;padding:14px 18px;background:rgba(34,197,94,0.07);border:1px solid rgba(34,197,94,0.2);border-radius:var(--r-sm);">
                <p style="color:#4ade80;font-size:14px;"><i class="fas fa-check-circle"></i> Appeal accepted — complaint has been re-opened.</p>
            </div>
            <?php else: ?>
            <div style="margin-top:16px;padding:14px 18px;background:rgba(239,68,68,0.07);border:1px solid rgba(239,68,68,0.2);border-radius:var(--r-sm);">
                <p style="color:#f87171;font-size:14px;"><i class="fas fa-times-circle"></i> Appeal rejected — original resolution stands.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php else: ?>
    <div class="card card-gold">
        <div class="card-header"><h3><i class="fas fa-pen"></i> State Your Reason for Appeal</h3></div>
        <form method="POST" class="form">
            <div class="form-section">
                <div style="margin-bottom:20px;padding:14px 18px;background:var(--gold-trace);border:1px solid var(--border);border-radius:var(--r-sm);">
                    <p style="color:var(--text-secondary);font-size:14px;line-height:1.75;">
                        <i class="fas fa-info-circle" style="color:var(--gold);margin-right:6px;"></i>
                        Explain why the resolution was unsatisfactory. Admin will review within <strong style="color:var(--gold-light);">3–5 business days</strong>.
                    </p>
                </div>
                <div class="form-group">
                    <label>Reason for Appeal *</label>
                    <textarea name="reason" rows="7"
                        placeholder="e.g. The complaint was marked resolved but the road is still broken. The repair was superficial and cracked again within 2 days. I request proper re-inspection and a permanent fix."
                        required></textarea>
                    <small>Be specific — mention what exactly was wrong with the resolution.</small>
                </div>
            </div>
            <div class="form-actions">
                <a href="<?= APP_URL ?>/complaints/view.php?id=<?= $complaint_id ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Cancel
                </a>
                <button type="submit" name="file_appeal" class="btn btn-primary">
                    <i class="fas fa-balance-scale"></i> Submit Appeal
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div style="margin-top:12px;">
        <a href="<?= APP_URL ?>/complaints/view.php?id=<?= $complaint_id ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Complaint
        </a>
    </div>
</div>
<?php include '../includes/footer.php'; ?>