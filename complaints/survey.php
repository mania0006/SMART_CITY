<?php
require_once '../config/config.php';
requireCitizen();

$citizen_id = getUserId();

$surveyQuestions = [
    "How satisfied are you with the overall civic complaint resolution service?",
    "How would you rate the response time of government departments?",
    "Do you feel your complaints are taken seriously by the authorities?",
    "How easy was it to submit and track your complaint online?",
    "Would you recommend this platform to other citizens in your area?",
];

$answered = $pdo->prepare("SELECT question FROM Survey WHERE citizen_id=?");
$answered->execute([$citizen_id]);
$answeredList = array_column($answered->fetchAll(), 'question');
$pending      = array_diff($surveyQuestions, $answeredList);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_survey'])) {
    // FIX: trim() use karo, clean() nahi — warna in_array match fail hota hai
    $question = trim($_POST['question'] ?? '');
    $response = trim($_POST['response'] ?? '');

    if (empty($response)) {
        setFlash('error', 'Please provide your response.');
    } elseif (!in_array($question, $surveyQuestions)) {
        setFlash('error', 'Invalid question received.');
    } elseif (in_array($question, $answeredList)) {
        setFlash('warning', 'Already answered this question.');
    } else {
        $pdo->prepare("INSERT INTO Survey (question,response,response_date,citizen_id) VALUES (?,?,CURDATE(),?)")
            ->execute([$question, $response, $citizen_id]);
        setFlash('success', 'Thank you for your feedback!');
        redirect(APP_URL . '/complaints/survey.php');
    }
}

$answered->execute([$citizen_id]);
$answeredList = array_column($answered->fetchAll(), 'question');
$pending      = array_diff($surveyQuestions, $answeredList);

$myResponses = $pdo->prepare("SELECT * FROM Survey WHERE citizen_id=? ORDER BY response_date DESC");
$myResponses->execute([$citizen_id]);
$myList = $myResponses->fetchAll();

include '../includes/header.php';
?>
<div class="container">
    <?php showFlash(); ?>

    <div class="page-header">
        <div class="page-header-left">
            <div class="breadcrumb"><i class="fas fa-home"></i><span>/</span><span>Surveys</span></div>
            <h2><i class="fas fa-poll"></i> Citizen Satisfaction Survey</h2>
            <p>Share your experience to help us improve civic services</p>
        </div>
    </div>

    <?php if (!empty($pending)): ?>
    <?php $currentQ = reset($pending); ?>
    <div class="card card-gold" style="margin-bottom:26px;">
        <div class="card-header">
            <h3><i class="fas fa-clipboard-check"></i> Pending Survey</h3>
            <span style="font-size:13px;color:var(--text-muted);"><?= count($pending) ?> question(s) remaining</span>
        </div>
        <form method="POST" class="form">
            <div class="form-section">
                <!-- FIX: htmlspecialchars with ENT_QUOTES — clean() nahi -->
                <input type="hidden" name="question" value="<?= htmlspecialchars($currentQ, ENT_QUOTES, 'UTF-8') ?>">
                <div style="background:var(--gold-trace);border:1px solid var(--border-accent);border-radius:var(--r-sm);padding:18px 22px;margin-bottom:22px;">
                    <p style="font-size:12px;font-weight:700;color:var(--gold);text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;">Question</p>
                    <p style="font-size:17px;color:var(--text-primary);line-height:1.65;"><?= clean($currentQ) ?></p>
                </div>
                <div class="form-group">
                    <label>Your Response *</label>
                    <textarea name="response" rows="5" placeholder="Share your honest feedback here..." required></textarea>
                </div>
                <div style="margin-bottom:16px;">
                    <p style="font-size:12px;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:10px;font-weight:700;">Quick Select</p>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <?php foreach(['Very Satisfied','Satisfied','Neutral','Dissatisfied','Very Dissatisfied'] as $q): ?>
                        <button type="button" onclick="document.querySelector('textarea[name=response]').value='<?= $q ?>'"
                            style="padding:6px 14px;background:var(--bg-elevated);border:1px solid var(--border-hover);border-radius:20px;color:var(--text-secondary);font-family:var(--font-body);font-size:13px;cursor:pointer;">
                            <?= $q ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <a href="<?= APP_URL ?>/dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Skip for Now</a>
                <button type="submit" name="submit_survey" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Submit Response
                </button>
            </div>
        </form>
    </div>

    <?php
    $total = count($surveyQuestions);
    $completed = count($answeredList);
    $progressP = $total > 0 ? round($completed / $total * 100) : 0;
    ?>
    <div class="card" style="margin-bottom:26px;">
        <div style="padding:20px 26px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:10px;">
                <span style="color:var(--text-secondary);font-size:14px;font-weight:600;">Survey Progress</span>
                <span style="color:var(--gold-light);font-family:var(--font-mono);font-size:14px;"><?= $completed ?> / <?= $total ?> completed</span>
            </div>
            <div class="progress-bar" style="height:8px;"><div class="progress-fill" style="width:<?= $progressP ?>%;"></div></div>
        </div>
    </div>

    <?php else: ?>
    <div class="card" style="margin-bottom:26px;border-left:3px solid var(--success);">
        <div style="padding:30px;text-align:center;">
            <i class="fas fa-check-circle" style="font-size:48px;color:var(--success);margin-bottom:14px;display:block;"></i>
            <h3 style="font-family:var(--font-display);font-size:24px;color:var(--text-primary);margin-bottom:8px;">All surveys completed!</h3>
            <p style="color:var(--text-secondary);font-size:15px;">Thank you for helping us improve city services.</p>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($myList)): ?>
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-history"></i> My Survey Responses</h3></div>
        <table class="table">
            <thead><tr><th>Question</th><th>My Response</th><th>Date</th></tr></thead>
            <tbody>
                <?php foreach($myList as $r): ?>
                <tr>
                    <td style="max-width:280px;color:var(--text-secondary);font-size:14px;"><?= clean($r['question']) ?></td>
                    <td style="color:var(--text-primary);font-size:14px;"><?= clean($r['response']) ?></td>
                    <td style="font-family:var(--font-mono);font-size:12px;color:var(--text-muted);"><?= $r['response_date'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>