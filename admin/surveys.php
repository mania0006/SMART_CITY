<?php
// ═══════════════════════════════════════════════════════════
//  SURVEYS — Admin creates periodic surveys, citizens respond
//  Path: admin/surveys.php
// ═══════════════════════════════════════════════════════════
require_once '../config/config.php';
requireAdmin();

// ── NOTE: Survey table only stores citizen responses.
//    We'll store survey questions as a simple text field.
//    Schema: survey_id, question, response, response_date, citizen_id
//    We treat each unique "question" value as a survey campaign.

// ── Fetch all distinct survey questions (campaigns) ──────────
$campaigns = $pdo->query("
    SELECT question,
           COUNT(*)    AS total_responses,
           MIN(response_date) AS first_response,
           MAX(response_date) AS last_response
    FROM Survey
    GROUP BY question
    ORDER BY last_response DESC
")->fetchAll();

// ── Fetch all responses for a selected campaign ──────────────
$selectedQ   = clean($_GET['q'] ?? '');
$responses   = [];
if ($selectedQ) {
    $stmt = $pdo->prepare("
        SELECT s.*, ci.name AS citizen_name, ci.email AS citizen_email
        FROM Survey s JOIN Citizen ci ON s.citizen_id = ci.citizen_id
        WHERE s.question = ?
        ORDER BY s.response_date DESC
    ");
    $stmt->execute([$selectedQ]);
    $responses = $stmt->fetchAll();
}

$totalResponses = $pdo->query("SELECT COUNT(*) FROM Survey")->fetchColumn();
$totalCitizens  = $pdo->query("SELECT COUNT(DISTINCT citizen_id) FROM Survey")->fetchColumn();

include '../includes/header.php';
?>
<div class="container">
    <?php showFlash(); ?>

    <div class="page-header">
        <div class="page-header-left">
            <div class="breadcrumb"><i class="fas fa-home"></i><span>/</span><span>Admin</span><span>/</span><span>Surveys</span></div>
            <h2><i class="fas fa-poll"></i> Citizen Surveys</h2>
            <p>View and analyse satisfaction surveys submitted by citizens</p>
        </div>
    </div>

    <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);max-width:620px;margin-bottom:32px;">
        <div class="stat-card blue">
            <div class="stat-icon-wrap"><i class="fas fa-clipboard-list"></i></div>
            <div class="stat-info"><h3><?= count($campaigns) ?></h3><p>Survey Campaigns</p></div>
        </div>
        <div class="stat-card gold">
            <div class="stat-icon-wrap"><i class="fas fa-comments"></i></div>
            <div class="stat-info"><h3><?= $totalResponses ?></h3><p>Total Responses</p></div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon-wrap"><i class="fas fa-users"></i></div>
            <div class="stat-info"><h3><?= $totalCitizens ?></h3><p>Respondents</p></div>
        </div>
    </div>

    <div class="grid-2">
        <!-- Campaigns List -->
        <div>
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-list"></i> Survey Campaigns</h3></div>
                <?php if (empty($campaigns)): ?>
                <div style="padding:40px;text-align:center;color:var(--text-muted);">
                    <i class="fas fa-poll" style="font-size:40px;margin-bottom:12px;display:block;opacity:0.4;"></i>
                    <p>No survey responses yet. Citizens submit surveys from their dashboard.</p>
                </div>
                <?php else: foreach($campaigns as $cam): ?>
                <div style="padding:16px 22px;border-bottom:1px solid var(--border);">
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
                        <div style="flex:1;min-width:0;">
                            <p style="font-weight:600;color:var(--text-primary);margin-bottom:6px;font-size:15px;"><?= clean($cam['question']) ?></p>
                            <div style="display:flex;gap:14px;font-size:12px;color:var(--text-muted);">
                                <span><i class="fas fa-comments" style="color:var(--gold);margin-right:4px;"></i><?= $cam['total_responses'] ?> responses</span>
                                <span><i class="fas fa-calendar" style="color:var(--gold);margin-right:4px;"></i><?= $cam['last_response'] ?></span>
                            </div>
                        </div>
                        <a href="?q=<?= urlencode($cam['question']) ?>" class="btn btn-secondary btn-sm <?= $selectedQ===$cam['question'] ? 'btn-primary' : '' ?>">
                            <i class="fas fa-eye"></i> View
                        </a>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- Responses Panel -->
        <div>
            <?php if ($selectedQ): ?>
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-comment-dots"></i> Responses</h3>
                    <span style="font-size:12px;color:var(--text-muted);"><?= count($responses) ?> total</span>
                </div>
                <div style="padding:6px 0;">
                    <div style="padding:14px 22px;background:var(--gold-trace);border-bottom:1px solid var(--border);">
                        <p style="font-size:12px;font-weight:700;color:var(--gold);text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;">Question</p>
                        <p style="color:var(--text-primary);font-size:15px;"><?= clean($selectedQ) ?></p>
                    </div>
                    <?php if(empty($responses)): ?>
                    <div style="padding:30px;text-align:center;color:var(--text-muted);">No responses yet.</div>
                    <?php else: foreach($responses as $r): ?>
                    <div style="padding:14px 22px;border-bottom:1px solid var(--border);">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;">
                            <div>
                                <p style="font-size:13px;color:var(--gold-light);font-weight:600;margin-bottom:4px;"><?= clean($r['citizen_name']) ?></p>
                                <p style="font-size:14px;color:var(--text-secondary);line-height:1.6;"><?= clean($r['response']) ?></p>
                            </div>
                            <span style="font-size:11px;color:var(--text-muted);font-family:var(--font-mono);white-space:nowrap;"><?= $r['response_date'] ?></span>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-mouse-pointer empty-icon"></i>
                <h3>Select a campaign</h3>
                <p>Click "View" on any survey campaign to see citizen responses.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>