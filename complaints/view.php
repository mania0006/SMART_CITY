<?php
// complaints/view.php — updated with Appeal, Fine, Work Order
require_once '../config/config.php';
requireLogin();

$complaint_id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT c.*, cat.type AS category, cat.priority_level, cat.avg_resolution_days,
           l.address AS loc_address, l.latitude, l.longitude,
           w.area_name AS ward, m.name AS municipality,
           d.name AS department, ci.name AS citizen_name
    FROM Complaint c
    JOIN Category cat  ON c.category_id  = cat.category_id
    JOIN Location l    ON c.location_id  = l.location_id
    JOIN Ward w        ON l.ward_id      = w.ward_id
    JOIN Municipality m ON w.municipality_id = m.municipality_id
    LEFT JOIN Department d ON c.assigned_dept_id = d.department_id
    JOIN Citizen ci    ON c.citizen_id   = ci.citizen_id
    WHERE c.complaint_id = ?
");
$stmt->execute([$complaint_id]);
$c = $stmt->fetch();

if (!$c) { setFlash('error', 'Complaint not found.'); redirect(APP_URL . '/dashboard.php'); }
if (isCitizen() && $c['citizen_id'] != getUserId()) redirect(APP_URL . '/dashboard.php');

// Status log
$logs = $pdo->prepare("SELECT sl.*, o.name AS officer_name FROM Status_Log sl LEFT JOIN Officer o ON sl.officer_id = o.officer_id WHERE sl.complaint_id = ? ORDER BY sl.changed_at ASC");
$logs->execute([$complaint_id]);
$statusLogs = $logs->fetchAll();

// Evidence
$media = $pdo->prepare("SELECT * FROM Evidence_Media WHERE complaint_id = ?");
$media->execute([$complaint_id]);
$mediaList = $media->fetchAll();

// Resolution
$res = $pdo->prepare("SELECT r.*, o.name AS officer_name FROM Resolution r JOIN Officer o ON r.officer_id = o.officer_id WHERE r.complaint_id = ?");
$res->execute([$complaint_id]);
$resolution = $res->fetch();

// Inspection
$insp = $pdo->prepare("SELECT i.*, o.name AS officer_name FROM Inspection i JOIN Officer o ON i.officer_id = o.officer_id WHERE i.complaint_id = ?");
$insp->execute([$complaint_id]);
$inspection = $insp->fetch();

// Feedback
$fb = $pdo->prepare("SELECT * FROM Feedback WHERE complaint_id = ?");
$fb->execute([$complaint_id]);
$feedback = $fb->fetch();

// Appeal
$ap = $pdo->prepare("SELECT * FROM Appeal WHERE complaint_id = ?");
$ap->execute([$complaint_id]);
$appeal = $ap->fetch();

// Fines
$fineStmt = $pdo->prepare("SELECT * FROM Fine WHERE complaint_id = ? ORDER BY issued_date DESC");
$fineStmt->execute([$complaint_id]);
$fines = $fineStmt->fetchAll();

// Work Orders
$woStmt = $pdo->prepare("
    SELECT wo.*, o.name AS officer_name, con.name AS contractor_name, con.specialization
    FROM Work_Order wo
    JOIN Officer o      ON wo.officer_id    = o.officer_id
    JOIN Contractor con ON wo.contractor_id = con.contractor_id
    WHERE wo.complaint_id = ?
    ORDER BY wo.issued_date DESC
");
$woStmt->execute([$complaint_id]);
$workOrders = $woStmt->fetchAll();

// Feedback POST
if (isCitizen() && $c['status'] === 'resolved' && !$feedback && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $rating   = (int)($_POST['rating'] ?? 0);
    $comments = clean($_POST['comments'] ?? '');
    if ($rating >= 1 && $rating <= 5) {
        $pdo->prepare("INSERT INTO Feedback (citizen_rating,comments,complaint_id,submitted_date) VALUES (?,?,?,CURDATE())")
            ->execute([$rating, $comments, $complaint_id]);
        setFlash('success', 'Thank you for your feedback!');
        redirect(APP_URL . '/complaints/view.php?id=' . $complaint_id);
    }
}

include '../includes/header.php';
?>

<div class="container">
    <?php showFlash(); ?>

    <div class="page-header">
        <div>
            <div class="breadcrumb">
                <i class="fas fa-home"></i><span>/</span>
                <?php if(isCitizen()): ?>
                    <a href="<?= APP_URL ?>/complaints/track.php" style="color:var(--text-muted);text-decoration:none;">My Complaints</a>
                <?php else: ?>
                    <a href="<?= APP_URL ?>/admin/manage_complaint.php" style="color:var(--text-muted);text-decoration:none;">Complaints</a>
                <?php endif; ?>
                <span>/</span><span>#<?= $complaint_id ?></span>
            </div>
            <h2><i class="fas fa-clipboard-check"></i> Complaint #<?= $complaint_id ?></h2>
            <p>Submitted on <?= $c['submitted_date'] ?> &nbsp;&middot;&nbsp; <strong style="color:var(--text-secondary);"><?= clean($c['citizen_name']) ?></strong></p>
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:10px;">
            <?= statusBadge($c['status']) ?>
            <?php if (isCitizen() && $c['status'] === 'resolved' && !$appeal): ?>
            <a href="<?= APP_URL ?>/complaints/appeal.php?id=<?= $complaint_id ?>" class="btn btn-secondary btn-sm">
                <i class="fas fa-balance-scale"></i> File Appeal
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid-2">
        <!-- LEFT -->
        <div>
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-info-circle"></i> Complaint Details</h3></div>
                <div class="detail-list">
                    <div class="detail-row"><span>Category</span><strong><?= clean($c['category']) ?></strong></div>
                    <div class="detail-row"><span>Priority</span>
                        <span class="badge priority-<?= strtolower($c['priority_level']) ?>"><?= clean($c['priority_level']) ?></span>
                    </div>
                    <div class="detail-row"><span>Department</span><strong><?= clean($c['department'] ?? 'Not assigned') ?></strong></div>
                    <div class="detail-row"><span>Citizen</span><strong><?= clean($c['citizen_name']) ?></strong></div>
                    <div class="detail-row"><span>Expected Resolution</span><strong><?= $c['avg_resolution_days'] ?> days</strong></div>
                </div>
                <div class="detail-description">
                    <strong>Description</strong>
                    <p><?= clean($c['description']) ?></p>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3><i class="fas fa-map-marker-alt"></i> Location</h3></div>
                <div class="detail-list">
                    <div class="detail-row"><span>Address</span><strong><?= clean($c['loc_address']) ?></strong></div>
                    <div class="detail-row"><span>Ward</span><strong><?= clean($c['ward']) ?></strong></div>
                    <div class="detail-row"><span>Municipality</span><strong><?= clean($c['municipality']) ?></strong></div>
                    <?php if ($c['latitude']): ?>
                    <div class="detail-row"><span>Coordinates</span><strong style="font-family:var(--font-mono);font-size:13px;"><?= $c['latitude'] ?>, <?= $c['longitude'] ?></strong></div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($mediaList)): ?>
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-paperclip"></i> Evidence</h3></div>
                <div class="media-grid" style="padding:16px;">
                    <?php foreach ($mediaList as $m): ?>
                        <?php if ($m['type'] === 'image'): ?>
                            <img src="<?= UPLOAD_URL . clean($m['file_path']) ?>" alt="Evidence" class="media-img">
                        <?php else: ?>
                            <video src="<?= UPLOAD_URL . clean($m['file_path']) ?>" controls class="media-img"></video>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Fines -->
            <?php if (!empty($fines)): ?>
            <div class="card" style="border-left:3px solid var(--danger);">
                <div class="card-header"><h3><i class="fas fa-gavel"></i> Fines Issued</h3></div>
                <?php foreach($fines as $f): ?>
                <div style="padding:14px 22px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
                    <div>
                        <p style="font-weight:700;color:var(--danger);font-family:var(--font-mono);font-size:16px;margin-bottom:4px;">PKR <?= number_format($f['amount'],2) ?></p>
                        <p style="font-size:13px;color:var(--text-secondary);margin-bottom:3px;"><?= clean($f['reason']) ?></p>
                        <p style="font-size:11px;color:var(--text-muted);font-family:var(--font-mono);"><?= $f['issued_date'] ?></p>
                    </div>
                    <?php if($f['paid_status']==='paid'): ?>
                        <span class="badge" style="background:rgba(34,197,94,0.1);color:#4ade80;border-color:rgba(34,197,94,0.25);">✅ Paid</span>
                    <?php else: ?>
                        <span class="badge" style="background:rgba(239,68,68,0.1);color:#f87171;border-color:rgba(239,68,68,0.25);">⏳ Unpaid</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Work Orders -->
            <?php if (!empty($workOrders) && (isAdmin() || isOfficer())): ?>
            <?php
            $woStatusMap = [
                'pending'     => ['rgba(245,158,11,0.1)','#fbbf24','⏳ Pending'],
                'in_progress' => ['rgba(99,102,241,0.1)', '#818cf8','🔧 In Progress'],
                'completed'   => ['rgba(16,185,129,0.1)', '#34d399','✅ Completed'],
                'cancelled'   => ['rgba(239,68,68,0.1)',  '#f87171','❌ Cancelled'],
            ];
            ?>
            <div class="card" style="border-left:3px solid var(--info);">
                <div class="card-header"><h3><i class="fas fa-clipboard-list"></i> Work Orders</h3></div>
                <?php foreach($workOrders as $wo):
                    [$wbg,$wcol,$wlbl] = $woStatusMap[$wo['status']] ?? $woStatusMap['pending'];
                ?>
                <div style="padding:14px 22px;border-bottom:1px solid var(--border);">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                        <span style="font-family:var(--font-mono);color:var(--gold-light);font-size:13px;font-weight:700;">WO #<?= $wo['work_order_id'] ?></span>
                        <span class="badge" style="background:<?= $wbg ?>;color:<?= $wcol ?>;border-color:<?= $wbg ?>;font-size:11px;"><?= $wlbl ?></span>
                    </div>
                    <p style="font-size:13px;color:var(--text-secondary);margin-bottom:6px;line-height:1.6;"><?= clean($wo['description']) ?></p>
                    <div style="font-size:12px;color:var(--text-muted);display:flex;gap:14px;flex-wrap:wrap;">
                        <span><i class="fas fa-hard-hat" style="color:var(--gold);margin-right:4px;"></i><?= clean($wo['contractor_name']) ?></span>
                        <span><i class="fas fa-user-tie" style="color:var(--gold);margin-right:4px;"></i><?= clean($wo['officer_name']) ?></span>
                        <span><i class="fas fa-calendar" style="color:var(--gold);margin-right:4px;"></i><?= $wo['issued_date'] ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT -->
        <div>
            <!-- Timeline -->
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-history"></i> Status Timeline</h3></div>
                <div class="timeline" style="padding:16px 22px;">
                    <?php if(empty($statusLogs)): ?>
                    <p style="color:var(--text-muted);font-size:14px;">No updates yet.</p>
                    <?php else: foreach ($statusLogs as $log):
                        $dotC = match($log['new_status']) { 'resolved'=>'green','escalated'=>'red','in_progress'=>'blue',default=>'gold' };
                    ?>
                    <div class="timeline-item">
                        <div class="timeline-dot <?= $dotC ?>"></div>
                        <div class="timeline-content">
                            <strong><?= ucfirst(str_replace('_',' ',$log['new_status'])) ?></strong>
                            <?php if($log['officer_name']): ?>
                                <span style="color:var(--text-muted);"> — <?= clean($log['officer_name']) ?></span>
                            <?php endif; ?>
                            <p class="timeline-time"><?= $log['changed_at'] ?></p>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <!-- Inspection -->
            <?php if ($inspection): ?>
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-search-location"></i> Inspection Report</h3></div>
                <div class="detail-list">
                    <div class="detail-row"><span>Officer</span><strong><?= clean($inspection['officer_name']) ?></strong></div>
                    <div class="detail-row"><span>Date</span><strong><?= $inspection['inspection_date'] ?></strong></div>
                    <div class="detail-row"><span>Result</span>
                        <?php $rC=['resolved'=>'var(--success)','action_required'=>'var(--danger)','in_progress'=>'var(--warning)'][$inspection['result']]??'var(--text-secondary)'; ?>
                        <strong style="color:<?= $rC ?>;"><?= clean(ucfirst(str_replace('_',' ',$inspection['result']))) ?></strong>
                    </div>
                </div>
                <div class="detail-description"><strong>Report</strong><p><?= clean($inspection['report']) ?></p></div>
            </div>
            <?php endif; ?>

            <!-- Resolution -->
            <?php if ($resolution): ?>
            <div class="card" style="border-left:3px solid var(--success);">
                <div class="card-header"><h3><i class="fas fa-check-circle" style="color:var(--success);"></i> Resolution</h3></div>
                <div class="detail-list">
                    <div class="detail-row"><span>Officer</span><strong><?= clean($resolution['officer_name']) ?></strong></div>
                    <div class="detail-row"><span>Date</span><strong><?= $resolution['resolved_date'] ?></strong></div>
                    <div class="detail-row"><span>Quality Check</span>
                        <span class="badge" style="background:<?= $resolution['quality_check']==='passed'?'rgba(34,197,94,0.1)':'rgba(245,158,11,0.1)' ?>;color:<?= $resolution['quality_check']==='passed'?'#4ade80':'#fbbf24' ?>;border-color:transparent;">
                            <?= clean(ucfirst(str_replace('_',' ',$resolution['quality_check']))) ?>
                        </span>
                    </div>
                </div>
                <div class="detail-description"><strong>Action Taken</strong><p><?= clean($resolution['action_taken']) ?></p></div>
            </div>
            <?php endif; ?>

            <!-- Appeal -->
            <?php if ($appeal): ?>
            <?php
            $apM=['pending'=>['rgba(245,158,11,0.1)','#fbbf24','⏳ Under Review'],'accepted'=>['rgba(16,185,129,0.1)','#34d399','✅ Accepted'],'rejected'=>['rgba(239,68,68,0.1)','#f87171','❌ Rejected']];
            [$apBg,$apCol,$apLbl]=$apM[$appeal['status']]??$apM['pending'];
            ?>
            <div class="card" style="border-left:3px solid <?= $apCol ?>;">
                <div class="card-header"><h3><i class="fas fa-balance-scale"></i> Appeal Filed</h3></div>
                <div style="padding:20px 26px;">
                    <span class="badge" style="background:<?= $apBg ?>;color:<?= $apCol ?>;border-color:<?= $apBg ?>;margin-bottom:14px;display:inline-flex;"><?= $apLbl ?></span>
                    <div class="detail-list">
                        <div class="detail-row"><span>Filed</span><strong><?= $appeal['filed_date'] ?></strong></div>
                        <?php if($appeal['decision_date']): ?><div class="detail-row"><span>Decision</span><strong><?= $appeal['decision_date'] ?></strong></div><?php endif; ?>
                    </div>
                    <div style="margin-top:12px;background:var(--gold-trace);border:1px solid var(--border);border-radius:var(--r-sm);padding:14px 16px;">
                        <p style="font-size:11px;font-weight:700;color:var(--gold);text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;">Citizen's Reason</p>
                        <p style="font-size:14px;color:var(--text-secondary);line-height:1.65;"><?= clean($appeal['reason']) ?></p>
                    </div>
                    <?php if(isAdmin() && $appeal['status']==='pending'): ?>
                    <div style="margin-top:14px;display:flex;gap:8px;">
                        <form method="POST" action="<?= APP_URL ?>/admin/manage_appeals.php">
                            <input type="hidden" name="appeal_id" value="<?= $appeal['appeal_id'] ?>">
                            <input type="hidden" name="complaint_id" value="<?= $complaint_id ?>">
                            <input type="hidden" name="decision" value="accepted">
                            <button type="submit" name="decide" class="btn btn-success btn-sm" onclick="return confirm('Accept this appeal?')"><i class="fas fa-check"></i> Accept</button>
                        </form>
                        <form method="POST" action="<?= APP_URL ?>/admin/manage_appeals.php">
                            <input type="hidden" name="appeal_id" value="<?= $appeal['appeal_id'] ?>">
                            <input type="hidden" name="complaint_id" value="<?= $complaint_id ?>">
                            <input type="hidden" name="decision" value="rejected">
                            <button type="submit" name="decide" class="btn btn-danger btn-sm" onclick="return confirm('Reject?')"><i class="fas fa-times"></i> Reject</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Feedback -->
            <?php if (isCitizen() && $c['status'] === 'resolved'): ?>
                <?php if ($feedback): ?>
                <div class="card">
                    <div class="card-header"><h3><i class="fas fa-star"></i> Your Feedback</h3></div>
                    <div style="padding:20px 26px;">
                        <div class="rating-display" style="margin-bottom:10px;">
                            <?php for($i=1;$i<=5;$i++): ?><i class="fas fa-star <?= $i<=$feedback['citizen_rating']?'star-filled':'star-empty' ?>" style="font-size:20px;"></i><?php endfor; ?>
                            <span style="margin-left:8px;color:var(--text-secondary);"><?= $feedback['citizen_rating'] ?>/5</span>
                        </div>
                        <p style="color:var(--text-secondary);font-size:15px;line-height:1.65;"><?= clean($feedback['comments']) ?></p>
                    </div>
                </div>
                <?php else: ?>
                <div class="card card-gold">
                    <div class="card-header"><h3><i class="fas fa-star"></i> Rate this Resolution</h3></div>
                    <form method="POST" class="form">
                        <div class="form-section">
                            <div class="form-group">
                                <label>Your Rating *</label>
                                <select name="rating" required>
                                    <option value="">Select Rating</option>
                                    <option value="5">&#11088;&#11088;&#11088;&#11088;&#11088; Excellent</option>
                                    <option value="4">&#11088;&#11088;&#11088;&#11088; Good</option>
                                    <option value="3">&#11088;&#11088;&#11088; Average</option>
                                    <option value="2">&#11088;&#11088; Poor</option>
                                    <option value="1">&#11088; Very Poor</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Comments</label>
                                <textarea name="comments" rows="3" placeholder="Share your experience..."></textarea>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" name="submit_feedback" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Submit Feedback</button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
            <?php elseif ((isAdmin()||isOfficer()) && $feedback): ?>
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-star"></i> Citizen Feedback</h3></div>
                <div style="padding:20px 26px;">
                    <div class="rating-display" style="margin-bottom:10px;">
                        <?php for($i=1;$i<=5;$i++): ?><i class="fas fa-star <?= $i<=$feedback['citizen_rating']?'star-filled':'star-empty' ?>" style="font-size:20px;"></i><?php endfor; ?>
                        <span style="margin-left:8px;color:var(--text-secondary);"><?= $feedback['citizen_rating'] ?>/5</span>
                    </div>
                    <p style="color:var(--text-secondary);font-size:15px;line-height:1.65;"><?= clean($feedback['comments']) ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Admin actions panel -->
            <?php if (isAdmin()): ?>
            <div class="card" style="border-left:3px solid var(--gold);">
                <div class="card-header"><h3><i class="fas fa-tools"></i> Admin Actions</h3></div>
                <div style="padding:18px 22px;display:flex;flex-direction:column;gap:10px;">
                    <a href="<?= APP_URL ?>/admin/fines.php" class="btn btn-secondary" style="justify-content:center;"><i class="fas fa-gavel"></i> Issue a Fine for this Complaint</a>
                    <a href="<?= APP_URL ?>/admin/manage_appeals.php" class="btn btn-secondary" style="justify-content:center;"><i class="fas fa-balance-scale"></i> Manage Appeals</a>
                    <a href="<?= APP_URL ?>/admin/manage_complaint.php" class="btn btn-secondary" style="justify-content:center;"><i class="fas fa-user-tie"></i> Assign Officer</a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="form-actions" style="margin-top:10px;">
        <?php if(isCitizen()): ?>
        <a href="<?= APP_URL ?>/complaints/track.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to My Complaints</a>
        <?php if($c['status']==='resolved' && !$appeal): ?>
        <a href="<?= APP_URL ?>/complaints/appeal.php?id=<?= $complaint_id ?>" class="btn btn-secondary"><i class="fas fa-balance-scale"></i> Not Satisfied? File Appeal</a>
        <?php endif; ?>
        <?php elseif(isOfficer()): ?>
        <a href="<?= APP_URL ?>/officer/assigned_complaints.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to My Tasks</a>
        <a href="<?= APP_URL ?>/officer/update_status.php?id=<?= $complaint_id ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Update Status</a>
        <?php else: ?>
        <a href="<?= APP_URL ?>/admin/manage_complaint.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Complaints</a>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>