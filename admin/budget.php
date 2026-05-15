<?php
// ═══════════════════════════════════════════════════════════
//  BUDGET ALLOCATION — Admin manages dept budgets
//  Path: admin/budget.php
// ═══════════════════════════════════════════════════════════
require_once '../config/config.php';
requireAdmin();

// ── Add allocation ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_budget'])) {
    $dept_id  = (int)($_POST['department_id'] ?? 0);
    $amount   = (float)($_POST['amount'] ?? 0);
    $fy       = clean($_POST['fiscal_year'] ?? '');

    if (!$dept_id || $amount <= 0 || empty($fy)) {
        setFlash('error', 'Please fill all required fields.');
    } else {
        $dup = $pdo->prepare("SELECT budget_id FROM Budget_Allocation WHERE department_id=? AND fiscal_year=?");
        $dup->execute([$dept_id, $fy]);
        if ($dup->fetch()) {
            setFlash('warning', 'Budget already allocated for this department and fiscal year. Record additional spending below instead.');
        } else {
            $pdo->prepare("INSERT INTO Budget_Allocation (amount,spent,fiscal_year,department_id) VALUES (?,0,?,?)")
                ->execute([$amount, $fy, $dept_id]);
            setFlash('success', 'Budget allocated successfully.');
            redirect(APP_URL . '/admin/budget.php');
        }
    }
}

// ── Record spending ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_spending'])) {
    $bid    = (int)$_POST['budget_id'];
    $spent  = (float)$_POST['spent_amount'];
    if ($bid && $spent > 0) {
        // Don't exceed budget
        $brow = $pdo->prepare("SELECT amount, spent FROM Budget_Allocation WHERE budget_id=?");
        $brow->execute([$bid]);
        $brow = $brow->fetch();
        if (($brow['spent'] + $spent) > $brow['amount']) {
            setFlash('error', 'Expenditure exceeds allocated budget. Remaining: PKR ' . number_format($brow['amount']-$brow['spent'], 2));
        } else {
            $pdo->prepare("UPDATE Budget_Allocation SET spent = spent + ? WHERE budget_id = ?")->execute([$spent, $bid]);
            setFlash('success', 'Expenditure of PKR ' . number_format($spent, 2) . ' recorded.');
            redirect(APP_URL . '/admin/budget.php');
        }
    }
}

// ── Fetch ────────────────────────────────────────────────────
$fyFilter = clean($_GET['fy'] ?? '');
$sql = "SELECT b.*, d.name AS dept_name FROM Budget_Allocation b JOIN Department d ON b.department_id=d.department_id";
if ($fyFilter) $sql .= " WHERE b.fiscal_year=" . $pdo->quote($fyFilter);
$sql .= " ORDER BY b.fiscal_year DESC, d.name";
$budgets = $pdo->query($sql)->fetchAll();

$departments = $pdo->query("SELECT * FROM Department ORDER BY name")->fetchAll();
$allFY       = $pdo->query("SELECT DISTINCT fiscal_year FROM Budget_Allocation ORDER BY fiscal_year DESC")->fetchAll(PDO::FETCH_COLUMN);

$totalBudget  = array_sum(array_column($budgets, 'amount'));
$totalSpent   = array_sum(array_column($budgets, 'spent'));
$totalBalance = $totalBudget - $totalSpent;
$overallPct   = $totalBudget > 0 ? round($totalSpent / $totalBudget * 100) : 0;

// Fiscal year options for form
$cy = (int)date('Y');
$fyOptions = [];
for ($y = $cy - 1; $y <= $cy + 1; $y++) {
    $fyOptions[] = $y . '-' . substr($y+1, -2);
}

include '../includes/header.php';
?>
<div class="container">
    <?php showFlash(); ?>

    <div class="page-header">
        <div class="page-header-left">
            <div class="breadcrumb"><i class="fas fa-home"></i><span>/</span><span>Admin</span><span>/</span><span>Budget</span></div>
            <h2><i class="fas fa-wallet"></i> Budget Allocation</h2>
            <p>Allocate and track departmental budgets per fiscal year</p>
        </div>
    </div>

    <div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:32px;">
        <div class="stat-card gold">
            <div class="stat-icon-wrap"><i class="fas fa-coins"></i></div>
            <div class="stat-info"><h3><?= number_format($totalBudget/1000000, 1) ?>M</h3><p>Total Budget (PKR)</p></div>
        </div>
        <div class="stat-card red">
            <div class="stat-icon-wrap"><i class="fas fa-chart-line"></i></div>
            <div class="stat-info"><h3><?= number_format($totalSpent/1000000, 1) ?>M</h3><p>Spent (PKR)</p></div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon-wrap"><i class="fas fa-piggy-bank"></i></div>
            <div class="stat-info"><h3><?= number_format($totalBalance/1000000, 1) ?>M</h3><p>Remaining (PKR)</p></div>
        </div>
        <div class="stat-card blue">
            <div class="stat-icon-wrap"><i class="fas fa-percent"></i></div>
            <div class="stat-info"><h3><?= $overallPct ?>%</h3><p>Utilised</p></div>
        </div>
    </div>

    <div class="grid-2">
        <!-- Allocate Form -->
        <div class="card card-gold">
            <div class="card-header"><h3><i class="fas fa-plus-circle"></i> Allocate New Budget</h3></div>
            <form method="POST" class="form">
                <div class="form-section">
                    <div class="form-group">
                        <label>Department *</label>
                        <select name="department_id" required>
                            <option value="">-- Select Department --</option>
                            <?php foreach($departments as $d): ?>
                            <option value="<?= $d['department_id'] ?>"><?= clean($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Amount (PKR) *</label>
                            <input type="number" name="amount" min="10000" step="1000" placeholder="e.g. 5000000" required>
                        </div>
                        <div class="form-group">
                            <label>Fiscal Year *</label>
                            <select name="fiscal_year" required>
                                <option value="">-- Select --</option>
                                <?php foreach($fyOptions as $fy): ?>
                                <option value="<?= $fy ?>" <?= $fy === $cy.'-'.substr($cy+1,-2) ? 'selected' : '' ?>><?= $fy ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" name="add_budget" class="btn btn-primary">
                        <i class="fas fa-wallet"></i> Allocate Budget
                    </button>
                </div>
            </form>
        </div>

        <!-- Record Spending -->
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-receipt"></i> Record Expenditure</h3></div>
            <form method="POST" class="form">
                <div class="form-section">
                    <div class="form-group">
                        <label>Select Budget Allocation *</label>
                        <?php if (empty($budgets)): ?>
                        <div style="padding:12px;background:var(--bg-elevated);border:1px solid var(--border);border-radius:var(--r-sm);color:var(--text-muted);font-size:14px;">
                            No budget allocations yet.
                        </div>
                        <?php else: ?>
                        <select name="budget_id" required>
                            <option value="">-- Select Allocation --</option>
                            <?php foreach($budgets as $b):
                                $rem = $b['amount'] - $b['spent'];
                            ?>
                            <option value="<?= $b['budget_id'] ?>">
                                <?= clean($b['dept_name']) ?> (<?= $b['fiscal_year'] ?>) — PKR <?= number_format($rem) ?> remaining
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>Amount Spent (PKR) *</label>
                        <input type="number" name="spent_amount" min="100" step="100" placeholder="e.g. 250000" required>
                        <small>Cannot exceed remaining balance for the selected allocation.</small>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" name="record_spending" class="btn btn-secondary" <?= empty($budgets) ? 'disabled' : '' ?>>
                        <i class="fas fa-minus-circle"></i> Record Spending
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Fiscal Year Filter -->
    <?php if (!empty($allFY)): ?>
    <div class="tab-bar">
        <a href="?" class="tab <?= !$fyFilter ? 'active' : '' ?>">All Years</a>
        <?php foreach($allFY as $fy): ?>
        <a href="?fy=<?= urlencode($fy) ?>" class="tab <?= $fyFilter===$fy ? 'active' : '' ?>"><?= clean($fy) ?></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Budget Table -->
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-table"></i> Budget Allocations</h3></div>
        <table class="table">
            <thead>
                <tr><th>Department</th><th>Fiscal Year</th><th>Allocated (PKR)</th><th>Spent (PKR)</th><th>Remaining (PKR)</th><th>Utilisation</th></tr>
            </thead>
            <tbody>
                <?php if(empty($budgets)): ?>
                <tr><td colspan="6" class="text-center" style="padding:40px;color:var(--text-muted);">No budget allocations yet. Create one above.</td></tr>
                <?php else: foreach($budgets as $b):
                    $rem = $b['amount'] - $b['spent'];
                    $pct = $b['amount'] > 0 ? min(100, round($b['spent'] / $b['amount'] * 100)) : 0;
                    $barClr = $pct >= 90 ? 'var(--danger)' : ($pct >= 70 ? 'var(--warning)' : 'var(--gold)');
                ?>
                <tr>
                    <td><strong style="color:var(--text-primary);"><?= clean($b['dept_name']) ?></strong></td>
                    <td style="font-family:var(--font-mono);color:var(--text-secondary);"><?= clean($b['fiscal_year']) ?></td>
                    <td style="color:var(--gold-light);font-weight:700;font-family:var(--font-mono);"><?= number_format($b['amount']) ?></td>
                    <td style="color:var(--danger);font-family:var(--font-mono);"><?= number_format($b['spent']) ?></td>
                    <td style="color:<?= $rem <= 0 ? 'var(--danger)' : 'var(--success)' ?>;font-weight:700;font-family:var(--font-mono);"><?= number_format($rem) ?></td>
                    <td style="min-width:180px;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div class="progress-bar" style="flex:1;height:7px;">
                                <div style="background:<?= $barClr ?>;height:100%;width:<?= $pct ?>%;border-radius:4px;transition:width 0.8s ease;"></div>
                            </div>
                            <span style="font-size:12px;color:var(--text-muted);font-family:var(--font-mono);min-width:38px;text-align:right;"><?= $pct ?>%</span>
                        </div>
                        <?php if($pct >= 90): ?>
                        <p style="font-size:11px;color:var(--danger);margin-top:3px;"><i class="fas fa-exclamation-triangle"></i> <?= $pct === 100 ? 'Exhausted' : 'Near limit' ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include '../includes/footer.php'; ?>