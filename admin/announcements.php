<?php
require_once '../config/config.php';
requireAdmin();

// ── Delete ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_announcement'])) {
    $pdo->prepare("DELETE FROM Announcement WHERE announcement_id = ?")->execute([(int)$_POST['announcement_id']]);
    setFlash('success', 'Announcement deleted.');
    redirect(APP_URL . '/admin/announcements.php');
}

// ── Add ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_announcement'])) {
    $title   = clean($_POST['title']           ?? '');
    $content = clean($_POST['content']         ?? '');
    $dept_id = (int)($_POST['department_id']   ?? 0);
    $muni_id = (int)($_POST['municipality_id'] ?? 0);

    if (empty($title) || empty($content) || !$dept_id || !$muni_id) {
        setFlash('error', 'Please fill all required fields.');
    } else {
        $pdo->prepare("INSERT INTO Announcement (title,content,published_date,department_id,municipality_id) VALUES (?,?,CURDATE(),?,?)")
            ->execute([$title, $content, $dept_id, $muni_id]);
        setFlash('success', 'Announcement published successfully!');
        redirect(APP_URL . '/admin/announcements.php');
    }
}

// ── Fetch ────────────────────────────────────────────────────
$announcements = $pdo->query("
    SELECT a.*, d.name AS dept_name, m.name AS muni_name, m.city
    FROM Announcement a
    JOIN Department d   ON a.department_id   = d.department_id
    JOIN Municipality m ON a.municipality_id = m.municipality_id
    ORDER BY a.published_date DESC
")->fetchAll();

$departments    = $pdo->query("SELECT * FROM Department ORDER BY name")->fetchAll();
$municipalities = $pdo->query("SELECT * FROM Municipality ORDER BY name")->fetchAll();
$total          = count($announcements);
$thisMonth      = $pdo->query("SELECT COUNT(*) FROM Announcement WHERE MONTH(published_date)=MONTH(CURDATE())")->fetchColumn();

include '../includes/header.php';
?>
<div class="container">
    <?php showFlash(); ?>

    <div class="page-header">
        <div class="page-header-left">
            <div class="breadcrumb"><i class="fas fa-home"></i><span>/</span><span>Admin</span><span>/</span><span>Announcements</span></div>
            <h2><i class="fas fa-bullhorn"></i> Announcements</h2>
            <p>Publish public notices to citizens about maintenance, outages or city updates</p>
        </div>
    </div>

    <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);max-width:620px;margin-bottom:32px;">
        <div class="stat-card gold">
            <div class="stat-icon-wrap"><i class="fas fa-bullhorn"></i></div>
            <div class="stat-info"><h3><?= $total ?></h3><p>Total</p></div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon-wrap"><i class="fas fa-calendar-check"></i></div>
            <div class="stat-info"><h3><?= $thisMonth ?></h3><p>This Month</p></div>
        </div>
        <div class="stat-card blue">
            <div class="stat-icon-wrap"><i class="fas fa-building"></i></div>
            <div class="stat-info"><h3><?= count($departments) ?></h3><p>Departments</p></div>
        </div>
    </div>

    <div class="grid-2">
        <!-- Form -->
        <div class="card card-gold">
            <div class="card-header"><h3><i class="fas fa-plus-circle"></i> Publish New Announcement</h3></div>
            <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>" class="form">
                <div class="form-section">
                    <div class="form-group">
                        <label>Title *</label>
                        <input type="text" name="title" placeholder="e.g. Road Maintenance — University Road Dec 25-30" required>
                    </div>
                    <div class="form-group">
                        <label>Content *</label>
                        <textarea name="content" rows="6" placeholder="Write the full announcement message for citizens..." required></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Department *</label>
                            <select name="department_id" required>
                                <option value="">-- Select --</option>
                                <?php foreach($departments as $d): ?>
                                <option value="<?= $d['department_id'] ?>"><?= clean($d['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Municipality *</label>
                            <select name="municipality_id" required>
                                <option value="">-- Select --</option>
                                <?php foreach($municipalities as $m): ?>
                                <option value="<?= $m['municipality_id'] ?>"><?= clean($m['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" name="add_announcement" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Publish Announcement
                    </button>
                </div>
            </form>
        </div>

        <!-- List -->
        <div>
            <?php if (empty($announcements)): ?>
            <div class="empty-state">
                <i class="fas fa-bullhorn empty-icon"></i>
                <h3>No announcements yet</h3>
                <p>Publish your first announcement using the form on the left.</p>
            </div>
            <?php else: foreach ($announcements as $a): ?>
            <div class="card" style="margin-bottom:16px;border-left:3px solid var(--gold);">
                <div style="padding:20px;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;">
                        <div style="flex:1;min-width:0;">
                            <h4 style="font-size:1rem;font-weight:700;color:var(--text-primary);margin-bottom:8px;"><?= clean($a['title']) ?></h4>
                            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
                                <span style="background:var(--gold-dim);color:var(--gold-light);padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;border:1px solid var(--border-accent);">
                                    <i class="fas fa-building"></i> <?= clean($a['dept_name']) ?>
                                </span>
                                <span style="background:rgba(56,189,248,0.08);color:#38bdf8;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;border:1px solid rgba(56,189,248,0.2);">
                                    <i class="fas fa-city"></i> <?= clean($a['city']) ?>
                                </span>
                                <span style="font-size:11px;color:var(--text-muted);font-family:var(--font-mono);">
                                    <i class="fas fa-calendar-alt"></i> <?= $a['published_date'] ?>
                                </span>
                            </div>
                            <p style="font-size:14px;color:var(--text-secondary);line-height:1.65;"><?= clean($a['content']) ?></p>
                        </div>
                        <form method="POST" data-confirm="Delete this announcement?">
                            <input type="hidden" name="announcement_id" value="<?= $a['announcement_id'] ?>">
                            <button type="submit" name="delete_announcement" class="btn btn-danger btn-sm" style="flex-shrink:0;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>