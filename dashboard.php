<?php
// dashboard.php — updated with new features
require_once 'config/config.php';
requireLogin();

if (isCitizen()) {
    $id = getUserId();
    $total    = $pdo->prepare("SELECT COUNT(*) FROM Complaint WHERE citizen_id=?"); $total->execute([$id]);
    $resolved = $pdo->prepare("SELECT COUNT(*) FROM Complaint WHERE citizen_id=? AND status='resolved'"); $resolved->execute([$id]);
    $pending  = $pdo->prepare("SELECT COUNT(*) FROM Complaint WHERE citizen_id=? AND status='submitted'"); $pending->execute([$id]);
    $recent   = $pdo->prepare("SELECT c.*,cat.type AS category FROM Complaint c JOIN Category cat ON c.category_id=cat.category_id WHERE c.citizen_id=? ORDER BY c.submitted_date DESC LIMIT 5"); $recent->execute([$id]);
    $tc=$total->fetchColumn(); $rc=$resolved->fetchColumn(); $pc=$pending->fetchColumn(); $list=$recent->fetchAll();

    // Pending surveys count
    $surveyQuestions = [
        "How satisfied are you with the overall civic complaint resolution service?",
        "How would you rate the response time of government departments?",
        "Do you feel your complaints are taken seriously by the authorities?",
        "How easy was it to submit and track your complaint online?",
        "Would you recommend this platform to other citizens in your area?",
    ];
    $answered = $pdo->prepare("SELECT COUNT(DISTINCT question) FROM Survey WHERE citizen_id=?");
    $answered->execute([$id]);
    $pendingSurveys = count($surveyQuestions) - (int)$answered->fetchColumn();

    // Open appeals
    $openAppeals = $pdo->prepare("SELECT COUNT(*) FROM Appeal ap JOIN Complaint c ON ap.complaint_id=c.complaint_id WHERE c.citizen_id=? AND ap.status='pending'");
    $openAppeals->execute([$id]);
    $openAppeals = (int)$openAppeals->fetchColumn();
}

if (isOfficer()) {
    $id = $_SESSION['user_id'];
    $assigned = $pdo->prepare("SELECT COUNT(*) FROM Officer_Assignment WHERE officer_id=?"); $assigned->execute([$id]);
    $rres     = $pdo->prepare("SELECT COUNT(*) FROM Resolution WHERE officer_id=?");           $rres->execute([$id]);
    $woCount  = $pdo->prepare("SELECT COUNT(*) FROM Work_Order WHERE officer_id=? AND status IN('pending','in_progress')"); $woCount->execute([$id]);
    $recent   = $pdo->prepare("SELECT c.*,cat.type AS category,oa.assigned_date FROM Complaint c JOIN Officer_Assignment oa ON c.complaint_id=oa.complaint_id JOIN Category cat ON c.category_id=cat.category_id WHERE oa.officer_id=? ORDER BY oa.assigned_date DESC LIMIT 6"); $recent->execute([$id]);
    $ac=$assigned->fetchColumn(); $orc=$rres->fetchColumn(); $owc=$woCount->fetchColumn(); $list=$recent->fetchAll();
}

if (isAdmin()) {
    $tc       = $pdo->query("SELECT COUNT(*) FROM Complaint")->fetchColumn();
    $rc       = $pdo->query("SELECT COUNT(*) FROM Complaint WHERE status='resolved'")->fetchColumn();
    $pc       = $pdo->query("SELECT COUNT(*) FROM Complaint WHERE status='submitted'")->fetchColumn();
    $ec       = $pdo->query("SELECT COUNT(*) FROM Complaint WHERE status='escalated'")->fetchColumn();
    $citizens = $pdo->query("SELECT COUNT(*) FROM Citizen")->fetchColumn();
    $officers = $pdo->query("SELECT COUNT(*) FROM Officer")->fetchColumn();
    // New counts
    $pendingAppeals = $pdo->query("SELECT COUNT(*) FROM Appeal WHERE status='pending'")->fetchColumn();
    $unpaidFines    = $pdo->query("SELECT COUNT(*) FROM Fine WHERE paid_status='unpaid'")->fetchColumn();
    $activeWO       = $pdo->query("SELECT COUNT(*) FROM Work_Order WHERE status IN('pending','in_progress')")->fetchColumn();
    $deptStats      = $pdo->query("SELECT * FROM DepartmentDashboard")->fetchAll();
    $recent         = $pdo->query("SELECT c.*,cat.type AS category,ci.name AS citizen_name FROM Complaint c JOIN Category cat ON c.category_id=cat.category_id JOIN Citizen ci ON c.citizen_id=ci.citizen_id ORDER BY c.submitted_date DESC LIMIT 8")->fetchAll();
    // Latest announcements
    $latestAnn = $pdo->query("SELECT a.title,a.published_date,d.name AS dept FROM Announcement a JOIN Department d ON a.department_id=d.department_id ORDER BY a.published_date DESC LIMIT 3")->fetchAll();
}

include 'includes/header.php';
?>
<div class="dashboard">
    <?php showFlash(); ?>

    <div class="page-header">
        <div class="page-header-left">
            <div class="breadcrumb"><i class="fas fa-home"></i><span>/</span><span>Dashboard</span></div>
            <h2>
                <?php if(isAdmin()) echo 'System Overview'; ?>
                <?php if(isCitizen()) echo 'My Portal'; ?>
                <?php if(isOfficer()) echo 'Officer Dashboard'; ?>
            </h2>
            <p>Welcome back, <strong style="color:var(--gold-light);"><?= clean(getUserName()) ?></strong>
               &nbsp;&middot;&nbsp; <?= date('l, d M Y') ?></p>
        </div>
        <?php if(isCitizen()): ?>
        <a href="<?= APP_URL ?>/complaints/submit.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Complaint
        </a>
        <?php endif; ?>
    </div>

    <!-- ═══ CITIZEN ═══════════════════════════════════════════ -->
    <?php if(isCitizen()): ?>
    <div class="stats-grid">
        <div class="stat-card blue">
            <div class="stat-icon-wrap"><i class="fas fa-clipboard-list"></i></div>
            <div class="stat-info"><h3><?= $tc ?></h3><p>Total Complaints</p></div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon-wrap"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info"><h3><?= $rc ?></h3><p>Resolved</p></div>
        </div>
        <div class="stat-card orange">
            <div class="stat-icon-wrap"><i class="fas fa-clock"></i></div>
            <div class="stat-info"><h3><?= $pc ?></h3><p>Pending</p></div>
        </div>
        <?php if($openAppeals > 0): ?>
        <div class="stat-card red">
            <div class="stat-icon-wrap"><i class="fas fa-balance-scale"></i></div>
            <div class="stat-info"><h3><?= $openAppeals ?></h3><p>Open Appeals</p></div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Pending survey notice -->
    <?php if($pendingSurveys > 0): ?>
    <div class="flash info" style="margin-bottom:22px;">
        <i class="fas fa-poll"></i>
        You have <strong><?= $pendingSurveys ?> pending survey question(s)</strong> — your feedback helps improve city services.
        <a href="<?= APP_URL ?>/complaints/survey.php" class="btn btn-sm btn-secondary" style="margin-left:auto;">Take Survey</a>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-history"></i> Recent Complaints</h3>
            <a href="<?= APP_URL ?>/complaints/track.php" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <table class="table">
            <thead><tr><th>#ID</th><th>Category</th><th>Description</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
                <?php if(empty($list)): ?>
                <tr><td colspan="6" class="text-center" style="padding:40px;color:var(--text-muted);">No complaints yet. <a href="<?= APP_URL ?>/complaints/submit.php" style="color:var(--gold-light);">Submit one!</a></td></tr>
                <?php else: foreach($list as $c): ?>
                <tr>
                    <td><span class="complaint-id">#<?= $c['complaint_id'] ?></span></td>
                    <td><?= clean($c['category']) ?></td>
                    <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= clean($c['description']) ?></td>
                    <td style="font-family:var(--font-mono);font-size:12px;"><?= $c['submitted_date'] ?></td>
                    <td><?= statusBadge($c['status']) ?></td>
                    <td><a href="<?= APP_URL ?>/complaints/view.php?id=<?= $c['complaint_id'] ?>" class="btn btn-secondary btn-sm"><i class="fas fa-eye"></i> View</a></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <div class="quick-actions">
        <a href="<?= APP_URL ?>/complaints/submit.php" class="action-card">
            <i class="fas fa-plus-circle"></i><span>Submit Complaint</span>
        </a>
        <a href="<?= APP_URL ?>/complaints/track.php" class="action-card">
            <i class="fas fa-search"></i><span>Track Complaints</span>
        </a>
        <a href="<?= APP_URL ?>/complaints/survey.php" class="action-card">
            <i class="fas fa-poll"></i><span>Fill Survey</span>
        </a>
    </div>
    <?php endif; ?>

    <!-- ═══ OFFICER ═══════════════════════════════════════════ -->
    <?php if(isOfficer()): ?>
    <div class="stats-grid">
        <div class="stat-card blue">
            <div class="stat-icon-wrap"><i class="fas fa-tasks"></i></div>
            <div class="stat-info"><h3><?= $ac ?></h3><p>Assigned</p></div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon-wrap"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info"><h3><?= $orc ?></h3><p>Resolved</p></div>
        </div>
        <div class="stat-card gold">
            <div class="stat-icon-wrap"><i class="fas fa-clipboard-list"></i></div>
            <div class="stat-info"><h3><?= $owc ?></h3><p>Active Work Orders</p></div>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-clipboard-check"></i> Assigned Complaints</h3>
            <a href="<?= APP_URL ?>/officer/assigned_complaints.php" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <table class="table">
            <thead><tr><th>#ID</th><th>Category</th><th>Description</th><th>Assigned</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
                <?php if(empty($list)): ?>
                <tr><td colspan="6" class="text-center" style="padding:40px;color:var(--text-muted);">No complaints assigned yet.</td></tr>
                <?php else: foreach($list as $c): ?>
                <tr>
                    <td><span class="complaint-id">#<?= $c['complaint_id'] ?></span></td>
                    <td><?= clean($c['category']) ?></td>
                    <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= clean($c['description']) ?></td>
                    <td style="font-family:var(--font-mono);font-size:12px;"><?= $c['assigned_date'] ?></td>
                    <td><?= statusBadge($c['status']) ?></td>
                    <td><a href="<?= APP_URL ?>/officer/update_status.php?id=<?= $c['complaint_id'] ?>" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i> Update</a></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <div class="quick-actions">
        <a href="<?= APP_URL ?>/officer/assigned_complaints.php" class="action-card">
            <i class="fas fa-clipboard-list"></i><span>My Tasks</span>
        </a>
        <a href="<?= APP_URL ?>/officer/work_orders.php" class="action-card">
            <i class="fas fa-file-alt"></i><span>Work Orders</span>
        </a>
    </div>
    <?php endif; ?>

    <!-- ═══ ADMIN ═════════════════════════════════════════════ -->
    <?php if(isAdmin()): ?>
    <div class="stats-grid">
        <div class="stat-card blue">
            <div class="stat-icon-wrap"><i class="fas fa-clipboard-list"></i></div>
            <div class="stat-info"><h3><?= $tc ?></h3><p>Total Complaints</p></div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon-wrap"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info"><h3><?= $rc ?></h3><p>Resolved</p></div>
        </div>
        <div class="stat-card orange">
            <div class="stat-icon-wrap"><i class="fas fa-clock"></i></div>
            <div class="stat-info"><h3><?= $pc ?></h3><p>Pending</p></div>
        </div>
        <div class="stat-card red">
            <div class="stat-icon-wrap"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-info"><h3><?= $ec ?></h3><p>Escalated</p></div>
        </div>
        <div class="stat-card purple">
            <div class="stat-icon-wrap"><i class="fas fa-users"></i></div>
            <div class="stat-info"><h3><?= $citizens ?></h3><p>Citizens</p></div>
        </div>
        <div class="stat-card teal">
            <div class="stat-icon-wrap"><i class="fas fa-user-tie"></i></div>
            <div class="stat-info"><h3><?= $officers ?></h3><p>Officers</p></div>
        </div>
        <div class="stat-card red">
            <div class="stat-icon-wrap"><i class="fas fa-balance-scale"></i></div>
            <div class="stat-info"><h3><?= $pendingAppeals ?></h3><p>Pending Appeals</p></div>
        </div>
        <div class="stat-card orange">
            <div class="stat-icon-wrap"><i class="fas fa-gavel"></i></div>
            <div class="stat-info"><h3><?= $unpaidFines ?></h3><p>Unpaid Fines</p></div>
        </div>
        <div class="stat-card gold">
            <div class="stat-icon-wrap"><i class="fas fa-hard-hat"></i></div>
            <div class="stat-info"><h3><?= $activeWO ?></h3><p>Active Work Orders</p></div>
        </div>
    </div>

    <div class="grid-2">
        <!-- Dept Performance -->
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-building"></i> Department Performance</h3></div>
            <table class="table">
                <thead><tr><th>Department</th><th>Total</th><th>Resolved</th><th>Pending</th><th>Escalated</th></tr></thead>
                <tbody>
                    <?php foreach($deptStats as $d): ?>
                    <tr>
                        <td><strong><?= clean($d['department']) ?></strong></td>
                        <td><?= $d['total_complaints'] ?></td>
                        <td style="color:var(--success)"><?= $d['resolved'] ?></td>
                        <td style="color:var(--warning)"><?= $d['pending'] ?></td>
                        <td style="color:var(--danger)"><?= $d['escalated'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Recent Complaints -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list"></i> Recent Complaints</h3>
                <a href="<?= APP_URL ?>/admin/manage_complaint.php" class="btn btn-secondary btn-sm">View All</a>
            </div>
            <table class="table">
                <thead><tr><th>#ID</th><th>Citizen</th><th>Category</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach($recent as $c): ?>
                    <tr>
                        <td><a href="<?= APP_URL ?>/complaints/view.php?id=<?= $c['complaint_id'] ?>" style="color:var(--gold-light);font-weight:700;text-decoration:none;">#<?= $c['complaint_id'] ?></a></td>
                        <td><?= clean($c['citizen_name']) ?></td>
                        <td><?= clean($c['category']) ?></td>
                        <td><?= statusBadge($c['status']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Quick Action Cards — all 6 new features -->
    <div class="card" style="margin-bottom:26px;">
        <div class="card-header"><h3><i class="fas fa-bolt"></i> Quick Actions</h3></div>
        <div style="padding:22px;">
            <div class="quick-actions">
                <a href="<?= APP_URL ?>/admin/manage_complaint.php" class="action-card">
                    <i class="fas fa-cogs"></i><span>Manage Complaints</span>
                </a>
                <a href="<?= APP_URL ?>/admin/announcements.php" class="action-card">
                    <i class="fas fa-bullhorn"></i><span>Announcements</span>
                </a>
                <a href="<?= APP_URL ?>/admin/manage_appeals.php" class="action-card">
                    <i class="fas fa-balance-scale"></i><span>Appeals <?php if($pendingAppeals>0): ?><span style="background:var(--danger);color:#fff;border-radius:10px;padding:1px 7px;font-size:11px;margin-left:4px;"><?= $pendingAppeals ?></span><?php endif; ?></span>
                </a>
                <a href="<?= APP_URL ?>/admin/fines.php" class="action-card">
                    <i class="fas fa-gavel"></i><span>Fines <?php if($unpaidFines>0): ?><span style="background:var(--warning);color:#000;border-radius:10px;padding:1px 7px;font-size:11px;margin-left:4px;"><?= $unpaidFines ?></span><?php endif; ?></span>
                </a>
                <a href="<?= APP_URL ?>/admin/surveys.php" class="action-card">
                    <i class="fas fa-poll"></i><span>Surveys</span>
                </a>
                <a href="<?= APP_URL ?>/admin/budget.php" class="action-card">
                    <i class="fas fa-wallet"></i><span>Budget</span>
                </a>
                <a href="<?= APP_URL ?>/admin/contractors.php" class="action-card">
                    <i class="fas fa-hard-hat"></i><span>Contractors</span>
                </a>
                <a href="<?= APP_URL ?>/admin/reports.php" class="action-card">
                    <i class="fas fa-chart-bar"></i><span>Reports</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Latest Announcements -->
    <?php if(!empty($latestAnn)): ?>
    <div class="card" style="margin-bottom:26px;">
        <div class="card-header">
            <h3><i class="fas fa-bullhorn"></i> Latest Announcements</h3>
            <a href="<?= APP_URL ?>/admin/announcements.php" class="btn btn-secondary btn-sm">Manage</a>
        </div>
        <?php foreach($latestAnn as $ann): ?>
        <div style="padding:13px 22px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;gap:14px;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:34px;height:34px;background:var(--gold-dim);border:1px solid var(--border-accent);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-bullhorn" style="color:var(--gold);font-size:14px;"></i>
                </div>
                <div>
                    <p style="color:var(--text-primary);font-weight:600;font-size:14px;"><?= clean($ann['title']) ?></p>
                    <p style="color:var(--text-muted);font-size:12px;"><?= clean($ann['dept']) ?></p>
                </div>
            </div>
            <span style="font-size:11px;color:var(--text-muted);font-family:var(--font-mono);white-space:nowrap;"><?= $ann['published_date'] ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- RBAC -->
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-shield-alt"></i> Role-Based Access Control (RBAC)</h3></div>
        <div style="padding:24px;">
            <p style="color:var(--text-secondary);font-size:13.5px;margin-bottom:24px;line-height:1.7;">
                This system implements strict role-based access control. Each role has specific permissions — users cannot access pages or data outside their role.
            </p>
            <div class="access-grid">
                <div class="access-card">
                    <div class="access-card-header">
                        <div class="access-role-icon" style="background:rgba(99,102,241,0.1);color:#818cf8"><i class="fas fa-user"></i></div>
                        <div><h3>Citizen</h3><div class="role-label">Public User</div></div>
                    </div>
                    <div class="access-item"><i class="fas fa-check allow"></i> Register &amp; Login</div>
                    <div class="access-item"><i class="fas fa-check allow"></i> Submit &amp; track complaints</div>
                    <div class="access-item"><i class="fas fa-check allow"></i> Upload evidence</div>
                    <div class="access-item"><i class="fas fa-check allow"></i> Submit feedback &amp; ratings</div>
                    <div class="access-item"><i class="fas fa-check allow"></i> File appeals on resolved complaints</div>
                    <div class="access-item"><i class="fas fa-check allow"></i> Complete satisfaction surveys</div>
                    <div class="access-item"><i class="fas fa-times deny"></i> Cannot access officer/admin pages</div>
                </div>
                <div class="access-card">
                    <div class="access-card-header">
                        <div class="access-role-icon" style="background:rgba(16,185,129,0.1);color:var(--success)"><i class="fas fa-shield-alt"></i></div>
                        <div><h3>Govt Officer</h3><div class="role-label">Department Staff</div></div>
                    </div>
                    <div class="access-item"><i class="fas fa-check allow"></i> View assigned complaints</div>
                    <div class="access-item"><i class="fas fa-check allow"></i> Update complaint status</div>
                    <div class="access-item"><i class="fas fa-check allow"></i> Submit inspection reports</div>
                    <div class="access-item"><i class="fas fa-check allow"></i> Record resolutions</div>
                    <div class="access-item"><i class="fas fa-check allow"></i> Create &amp; manage work orders</div>
                    <div class="access-item"><i class="fas fa-times deny"></i> Cannot assign officers</div>
                    <div class="access-item"><i class="fas fa-times deny"></i> Cannot access admin panel</div>
                </div>
                <div class="access-card">
                    <div class="access-card-header">
                        <div class="access-role-icon" style="background:rgba(245,158,11,0.1);color:var(--warning)"><i class="fas fa-crown"></i></div>
                        <div><h3>Admin</h3><div class="role-label">Full Access</div></div>
                    </div>
                    <div class="access-item"><i class="fas fa-check allow"></i> Full system access</div>
                    <div class="access-item"><i class="fas fa-check allow"></i> Assign officers to complaints</div>
                    <div class="access-item"><i class="fas fa-check allow"></i> Publish announcements</div>
                    <div class="access-item"><i class="fas fa-check allow"></i> Review &amp; decide appeals</div>
                    <div class="access-item"><i class="fas fa-check allow"></i> Issue &amp; track fines</div>
                    <div class="access-item"><i class="fas fa-check allow"></i> Manage budget allocations</div>
                    <div class="access-item"><i class="fas fa-check allow"></i> Manage contractors &amp; surveys</div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>