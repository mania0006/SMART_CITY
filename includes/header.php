<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart City Complaints</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-container">
        <a href="<?= APP_URL ?>/dashboard.php" class="nav-brand">
            <div class="nav-brand-icon"><i class="fas fa-city"></i></div>
            <span>SmartCity</span>
            <span style="color:var(--text-muted);font-weight:400;font-size:13px;">Complaints</span>
        </a>
        <ul class="nav-links">
            <li><a href="<?= APP_URL ?>/dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>

            <?php if (isCitizen()): ?>
                <li><a href="<?= APP_URL ?>/complaints/submit.php"><i class="fas fa-plus-circle"></i> Submit</a></li>
                <li><a href="<?= APP_URL ?>/complaints/track.php"><i class="fas fa-search"></i> Track</a></li>
                <li><a href="<?= APP_URL ?>/complaints/survey.php"><i class="fas fa-poll"></i> Survey</a></li>
            <?php endif; ?>

            <?php if (isOfficer()): ?>
                <li><a href="<?= APP_URL ?>/officer/assigned_complaints.php"><i class="fas fa-clipboard-list"></i> My Tasks</a></li>
                <li><a href="<?= APP_URL ?>/officer/update_status.php"><i class="fas fa-edit"></i> Update</a></li>
                <li><a href="<?= APP_URL ?>/officer/work_orders.php"><i class="fas fa-file-alt"></i> Work Orders</a></li>
            <?php endif; ?>

            <?php if (isAdmin()): ?>
                <li><a href="<?= APP_URL ?>/admin/manage_complaint.php"><i class="fas fa-cogs"></i> Complaints</a></li>
                <li><a href="<?= APP_URL ?>/admin/manage_user.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="<?= APP_URL ?>/admin/reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="<?= APP_URL ?>/admin/announcements.php"><i class="fas fa-bullhorn"></i> Notices</a></li>

                <!-- Admin Dropdown for extra features -->
                <li style="position:relative;" id="moreMenu">
                    <a href="#" onclick="toggleMore(event)" style="display:flex;align-items:center;gap:6px;">
                        <i class="fas fa-ellipsis-h"></i> More
                        <i class="fas fa-chevron-down" style="font-size:10px;opacity:0.6;"></i>
                    </a>
                    <div id="moreDropdown" style="
                        display:none;position:absolute;top:calc(100% + 8px);right:0;
                        background:var(--bg-elevated);border:1px solid var(--border-hover);
                        border-radius:var(--r);min-width:200px;
                        box-shadow:var(--shadow-lg);z-index:999;overflow:hidden;">
                        <a href="<?= APP_URL ?>/admin/manage_appeals.php"
                           style="display:flex;align-items:center;gap:10px;padding:12px 18px;color:var(--text-secondary);text-decoration:none;font-size:14px;transition:all 0.15s;border-bottom:1px solid var(--border);"
                           onmouseover="this.style.background='var(--gold-trace)';this.style.color='var(--gold-light)'"
                           onmouseout="this.style.background='';this.style.color='var(--text-secondary)'">
                            <i class="fas fa-balance-scale" style="color:var(--gold);width:16px;"></i> Appeals
                        </a>
                        <a href="<?= APP_URL ?>/admin/fines.php"
                           style="display:flex;align-items:center;gap:10px;padding:12px 18px;color:var(--text-secondary);text-decoration:none;font-size:14px;transition:all 0.15s;border-bottom:1px solid var(--border);"
                           onmouseover="this.style.background='var(--gold-trace)';this.style.color='var(--gold-light)'"
                           onmouseout="this.style.background='';this.style.color='var(--text-secondary)'">
                            <i class="fas fa-gavel" style="color:var(--gold);width:16px;"></i> Fines
                        </a>
                        <a href="<?= APP_URL ?>/admin/surveys.php"
                           style="display:flex;align-items:center;gap:10px;padding:12px 18px;color:var(--text-secondary);text-decoration:none;font-size:14px;transition:all 0.15s;border-bottom:1px solid var(--border);"
                           onmouseover="this.style.background='var(--gold-trace)';this.style.color='var(--gold-light)'"
                           onmouseout="this.style.background='';this.style.color='var(--text-secondary)'">
                            <i class="fas fa-poll" style="color:var(--gold);width:16px;"></i> Surveys
                        </a>
                        <a href="<?= APP_URL ?>/admin/budget.php"
                           style="display:flex;align-items:center;gap:10px;padding:12px 18px;color:var(--text-secondary);text-decoration:none;font-size:14px;transition:all 0.15s;border-bottom:1px solid var(--border);"
                           onmouseover="this.style.background='var(--gold-trace)';this.style.color='var(--gold-light)'"
                           onmouseout="this.style.background='';this.style.color='var(--text-secondary)'">
                            <i class="fas fa-wallet" style="color:var(--gold);width:16px;"></i> Budget
                        </a>
                        <a href="<?= APP_URL ?>/admin/contractors.php"
                           style="display:flex;align-items:center;gap:10px;padding:12px 18px;color:var(--text-secondary);text-decoration:none;font-size:14px;transition:all 0.15s;"
                           onmouseover="this.style.background='var(--gold-trace)';this.style.color='var(--gold-light)'"
                           onmouseout="this.style.background='';this.style.color='var(--text-secondary)'">
                            <i class="fas fa-hard-hat" style="color:var(--gold);width:16px;"></i> Contractors
                        </a>
                    </div>
                </li>
            <?php endif; ?>

            <?php if (isLoggedIn()): ?>
                <li>
                    <div class="nav-user">
                        <div class="nav-user-dot"></div>
                        <?= clean(getUserName()) ?>
                        <span style="color:var(--text-muted);font-size:10px;text-transform:uppercase;letter-spacing:0.5px;"><?= $_SESSION['role'] ?? '' ?></span>
                    </div>
                </li>
                <li><a href="<?= APP_URL ?>/logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
<main class="main-content">

<script>
// ── Dropdown toggle ───────────────────────────────────────────
function toggleMore(e) {
    e.preventDefault();
    const dd = document.getElementById('moreDropdown');
    dd.style.display = dd.style.display === 'none' ? 'block' : 'none';
}
document.addEventListener('click', function(e) {
    const menu = document.getElementById('moreMenu');
    if (menu && !menu.contains(e.target)) {
        const dd = document.getElementById('moreDropdown');
        if (dd) dd.style.display = 'none';
    }
});

// ── FIX: data-confirm handler ─────────────────────────────────
// Yeh missing tha — isliye Delete/Submit buttons kaam nahi kar rahe the
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            const msg = form.getAttribute('data-confirm');
            if (msg && !confirm(msg)) {
                e.preventDefault();
            }
        });
    });
});
</script>