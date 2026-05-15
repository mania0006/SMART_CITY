<?php
// ═══════════════════════════════════════════════════════════
//  MANAGE APPEALS — Admin reviews citizen appeals
//  Path: admin/manage_appeals.php
// ═══════════════════════════════════════════════════════════
require_once '../config/config.php';
requireAdmin();

// ── Decision ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['decide'])) {
    $aid      = (int)$_POST['appeal_id'];
    $decision = in_array($_POST['decision'], ['accepted','rejected']) ? $_POST['decision'] : null;
    if ($aid && $decision) {
        $pdo->prepare("UPDATE Appeal SET status=?, decision_date=CURDATE() WHERE appeal_id=?")
            ->execute([$decision, $aid]);
        // If accepted, re-open the complaint
        if ($decision === 'accepted') {
            $cid = (int)$_POST['complaint_id'];
            $pdo->prepare("UPDATE Complaint SET status='in_progress' WHERE complaint_id=?")->execute([$cid]);
            $pdo->prepare("INSERT INTO Status_Log (old_status,new_status,complaint_id) VALUES ('resolved','in_progress',?)")->execute([$cid]);
        }
        setFlash('success', 'Appeal ' . $decision . ' and updated.');
        redirect(APP_URL . '/admin/manage_appeals.php');
    }
}

$filter = clean($_GET['status'] ?? '');
$sql = "
    SELECT ap.*, c.description AS complaint_desc, c.status AS complaint_status,
           ci.name AS citizen_name, ci.email AS citizen_email,
           cat.type AS category
    FROM Appeal ap
    JOIN Complaint c  ON ap.complaint_id = c.complaint_id
    JOIN Citizen ci   ON c.citizen_id    = ci.citizen_id
    JOIN Category cat ON c.category_id   = cat.category_id
";
if ($filter) $sql .= " WHERE ap.status = " . $pdo->quote($filter);
$sql .= " ORDER BY ap.filed_date DESC";
$appeals = $pdo->query($sql)->fetchAll();

$totalPending  = $pdo->query("SELECT COUNT(*) FROM Appeal WHERE status='pending'")->fetchColumn();
$totalAccepted = $pdo->query("SELECT COUNT(*) FROM Appeal WHERE status='accepted'")->fetchColumn();
$totalRejected = $pdo->query("SELECT COUNT(*) FROM Appeal WHERE status='rejected'")->fetchColumn();

include '../includes/header.php';
?>
<div class="container">
    <?php showFlash(); ?>

    <div class="page-header">
        <div class="page-header-left">
            <div class="breadcrumb"><i class="fas fa-home"></i><span>/</span><span>Admin</span><span>/</span><span>Appeals</span></div>
            <h2><i class="fas fa-balance-scale"></i> Manage Appeals</h2>
            <p>Review and decide on citizen appeals for resolved complaints</p>
        </div>
    </div>

    <div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:32px;">
        <div class="stat-card blue">
            <div class="stat-icon-wrap"><i class="fas fa-balance-scale"></i></div>
            <div class="stat-info"><h3><?= count($appeals) ?></h3><p>Total Appeals</p></div>
        </div>
        <div class="stat-card orange">
            <div class="stat-icon-wrap"><i class="fas fa-hourglass-half"></i></div>
            <div class="stat-info"><h3><?= $totalPending ?></h3><p>Pending</p></div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon-wrap"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info"><h3><?= $totalAccepted ?></h3><p>Accepted</p></div>
        </div>
        <div class="stat-card red">
            <div class="stat-icon-wrap"><i class="fas fa-times-circle"></i></div>
            <div class="stat-info"><h3><?= $totalRejected ?></h3><p>Rejected</p></div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="tab-bar">
        <a href="?" class="tab <?= !$filter ? 'active' : '' ?>">All</a>
        <a href="?status=pending"  class="tab <?= $filter==='pending'  ? 'active' : '' ?>">⏳ Pending</a>
        <a href="?status=accepted" class="tab <?= $filter==='accepted' ? 'active' : '' ?>">✅ Accepted</a>
        <a href="?status=rejected" class="tab <?= $filter==='rejected' ? 'active' : '' ?>">❌ Rejected</a>
    </div>

    <?php if (empty($appeals)): ?>
    <div class="empty-state">
        <i class="fas fa-balance-scale empty-icon"></i>
        <h3>No appeals found</h3>
        <p>No citizen appeals match the current filter.</p>
    </div>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:16px;">
        <?php foreach ($appeals as $ap):
            $statusMap = [
                'pending'  => ['rgba(245,158,11,0.1)', '#fbbf24', 'rgba(245,158,11,0.25)', '⏳ Pending'],
                'accepted' => ['rgba(16,185,129,0.1)', '#34d399', 'rgba(16,185,129,0.25)', '✅ Accepted'],
                'rejected' => ['rgba(239,68,68,0.1)',  '#f87171', 'rgba(239,68,68,0.25)',  '❌ Rejected'],
            ];
            [$bg,$color,$border,$slabel] = $statusMap[$ap['status']] ?? $statusMap['pending'];
        ?>
        <div class="card" style="border-left:3px solid <?= $ap['status']==='pending' ? 'var(--warning)' : ($ap['status']==='accepted' ? 'var(--success)' : 'var(--danger)') ?>;">
            <div style="padding:22px 26px;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
                    <div style="flex:1;min-width:0;">
                        <!-- Header -->
                        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:10px;">
                            <span style="font-family:var(--font-mono);color:var(--gold-light);font-weight:700;">Appeal #<?= $ap['appeal_id'] ?></span>
                            <span style="background:var(--gold-dim);color:var(--gold-light);padding:2px 10px;border-radius:20px;font-size:11px;font-weight:600;border:1px solid var(--border-accent);"><?= clean($ap['category']) ?></span>
                            <span class="badge" style="background:<?= $bg ?>;color:<?= $color ?>;border-color:<?= $border ?>;font-size:12px;"><?= $slabel ?></span>
                        </div>
                        <!-- Citizen + Complaint info -->
                        <div style="display:flex;gap:20px;flex-wrap:wrap;font-size:13px;color:var(--text-muted);margin-bottom:12px;">
                            <span><i class="fas fa-user" style="color:var(--gold);margin-right:5px;"></i><?= clean($ap['citizen_name']) ?></span>
                            <span><i class="fas fa-envelope" style="color:var(--gold);margin-right:5px;"></i><?= clean($ap['citizen_email']) ?></span>
                            <span><i class="fas fa-link" style="color:var(--gold);margin-right:5px;"></i>
                                <a href="<?= APP_URL ?>/complaints/view.php?id=<?= $ap['complaint_id'] ?>" style="color:var(--gold-light);">Complaint #<?= $ap['complaint_id'] ?></a>
                            </span>
                            <span><i class="fas fa-calendar" style="color:var(--gold);margin-right:5px;"></i>Filed: <?= $ap['filed_date'] ?></span>
                            <?php if ($ap['decision_date']): ?>
                            <span><i class="fas fa-gavel" style="color:var(--gold);margin-right:5px;"></i>Decided: <?= $ap['decision_date'] ?></span>
                            <?php endif; ?>
                        </div>
                        <!-- Complaint description -->
                        <div style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:var(--r-sm);padding:12px 16px;margin-bottom:12px;">
                            <p style="font-size:12px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:5px;">Complaint</p>
                            <p style="font-size:14px;color:var(--text-secondary);line-height:1.6;"><?= clean(mb_strimwidth($ap['complaint_desc'], 0, 180, '…')) ?></p>
                        </div>
                        <!-- Appeal reason -->
                        <div style="background:var(--gold-trace);border:1px solid var(--border-accent);border-radius:var(--r-sm);padding:12px 16px;">
                            <p style="font-size:12px;font-weight:700;color:var(--gold);text-transform:uppercase;letter-spacing:1px;margin-bottom:5px;">Citizen's Appeal Reason</p>
                            <p style="font-size:14px;color:var(--text-secondary);line-height:1.6;"><?= clean($ap['reason']) ?></p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <?php if ($ap['status'] === 'pending'): ?>
                    <div style="display:flex;flex-direction:column;gap:8px;min-width:140px;">
                        <form method="POST">
                            <input type="hidden" name="appeal_id"    value="<?= $ap['appeal_id'] ?>">
                            <input type="hidden" name="complaint_id" value="<?= $ap['complaint_id'] ?>">
                            <input type="hidden" name="decision"     value="accepted">
                            <button type="submit" name="decide" class="btn btn-success btn-sm" style="width:100%;justify-content:center;" onclick="return confirm('Accept this appeal and re-open the complaint?')">
                                <i class="fas fa-check"></i> Accept
                            </button>
                        </form>
                        <form method="POST">
                            <input type="hidden" name="appeal_id"    value="<?= $ap['appeal_id'] ?>">
                            <input type="hidden" name="complaint_id" value="<?= $ap['complaint_id'] ?>">
                            <input type="hidden" name="decision"     value="rejected">
                            <button type="submit" name="decide" class="btn btn-danger btn-sm" style="width:100%;justify-content:center;" onclick="return confirm('Reject this appeal?')">
                                <i class="fas fa-times"></i> Reject
                            </button>
                        </form>
                        <a href="<?= APP_URL ?>/complaints/view.php?id=<?= $ap['complaint_id'] ?>" class="btn btn-secondary btn-sm" style="width:100%;justify-content:center;">
                            <i class="fas fa-eye"></i> View
                        </a>
                    </div>
                    <?php else: ?>
                    <div>
                        <a href="<?= APP_URL ?>/complaints/view.php?id=<?= $ap['complaint_id'] ?>" class="btn btn-secondary btn-sm">
                            <i class="fas fa-eye"></i> View Complaint
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>